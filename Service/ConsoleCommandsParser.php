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
    private $commandsCache;

    function __construct (KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    private function getConsoleApplication()
    {
        if (!$this->consoleApplication) {
            $this->consoleApplication = new Application($this->kernel);
            $this->consoleApplication->setAutoExit(false);
        }
        return $this->consoleApplication;
    }

    public function getNamespaces()
    {
        $commands = $this->requestCommands();

        return $commands['namespaces'];
    }

    public function getAllCommands($withAliases = true)
    {
        $commands = $this->requestCommands();

        $result = array();
        foreach ($commands['commands'] as $command) {
            $result[$command['name']] = $command;
            if (count($command['aliases']) && $withAliases) {
                foreach ($command['aliases'] as $alias) {
                    $result[$alias] = $command;
                }
            }
        }
        return $result;
    }

    public function getCommandsList()
    {
        return array_keys($this->getAllCommands());
    }

    private function requestCommands()
    {
        if (!$this->commandsCache) {
            $input = new ArrayInput(array(
                'command'   =>  'list',
                '--format'  =>  'json'
            ));

            $output = new StreamOutput(fopen('php://memory', 'w+'));
            $this->getConsoleApplication()->run($input, $output);

            rewind($output->getStream());

            $this->commandsCache = json_decode(stream_get_contents($output->getStream()), true);
        }

        return $this->commandsCache;
    }

}