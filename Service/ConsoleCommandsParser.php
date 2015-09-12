<?php


namespace AgentSIB\CrontabBundle\Service;


use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class ConsoleCommandsParser
{
    private $kernel;
    private $consoleApplication;

    function __construct (KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    private function getConsoleApplication()
    {
        if (!$this->consoleApplication) {
            $this->consoleApplication = new Application($this->kernel);
        }
        return $this->consoleApplication;
    }


    public function getCommands()
    {
        $input = new ArrayInput(array(
            'command'   =>  'list',
            '--format'  =>  'json'
        ));

        $output = new StreamOutput(fopen('php://memory', 'w+'));
        $this->getConsoleApplication()->run($input, $output);

        rewind($output->getStream());

        $result = stream_get_contents($output->getStream());

        return $result;
    }

}