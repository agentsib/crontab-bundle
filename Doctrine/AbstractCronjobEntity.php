<?php


namespace AgentSIB\CrontabBundle\Doctrine;

use AgentSIB\CrontabBundle\Model\AbstractCronjob;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class AbstractCronjobEntity extends AbstractCronjob
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="id", type="string", length=255)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="command", type="string", length=64)
     */
    protected $command;

    /**
     * @var string
     *
     * @ORM\Column(name="arguments", type="array", nullable=true)
     */
    protected $arguments;

    /**
     * @var string
     *
     * @ORM\Column(name="cron_expression", type="string", length=32, nullable=true)
     */
    protected $cronExpression;

    /**
     * @var integer
     *
     * @ORM\Column(name="execute_timeout", type="integer")
     */
    protected $executeTimeout;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_execution", type="datetime", nullable=true)
     */
    protected $lastExecution;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_return_code", type="integer")
     */
    protected $lastReturnCode;

    /**
     * @var string
     *
     * @ORM\Column(name="log_file", type="string", nullable=true, length=32)
     */
    protected $logFile;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer")
     */
    protected $priority;

    /**
     * @var boolean
     *
     * @ORM\Column(name="execute_immediately", type="boolean")
     */
    protected $executeImmediately;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean")
     */
    protected $disabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    protected $locked;
}