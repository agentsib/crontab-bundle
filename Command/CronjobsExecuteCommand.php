<?php


namespace AgentSIB\CrontabBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CronjobsExecuteCommand extends Command implements ContainerAwareInterface
{
    /** @var  ContainerInterface */
    private $container;

    protected function configure ()
    {
        $this->setName('agentsib:crontab:execute')
            ->setDescription('Execute crontab task');

        $this->addArgument('command_id', InputArgument::REQUIRED, 'Command ID');
    }


    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $manager = $this->container->get('agentsib_crontab.manager');
        $cronjob = $manager->getCronjobByName($input->getArgument('command_id'));

        if (!$cronjob) {
            throw new \Exception('Cronjob not found');
        }

        try {
            /** @var Command $command */
            $command = $this->getApplication()->find($cronjob->getCommand());
        } catch (\InvalidArgumentException $e) {
            $cronjob->setLastReturnCode(-1);
            $manager->flush();
            $output->writeln(sprintf('<error>Cannot find "%s" for job "%s"</error>', $cronjob->getCommand(), $cronjob->getName()));
            return;
        }


        $arguments = array();
        foreach ($cronjob->getArguments() as $arg) {
            $pos = mb_strpos($arg, '=');
            if (mb_strpos($arg, '=') !== false) {
                $arguments[mb_substr($arg, 0, $pos)] = mb_substr($arg, $pos + 1);
            } else {
                $arguments[$arg] = true;
            }
        }
        $inputExec = new ArrayInput(array_merge(array(
            'command'   =>  $command->getName(),
        ), $arguments));

        $inputExec->setInteractive(false);
        $output->setDecorated(true);

        return $command->run($inputExec, $output);

    }


    /**
     * {@inheritdoc}
     */
    public function setContainer (ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}