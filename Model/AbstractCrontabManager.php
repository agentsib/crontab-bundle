<?php


namespace AgentSIB\CrontabBundle\Model;


use AgentSIB\CrontabBundle\Service\ConsoleCommandsParser;
use Cron\CronExpression;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

abstract class AbstractCrontabManager
{

    /** @var  ObjectManager */
    protected $om;

    /** @var  ObjectRepository */
    protected $repository;

    protected $class;

    /** @var  ConsoleCommandsParser */
    protected $commandsParser;

    protected $configCronjobs;

    public function __construct(ConsoleCommandsParser $commandsParser, ObjectManager $om, $class)
    {
        $this->om = $om;
        $this->repository = $om->getRepository($class);
        $this->commandsParser = $commandsParser;

        $metadata = $om->getClassMetadata($class);
        $this->class = $metadata->getName();
    }

    public function registryConfigCronjobs($jobs)
    {
        $commandsList = $this->commandsParser->getCommandsList();

        foreach ($jobs as $job_id => $job) {
            if (!in_array($job['command'], $commandsList)) {
                throw new \Exception(sprintf('Command "%s" non exist', $job['command']));
            }
            try {
                CronExpression::factory($job['expression']);
            } catch (\InvalidArgumentException $e) {
                throw new \Exception(sprintf('Cron syntax error for job "%s": %s', $job_id, $e->getMessage()));
            }
        }

        $this->configCronjobs = $jobs;
    }

    public function getCronjobByName($name)
    {
        $cronjobs = $this->getDatabaseCronjobs();

        return isset($cronjobs[$name])?$cronjobs[$name]:null;
    }

    public function syncCronjobs()
    {
        $dbCronjobs = $this->getDatabaseCronjobs();

        foreach ($this->configCronjobs as $job_id => $job) {
            if (!isset($dbCronjobs[$job_id])) {
                $cronjob = $this->createCronjob();
                $cronjob->setName($job_id);
                $this->om->persist($cronjob);

                $dbCronjobs[$job_id] = $cronjob;
            } else {
                $cronjob = $dbCronjobs[$job_id];
            }

            $cronjob->setCommand($job['command']);
            $cronjob->setArguments($job['arguments']);
            $cronjob->setCronExpression($job['expression']);
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

    /**
     * @return AbstractCronjob[]
     */
    public function getCronjobsForExecute()
    {
        $result = array();
        foreach ($this->getDatabaseCronjobs() as $job_id => $cronjob) {
            if ($cronjob->isLocked() || $cronjob->isDisabled()) {
                continue;
            }
            $result[] = $cronjob;
        }
        return $result;
    }


    public function flush()
    {
        $this->om->flush();
    }

    public function getClass ()
    {
        return $this->class;
    }

}