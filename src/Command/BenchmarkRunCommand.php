<?php

declare(strict_types=1);

/**
 * This file is part of the benchmarker package.
 *
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orbitale\Benchmarker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BenchmarkRunCommand extends Command
{
    use DetailsTrait;

    public const DEFAULT_COUNT = 10;

    private const OUTPUT_FORMATS = ['json', 'xml'];

    protected static $defaultName = 'run';

    private $workingDirectory;

    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setAliases(['benchmark', 'bench'])
            ->addArgument(
                'test-files',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The file you would like to test.'
            )
            ->addOption('output-format', 'f', InputOption::VALUE_OPTIONAL, 'Change the output format', '')
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'If set, progress bar will not be shown.'
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_REQUIRED,
                'The number of times each test is executed.',
                self::DEFAULT_COUNT
            )
            ->setHelp(<<<HELP
The files you would like to test must be a PHP file that returns an <comment>iterable</> for which each item is a <comment>callable</>.
This callable will be your single test.

If <info>--progress</> is set, the progress information about current test count will not be visible.
This is useful for terminals that do not handle chariot returns without line feeds (like during an HTTP request).

You should specify <info>--count</> to make sure your benchmarks are executed a significant amount of times.
HELP
)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \set_include_path(\get_include_path().';'.$this->workingDirectory);

        $outputFormat = $input->getOption('output-format');

        if ($outputFormat && !\in_array($outputFormat, self::OUTPUT_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid format %s. Available: %s', $outputFormat, \implode(', ', self::OUTPUT_FORMATS)));
        }

        $io = new SymfonyStyle($input, $output);
        $testFiles = $input->getArgument('test-files');

        $count = $input->getOption('count');

        $total = 0;

        if (!$outputFormat) {
            $this->outputDetails($io, $count);
        }

        $progress = !($input->hasOption('no-progress') && $input->getOption('no-progress'));

        $times = [];

        foreach ($testFiles as $testFile) {
            \ob_start();
            $tests = $this->fetchTestsFromFile($testFile);
            \ob_end_flush();
            foreach ($tests as $testName => $test) {
                $time_start = \microtime(true);
                if (!$progress && !$outputFormat) {
                    $io->comment("Executing $testFile#$testName");
                }
                for ($i = 1; $i <= $count; $i++) {
                    if ($progress && !$outputFormat) {
                        $io->write(
                            \str_pad(" > Executing $testFile#$testName", 50).
                            \str_pad("$i/$count", 10, ' ', STR_PAD_LEFT).
                            "\r"
                        );
                    }
                    \ob_start();
                    $test();
                    \ob_end_clean();
                }
                $result = microtime(true) - $time_start;
                $times[] = [
                    'file_name' => $testFile,
                    'test_name' => $testName,
                    'duration' => $result,
                ];
                $total += $result;
                if (!$outputFormat) {
                    $io->writeln(
                        \str_pad(" $testFile#$testName".' ', 50).
                        \str_pad(((int) $result).' sec.', 10, ' ', STR_PAD_LEFT).
                        \str_pad('', \strlen((string) $count) * 2 + \strlen((string) $testName) + 4, ' ', STR_PAD_LEFT)
                    );
                }
            }
            $times[] = new TableSeparator();
        }

        $times = \array_map(
            function ($time) use ($total) {
                if ($time instanceof TableSeparator) {
                    return $time;
                }

                return [
                    $time['file_name'],
                    $time['test_name'],
                    \number_format($time['duration'], 3).' seconds',
                    \number_format($time['duration'] * 100 / $total, 3).' %',
                ];
            },
            $times
        );

        $times[] = ['Total time', null, \number_format($total, 3).' seconds', ''];

        if (!$outputFormat) {
            $io->title('Summary');

            $io->table([
                'File name',
                'Test name',
                'Duration',
                '% of total',
            ], $times);

            $io->success('Done!');
        } else {
            $times = \array_map(function($time) {
                if ($time instanceof TableSeparator) {
                    return null;
                }
                return [
                    'file_name' => $time[0],
                    'test_name' => $time[1],
                    'duration' => $time[2],
                    'percent_total' => $time[3],
                ];
            }, $times);

            $io->write(\json_encode(\array_values(\array_filter($times)), JSON_PRETTY_PRINT));
        }
    }

    private function fetchTestsFromFile(string $testFile): iterable
    {
        return require $testFile;
    }
}
