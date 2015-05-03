<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Output\StreamOutput,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Process\Process;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ModerateIndexPopulationCommand extends PopulateIndexCommand
{
    const OPTION_LOG_OUTPUT = 'log_output';

    const OPTION_ZVAL_TRACE = 'zval_trace';

    const OPTION_PROCESS_ISOLATION = 'process_isolation';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('weaving-the-web:api:manage-index-population')
            ->setAliases(['wtw:s:m:i'])
            ->setDescription('Manage population of search index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $successMessage = $this->getNoLeftoversMessage();
        $command = $this->buildManagedCommand($input);

        while (1) {
            $this->setupOutput();

            $projectDir = $this->getContainer()->getParameter('kernel.root_dir') . '/..';
            $process = new Process($command, $projectDir);
            $process->setTimeout(3600);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            } else {
                $managedOutput = $process->getOutput();
            }

            $output->writeln($managedOutput);

            if (strpos($managedOutput, $successMessage) !== false) {
                break;
            }
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     * @throws \Exception
     */
    protected function buildManagedCommand(InputInterface $input)
    {
        $availableOptions = $this->getAvailableOptions();
        $options          = $input->getOptions();
        $command          = 'php app/console weaving-the-web:api:populate-index';

        foreach ($availableOptions as $name) {
            if (array_key_exists($name, $options)) {
                $optionPrefix = '--';
                $value        = $options[$name];
                $argument     = '';

                if (is_bool($value) && $value) {
                    $argument = $optionPrefix . $name;
                } elseif (is_string($value) || is_numeric($value)) {
                    $argument .= $optionPrefix . $name . '=' . $value;
                }

                $command .= ' ' . $argument;
            } else {
                throw new \Exception(sprintf('An invalid option has been passed to this command %s', $name));
            }
        }

        return $command;
    }

    /**
     * @return array
     */
    protected function getAvailableOptions()
    {
        $availableOptions = [
            self::OPTION_INDEX,
            self::OPTION_TYPE,
            self::OPTION_RESET,
            self::OPTION_BATCH_SIZE,
            'env'
        ];

        return $availableOptions;
    }

    /**
     * @return string
     */
    protected function getOutput()
    {
        rewind($this->output->getStream());

        return stream_get_contents($this->output->getStream());
    }

    protected function setupOutput()
    {
        $outputResource = $this->getLogsResource();

        $this->output = new StreamOutput($outputResource);
        $this->output->setDecorated(false);
        $this->output->setVerbosity(true);
    }

    /**
     * @return resource
     */
    protected function getLogsResource()
    {
        $cacheDir       = $this->getContainer()->getParameter('kernel.logs_dir');
        $outputResource = fopen($cacheDir . '/index_population_output.log', 'w');

        return $outputResource;
    }
}
