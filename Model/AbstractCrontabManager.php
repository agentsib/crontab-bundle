<?php


namespace AgentSIB\CrontabBundle\Model;


use AgentSIB\CrontabBundle\Event\CronjobsCommandEvent;
use AgentSIB\CrontabBundle\Service\ConsoleCommandsParser;
use Cron\CronExpression;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractCrontabManager
{
    const CHANNEL_INFO = 'INFO';
    const CHANNEL_DEBUG = 'DEBUG';
    const CHANNEL_ERROR = 'ERROR';

    /** @var  ObjectManager */
    protected $om;

    /** @var  ObjectRepository */
    protected $repository;

    protected $class;

    /** @var  ConsoleCommandsParser */
    protected $commandsParser;

    protected $configCronjobs;

    protected $logPath;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(ConsoleCommandsParser $commandsParser, ObjectManager $om, EventDispatcherInterface $dispatcher, $class, $logPath)
    {
        $this->om = $om;
        $this->repository = $om->getRepository($class);
        $this->commandsParser = $commandsParser;
        $this->logPath = $logPath;
        $this->dispatcher = $dispatcher;

        $metadata = $om->getClassMetadata($class);
        $this->class = $metadata->getName();
    }

    public function registryConfigCronjobs($jobs)
    {
        $commandsList = $this->commandsParser->getCommandsList();

        foreach ($jobs as $jobId => $job) {
            if (!in_array($job['command'], $commandsList)) {
                throw new \Exception(sprintf('Command "%s" non exist', $job['command']));
            }
            if (!is_null($job['expression'])) {
                try {
                    CronExpression::factory($job['expression']);
                } catch (\InvalidArgumentException $e) {
                    throw new \Exception(sprintf('Cron syntax error for job "%s": %s', $jobId, $e->getMessage()));
                }
            }

        }

        $this->configCronjobs = $jobs;
    }

    public function getCronjobById($id)
    {
        $cronjobs = $this->getDatabaseCronjobs();

        return isset($cronjobs[$id])?$cronjobs[$id]:null;
    }

    public function syncCronjobs()
    {
        $dbCronjobs = $this->getDatabaseCronjobs();

        foreach ($this->configCronjobs as $jobId => $job) {
            if (!isset($dbCronjobs[$jobId])) {
                $cronjob = $this->createCronjob();
                $cronjob->setId($jobId);
                $this->om->persist($cronjob);

                $dbCronjobs[$jobId] = $cronjob;
            } else {
                $cronjob = $dbCronjobs[$jobId];
            }

            $cronjob->setCommand($job['command']);
            $cronjob->setArguments($job['arguments']);
            $cronjob->setCronExpression($job['expression']);
            $cronjob->setExecuteTimeout($job['execute_timeout']);
            $cronjob->setLogFile($job['log_file']);
        }

        $i = 0;
        foreach ($dbCronjobs as $job_id => $cronjob) {
            if (!isset($this->configCronjobs[$job_id])) {
                if (!$cronjob->isLocked()) {
                    $this->om->remove($cronjob);
                    unset($dbCronjobs[$job_id]);
                }
            } else {
                $cronjob->setPriority($i++);
            }
        }

        $this->om->flush();
    }

    /**
     * @return AbstractCronjob
     */
    public function createCronjob()
    {
        $class = $this->getClass();
        $cronjob = new $class;

        return $cronjob;
    }

    /**
     * @return AbstractCronjob[]
     */
    abstract public function getDatabaseCronjobs();


    public function startCronjob(AbstractCronjob $cronjob)
    {
        $this->om->refresh($cronjob);
        $cronjob->setLastExecution(new \DateTime());
        $cronjob->setLocked(true);
        $this->om->flush();
    }

    public function stopCronjob(AbstractCronjob $cronjob, $responseCode = 0)
    {
        $this->om->refresh($cronjob);
        $cronjob->setLastReturnCode($responseCode);
        $cronjob->setLocked(false);
        $cronjob->setExecuteImmediately(false);
        $this->om->flush();
    }

    public function enableCronjob(AbstractCronjob $cronjob)
    {
        $this->om->refresh($cronjob);
        $cronjob->setDisabled(false);
        $this->om->flush();
    }

    public function disableCronjob(AbstractCronjob $cronjob)
    {
        $this->om->refresh($cronjob);
        $cronjob->setDisabled(true);
        $this->om->flush();
    }

    public function executeImmediatelyCronjob(AbstractCronjob $cronjob)
    {
        $this->om->refresh($cronjob);
        $cronjob->setExecuteImmediately(true);
        $this->om->flush();
    }

    public function getClass ()
    {
        return $this->class;
    }

    public function appendToLog(AbstractCronjob $cronjob, $channel, $str)
    {
        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath);
        }
        if ($cronjob->getLogFile()) {
            $date = new \DateTime();
            $strings = explode(PHP_EOL, $str);
            $log = '';
            foreach ($strings as $s) {
                $log .= sprintf('%s [%s] %s: %s'.PHP_EOL, $date->format('Y-m-d H:i:s'), $cronjob->getId(), $channel, $s);
            }
            @file_put_contents($this->logPath.'/'.$cronjob->getLogFile().'.log', $log, FILE_APPEND);
        }
    }

    /**
     * Dispatch crontab event
     *
     * @param AbstractCronjob $cronjob
     * @param string          $eventType
     * @param string          $str
     */
    public function triggerEvent(AbstractCronjob $cronjob, $eventType, $str)
    {
        $event = new CronjobsCommandEvent($cronjob, $str);
        $this->dispatcher->dispatch($eventType, $event);
    }
}