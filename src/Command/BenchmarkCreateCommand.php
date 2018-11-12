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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BenchmarkCreateCommand extends Command
{
    protected static $defaultName = 'create';

    private const FILE_TEMPLATE = '<?php

declare(strict_types=1);

return (function () { {{ tests }}})();
';
    private const TEST_TEMPLATE = "
    yield '{{ test_name }}' => function () {
        // TODO
    };
";

    private $workingDirectory;

    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'The final file name')
            ->addArgument('test-names', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'A list of names you want for your tests')
            ->setDescription('This commmand creates a sample benchmark to the specified filename.')
            ->setHelp(
                <<<HELP
Usage:

$ benchmarker create my_test.php test1 test2 test3

Will output this in the <comment>my_test.php</> file:

<?php

declare(strict_types=1);

return (function () { 
    yield 'test1' => function () {
        // TODO
    };

    yield 'test2' => function () {
        // TODO
    };

    yield 'test3' => function () {
        // TODO
    };
})();
HELP
)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $filename = $input->getArgument('filename');

        $realFilename = \basename($filename, '.php').'.php';
        $directory = \pathinfo($filename, PATHINFO_DIRNAME);

        // If not a root directory, we prepend working dir to it
        if (!\preg_match('~^(?:/|[A-Z]:)~i', $directory)) {
            $directory = \rtrim($this->workingDirectory, '/\\').DIRECTORY_SEPARATOR.trim($directory, '/\\');
            $realFilename = $directory.DIRECTORY_SEPARATOR.$realFilename;
        }

        if (\file_exists($realFilename)) {
            if (!$io->confirm('File already exists. Overwrite?')) {
                return;
            }
        }

        $testNames = $input->getArgument('test-names');

        while (0 === \count($testNames)) {
            $io->block('No test names provided');
            do {
                $result = \trim($io->ask('Provide a name for one test (or leave empty if you have finished):') ?: '');
                if ($result) {
                    $testNames[] = $result;
                }
            } while ($result);
        }

        $tests = '';

        foreach ($testNames as $testName) {
            $tests .= \str_replace('{{ test_name }}', $testName, self::TEST_TEMPLATE);
        }

        $fileContent = \str_replace('{{ tests }}', $tests, self::FILE_TEMPLATE);

        if (!\is_dir($directory) && !\mkdir($directory, 0775, true) && !\is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Directory "%s" could not be created', $directory));
        }

        \file_put_contents($realFilename, $fileContent, LOCK_EX);

        $io->success("Created benchmark at $realFilename");
    }

    private function fetchTestsFromFile(string $testFile): iterable
    {
        return require $testFile;
    }
}
