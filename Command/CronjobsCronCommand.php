<?php


namespace AgentSIB\CrontabBundle\Command;


use AgentSIB\CrontabBundle\Model\AbstractCronjob;
use Cron\CronExpression;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class CronjobsCronCommand extends Command implements ContainerAwareInterface
{
    /** @var  ContainerInterface */
    private $container;

    protected function configure ()
    {
        $this->setName('agentsib:crontab:cron')
            ->setDescription('Run crontab tasks');

        $this->addOption('dry-run', 'r', InputOption::VALUE_OPTIONAL, 'Just show commands for execute');
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $manager = $this->container->get('agentsib_crontab.manager');
        $manager->syncCronjobs();

        $output->writeln('<info>Start cron</info>');

        $processes = array();

        foreach ($manager->getCronjobsForExecute() as $cronjob) {

            if ($cronjob->isDisabled() || $cronjob->isLocked()) {
                continue;
            }

            $cron = CronExpression::factory($cronjob->getCronExpression());
            $newRunDate = $cron->getNextRunDate($cronjob->getLastExecution());
            $newDate = new \DateTime();

            if ($cronjob->isExecuteImmediately()) {
                $output->writeln(sprintf('Immediately execution for: <comment>%s</comment>', $cronjob->getName()));

                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getName()] = $this->executeCommand($cronjob, $input, $output);
                }
            } elseif (!$cronjob->getLastExecution() || $newRunDate < $newDate) {
                $output->writeln(
                    sprintf('Command <comment>%s</comment> should be executed - last execution : <comment>%s</comment>', $cronjob->getName(), '')
                );
                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getName()] = $this->executeCommand($cronjob, $input, $output);
                }
            }
        }

        while (count($processes) > 0) {

            foreach ($processes as $job_id => $process) {
                usleep(500000);
                try {
                    $process->checkTimeout();
                } catch (\RuntimeException $e) {

                }


                if (!$process->isRunning()) {
                    $output->writeln($process->getOutput());
                    $output->writeln($process->getErrorOutput());
                    $output->writeln(sprintf('Command <comment>%s</comment> completed. Exit code: %s', $job_id, $process->getExitCode()));
                    unset($processes[$job_id]);
                }
            }
        }
    }

    private function executeCommand(AbstractCronjob $cronjob, InputInterface $input, OutputInterface $output)
    {
        $manager = $this->container->get('agentsib_crontab.manager');
        $manager->flush();

        $executableFinder = new PhpExecutableFinder();

        if (false === $php = $executableFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        $builder = new ProcessBuilder();
        $builder->setPrefix($php);
        $builder->setArguments(array(
            'app/console',
            'agentsib:crontab:execute',
            $cronjob->getName()
        ));
        $builder->setWorkingDirectory(realpath($this->container->getParameter('kernel.root_dir').'/../'));

        $process = $builder->getProcess();
        $process->getOutput();

        $process->start();

        return $process;

    }


    /**
     * {@inheritdoc}
     */
    public function setContainer (ContainerInterface $container = null)
    {
        $this->container = $container;
    }



}