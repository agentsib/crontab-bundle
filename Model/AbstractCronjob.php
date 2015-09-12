<?php


namespace AgentSIB\CrontabBundle\Model;


abstract class AbstractCronjob
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var string
     */
    protected $cronExpression;

    /**
     * @var \DateTime
     */
    protected $lastExecution;

    /**
     * @var int
     */
    protected $lastReturnCode;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var boolean
     */
    protected $executeImmediately;

    /**
     * @var boolean
     */
    protected $disabled;

    /**
     * @var bool
     */
    protected $locked;

    public function __construct()
    {
        $this->lastExecution = null;
        $this->lastReturnCode = -1;
        $this->logFile = null;
        $this->priority = 0;
        $this->executeImmediately = false;
        $this->disabled = false;
        $this->locked = false;
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return AbstractCronjob
     */
    public function setName ($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommand ()
    {
        return $this->command;
    }

    /**
     * @param string $command
     *
     * @return AbstractCronjob
     */
    public function setCommand ($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments ()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     *
     * @return AbstractCronjob
     */
    public function setArguments ($arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return string
     */
    public function getCronExpression ()
    {
        return $this->cronExpression;
    }

    /**
     * @param string $cronExpression
     *
     * @return AbstractCronjob
     */
    public function setCronExpression ($cronExpression)
    {
        $this->cronExpression = $cronExpression;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastExecution ()
    {
        return $this->lastExecution;
    }

    /**
     * @param \DateTime $lastExecution
     *
     * @return AbstractCronjob
     */
    public function setLastExecution ($lastExecution)
    {
        $this->lastExecution = $lastExecution;

        return $this;
    }

    /**
     * @return int
     */
    public function getLastReturnCode ()
    {
        return $this->lastReturnCode;
    }

    /**
     * @param int $lastReturnCode
     *
     * @return AbstractCronjob
     */
    public function setLastReturnCode ($lastReturnCode)
    {
        $this->lastReturnCode = $lastReturnCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogFile ()
    {
        return $this->logFile;
    }

    /**
     * @param string $logFile
     *
     * @return AbstractCronjob
     */
    public function setLogFile ($logFile)
    {
        $this->logFile = $logFile;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority ()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     *
     * @return AbstractCronjob
     */
    public function setPriority ($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isExecuteImmediately ()
    {
        return $this->executeImmediately;
    }

    /**
     * @param boolean $executeImmediately
     *
     * @return AbstractCronjob
     */
    public function setExecuteImmediately ($executeImmediately)
    {
        $this->executeImmediately = $executeImmediately;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDisabled ()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $disabled
     *
     * @return AbstractCronjob
     */
    public function setDisabled ($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLocked ()
    {
        return $this->locked;
    }

    /**
     * @param boolean $locked
     *
     * @return AbstractCronjob
     */
    public function setLocked ($locked)
    {
        $this->locked = $locked;

        return $this;
    }

}