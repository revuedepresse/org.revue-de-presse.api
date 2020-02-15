<?php

namespace App\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Base class for testing the CLI tools.
 * Inspired by the work of Alexandre Salomé
 * @see http://alexandre-salome.fr/blog/Test-your-commands-in-Symfony2
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected $application;

    protected $command;

    protected $commandClass;

    /**
     * @var $commandTester CommandTester
     */
    protected $commandTester;

    /**
     * Gets command
     *
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Gets command name
     *
     * @return mixed
     */
    public function getCommandName()
    {
        return $this->getCommand()->getName();
    }

    /**
     * Gets command tester
     *
     * @param $name
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    public function getCommandTester($name)
    {
        $this->command = $this->application->find($name);

        return new CommandTester($this->command);
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param $fp
     * @return string
     */
    public function getOutput($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    /**
     * Run an application command and bind its output to a temporary file handler
     *
     * @param $command
     * @return resource
     */
    public function getOutputFileHandle($command)
    {
        $stringInput = $this->getParameter('quality_assurance.string_input.class');
        $streamOutput = $this->getParameter('quality_assurance.stream_output.class');

        $fileHandle = tmpfile();

        $input = new $stringInput($command);
        $output = new $streamOutput($fileHandle);
        $this->runApplication($input, $output);

        return $fileHandle;
    }

    public function runApplication(InputInterface $input, OutputInterface $output)
    {
        $this->application->run($input, $output);
    }

    /**
     * Runs a command and returns it output
     */
    public function runCommand()
    {
        $this->setApplication();
        $fileHandle = $this->getOutputFileHandle($this->command);

        return $this->getOutput($fileHandle);
    }

    public function setUpApplication($command = null)
    {
        $commandClass = $this->commandClass;

        $this->application = new Application($this->client->getKernel());

        if (is_null($command)) {
            $this->application->add(new $commandClass);
        } else {
            $this->application->add($command);
        }

        $this->application->setAutoExit(false);
    }

    /**
     *
     */
    public function setApplication()
    {
        $this->application = new Application($this->client->getKernel());
        $this->application->setAutoExit(false);
    }

    /**
     * @return mixed
     */
    public function getXmlRpcClientMock()
    {
        return $this->getMock(
            '\fXmlRpc\ClientInterface',
            array(
                'appendParams',
                'call',
                'getUri',
                'getPrependParams',
                'getAppendParams',
                'importNewPosts',
                'importNewCategories',
                'multicall',
                'prependParams',
                'setUri',
            ),
            array(),
            '',
            false
        );
    }

    /**
     * @param $value
     * @param null $invocationCount
     * @return mixed
     */
    public function getXmlRpcClientMockReturningValue($value, $invocationCount = null)
    {
        if (is_null($invocationCount)) {
            $invocationCount = $this->any();
        } else {
            $invocationCount = $this->exactly($invocationCount);
        }

        $clientMock = $this->getXmlRpcClientMock();
        $clientMock->expects($invocationCount)
            ->method('call')
            ->will(
                $this->returnValue($value)
            );

        return $clientMock;
    }
}
