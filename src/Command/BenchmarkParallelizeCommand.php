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
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BenchmarkParallelizeCommand extends Command
{
    protected static $defaultName = 'parallelize';

    private $benchmarkerBinary;
    private $workingDirectory;

    public function __construct(string $workingDirectory, string $benchmarkerBinary)
    {
        $this->benchmarkerBinary = $benchmarkerBinary;
        $this->workingDirectory = $workingDirectory;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'test-files',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The file you would like to test.'
            )
            ->addOption(
                'php-binary',
                'p',
                InputOption::VALUE_REQUIRED,
                'The php script to use to run the tests. You can specify a docker-based script if you need.',
                'php'
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_REQUIRED,
                'The number of times each test is executed (injected in each script).'
            )
            ->setDescription('Runs the specified test files in parallel.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $testFiles = $input->getArgument('test-files');

        /** @var Process[] $processes */
        $processes = [];
        $files = [];

        $io->comment('Creating processes...');

        foreach ($testFiles as $testFile) {
            $realFilename = \basename($testFile, '.php').'.php';
            $directory = \pathinfo($testFile, PATHINFO_DIRNAME);

            // If not a root directory, we prepend working dir to it
            if (!\preg_match('~^(?:/|[A-Z]:)~i', $directory)) {
                $directory = \rtrim($this->workingDirectory, '/\\').DIRECTORY_SEPARATOR.trim($directory, '/\\');
                $realFilename = $directory.DIRECTORY_SEPARATOR.$realFilename;
            }

            $args = [
                $input->getOption('php-binary'),
                $this->benchmarkerBinary,
                'run',
                '--no-interaction',
                '--output-format=json',
                '--no-progress',
            ];

            if (null !== $count = $input->getOption('count')) {
                $args[] = "--count=$count";
            }

            $args[] = '--';
            $args[] = $realFilename;

            $files[] = $testFile;
            $processes[] = new Process($args);
        }

        $io->comment('Starting processes...');

        foreach ($processes as $i => $process) {
            $processesOutputs[$i] = '';
            $process->start();
        }

        /** @var Process[] $finishedProcesses */
        $finishedProcesses = [];
        $numberFinished = 0;
        $numberOfProcesses = \count($processes);

        $vb = $io->getVerbosity();
        $io->setVerbosity($io::VERBOSITY_DEBUG);
        $progress = new ProgressIndicator($io);
        $io->setVerbosity($vb);

        $progress->start('Executing processes...');

        while ($numberFinished !== $numberOfProcesses) {
            foreach ($processes as $i => $process) {
                $progress->advance();
                if ($process->isRunning()) {
                    continue;
                }
                $finishedProcesses[$i] = $process;
                $numberFinished++;

                unset($processes[$i]);
            }
        }

        $progress->finish('Done!');

        $rows = [];

        foreach ($finishedProcesses as $i => $process) {
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $out = $process->getOutput();
            $decoded = @\json_decode($out, true) ?? [[null]];

            foreach ($decoded as $line) {
                if (!isset($line['file_name'], $line['duration'], $line['percent_total'])) {
                    $io->error(sprintf('Could not decode output from test file "%s"', $files[$i]));
                    continue;
                }
                $rows[] = [
                    $line['file_name'],
                    $line['test_name'],
                    $line['duration'],
                    $line['percent_total'],
                ];
            }
            $rows[] = new TableSeparator();
        }

        if (end($rows) instanceof TableSeparator) {
            array_pop($rows);
        }

        $io->table([
            'File name',
            'Test name',
            'Duration',
            '% of total',
        ], $rows);
    }
}
