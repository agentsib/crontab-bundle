<?php


namespace AgentSIB\CrontabBundle\Command;


use AgentSIB\CrontabBundle\Model\AbstractCronjob;
use AgentSIB\CrontabBundle\Model\AbstractCrontabManager;
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

            $newRunDate = new \DateTime();
            $newDate = new \DateTime();

            if (!$cronjob->isExecuteImmediately()) {
                if ($cronjob->isDisabled() || $cronjob->isLocked() || !$cronjob->getCronExpression()) {
                    continue;
                }
                $cron = CronExpression::factory($cronjob->getCronExpression());
                $newRunDate = $cron->getNextRunDate($cronjob->getLastExecution());
            }

            if ($cronjob->isExecuteImmediately()) {

                $noneExecution = false;

                $manager->appendToLog(
                    $cronjob,
                    AbstractCrontabManager::CHANNEL_INFO,
                    sprintf('Immediately execution for: %s', $cronjob->getId())
                );

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf('Immediately execution for: <comment>%s</comment>', $cronjob->getId()));
                }

                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getId()] = $this->executeCommand($cronjob, $input, $output);
                }
            } elseif (!$cronjob->getLastExecution() || $newRunDate < $newDate) {

                $noneExecution = false;

                $manager->appendToLog(
                    $cronjob,
                    AbstractCrontabManager::CHANNEL_INFO,
                    sprintf('Cronjob %s should be executed', $cronjob->getId())
                );

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(sprintf('Cronjob <comment>%s</comment> should be executed', $cronjob->getId(), ''));
                }

                if (!$input->getOption('dry-run')) {
                    $processes[$cronjob->getId()] = $this->executeCommand($cronjob, $input, $output);
                }
            }
        }

        $cronResponseCode = 0;

        if ($noneExecution) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('');
                $output->writeln('Nothing to do');
            }
        } else {
            while (count($processes) > 0) {

                foreach ($processes as $jobId => $process) {
                    usleep(500000);
                    $cronjob = $manager->getCronjobById($jobId);
                    try {
                        $process->checkTimeout();
                    } catch (\RuntimeException $e) {
                        $cronResponseCode = 1;

                        $manager->stopCronjob($cronjob, -10);

                        $manager->appendToLog(
                            $cronjob,
                            AbstractCrontabManager::CHANNEL_ERROR,
                            sprintf('Cronjob %s killed by timeout', $cronjob->getId())
                        );

                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                            $output->writeln(sprintf('Cronjob <comment>%s</comment> <error>killed by timeout</error>.', $cronjob->getId()));
                        }
                        unset($processes[$jobId]);
                        continue;
                    }


                    if (!$process->isRunning()) {

                        if ($process->getExitCode() == 0) {

                            $manager->appendToLog(
                                $cronjob,
                                AbstractCrontabManager::CHANNEL_INFO,
                                sprintf('Cronjob %s success completed', $cronjob->getId())
                            );

                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln(sprintf('Cronjob <comment>%s</comment> success completed', $cronjob->getId()));
                            }
                        } else {
                            $cronResponseCode = 1;

                            $manager->appendToLog(
                                $cronjob,
                                AbstractCrontabManager::CHANNEL_ERROR,
                                sprintf('Cronjob %s completed. Exit code: %s', $cronjob->getId(), $process->getExitCode())
                            );

                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                                $output->writeln(sprintf('Cronjob <comment>%s</comment> completed. <error>Exit code: %s</error>', $cronjob->getId(), $process->getExitCode()));
                            }
                        }

                        $manager->appendToLog(
                            $cronjob,
                            AbstractCrontabManager::CHANNEL_DEBUG,
                            '----- Output: '.PHP_EOL.trim($process->getOutput()).PHP_EOL
                        );
                        if ($process->getErrorOutput()) {
                            $manager->appendToLog(
                                $cronjob,
                                AbstractCrontabManager::CHANNEL_DEBUG,
                                '----- Error output: '.PHP_EOL.trim($process->getErrorOutput()).PHP_EOL
                            );
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
        return $cronResponseCode;
    }


    private function executeCommand(AbstractCronjob $cronjob, InputInterface $input, OutputInterface $output)
    {
        $manager = $this->container->get('agentsib_crontab.manager');

        $executableFinder = new PhpExecutableFinder();

        if (false === $php = $executableFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        $builder = new ProcessBuilder();
        $builder->setPrefix($php);
        $builder->setArguments(array(
            $this->container->getParameter('agentsib_crontab.cronjob_console'),
            'agentsib:crontab:execute',
            $cronjob->getId()
        ));
        $builder->setWorkingDirectory(realpath($this->container->getParameter('kernel.root_dir').'/../'));

        $process = $builder->getProcess();
//        $process->getOutput();
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