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

        $this->addOption('dry-run', 'r', InputOption::VALUE_NONE, 'Just show commands for execute');
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $manager = $this->container->get('agentsib_crontab.manager');
        $manager->syncCronjobs();

        if ($input->getOption('dry-run')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('Start <comment>%s</comment> cron scripts', $input->getOption('dry-run')?"dump":"execute"));
        }

        /** @var Process[] $processes */
        $processes = array();
        $noneExecution = true;

        foreach ($manager->getDatabaseCronjobs() as $cronjob) {
            /** @var AbstractCronjob $cronjob */
            if ($cronjob->isDisabled() || $cronjob->isLocked() || !$cronjob->getCronExpression()) {
                continue;
            }

            $cron = CronExpression::factory($cronjob->getCronExpression());
            $newRunDate = $cron->getNextRunDate($cronjob->getLastExecution());
            $newDate = new \DateTime();


            if ($cronjob->isExecuteImmediately()) {

                $noneExecution = false;

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf('Immediately execution for: <comment>%s</comment>', $cronjob->getId()));
                }

                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getId()] = $this->executeCommand($cronjob, $input, $output);
                }
            } elseif (!$cronjob->getLastExecution() || $newRunDate < $newDate) {

                $noneExecution = false;

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf('Cronjob <comment>%s</comment> should be executed.', $cronjob->getId(), ''));
                }

                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getId()] = $this->executeCommand($cronjob, $input, $output);
                }
            }
        }

        if ($noneExecution) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('');
                $output->writeln('Nothing to do');
            }
        } else {
            while (count($processes) > 0) {

                foreach ($processes as $jobId => $process) {
                    usleep(500000);
                    try {
                        $process->checkTimeout();
                    } catch (\RuntimeException $e) {
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln(sprintf('Cronjob <comment>%s</comment> <error>killed by timeout</error>.', $jobId));
                        }
                        unset($processes[$jobId]);
                        continue;
                    }


                    if (!$process->isRunning()) {
                        if ($process->getExitCode() == 0) {
                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln(sprintf('Cronjob <comment>%s</comment> success completed.', $jobId));
                            }
                        } else {
                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln(sprintf('Cronjob <comment>%s</comment> completed. <error>Exit code: %s</error>', $jobId, $process->getExitCode()));
                            }
                        }

                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                            $output->writeln('-------------<info>OUTPUT</info>-------------');
                            $output->writeln($process->getOutput());
                            $output->writeln('-----------<info>END OUTPUT</info>-----------');
                            if ($process->getErrorOutput()) {
                                $output->writeln('-------------<error>ERROR</error>--------------');
                                $output->writeln($process->getErrorOutput());
                                $output->writeln('-----------<error>END ERROR</error>-----------');
                            }

                        }

                        unset($processes[$jobId]);
                    }
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
            $cronjob->getId()
        ));
        $builder->setWorkingDirectory(realpath($this->container->getParameter('kernel.root_dir').'/../'));

        $process = $builder->getProcess();
        $process->getOutput();
        $process->setTimeout($cronjob->getExecuteTimeout());

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