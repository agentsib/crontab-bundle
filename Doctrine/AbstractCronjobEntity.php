<?php


namespace AgentSIB\CrontabBundle\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class AbstractCronjobEntity
{
    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

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
     * @ORM\Column(name="cron_expression", type="string", length=32)
     */
    protected $cronExpression;

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
     * @ORM\Column(name="log_file", type="string", length=32)
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