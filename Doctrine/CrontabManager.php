<?php


namespace AgentSIB\CrontabBundle\Doctrine;


use AgentSIB\CrontabBundle\Model\AbstractCrontabManager;
use AgentSIB\CrontabBundle\Service\ConsoleCommandsParser;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class CrontabManager extends AbstractCrontabManager
{
    /** @var  EntityManager */
    protected $om;

    /** @var  EntityRepository */
    protected $repository;

    public function __construct (ConsoleCommandsParser $commandsParser, ObjectManager $om, $class)
    {
        if (!$om instanceof EntityManager) {
            throw new \Exception('Wrong $om');
        }
        parent::__construct($commandsParser, $om, $class);
    }


    /**
     * {@inheritdoc}
     */
    public function getDatabaseCronjobs ()
    {
        return $this->om->createQueryBuilder()
            ->select('c')
            ->from($this->class, 'c', 'c.id')
            ->addOrderBy('c.priority', 'ASC')
            ->getQuery()->getResult();
    }



}