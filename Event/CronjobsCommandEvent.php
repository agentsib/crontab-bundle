<?php


namespace AgentSIB\CrontabBundle\Event;


use AgentSIB\CrontabBundle\Model\AbstractCronjob;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CronjobsCommandEvent
 *
 * @package AgentSIB\CrontabBundle\Event
 */
class CronjobsCommandEvent extends Event
{
    const ON_EXECUTE_ERROR = 'agentsib.crontab.error';

    const ON_EXECUTE_SUCCESS = 'agentsib.crontab.success';

    /** @var AbstractCronjob */
    private $cronjob;

    /** @var string */
    private $message;

    /** @var string */
    private $output;

    /**
     * @param AbstractCronjob $cronjob
     * @param string|null     $message
     * @param string|null     $output
     */
    public function __construct(AbstractCronjob $cronjob, $message = null, $output = null)
    {
        $this->cronjob = $cronjob;
        $this->message = $message;
        $this->output = $output;
    }

    /**
     * @return AbstractCronjob
     */
    public function getCronjob()
    {
        return $this->cronjob;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->cronjob->getCommand();
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }
}