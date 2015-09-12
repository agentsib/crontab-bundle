<?php

namespace AgentSIB\CrontabBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AgentSIBCrontabExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load(sprintf('services.%s.yml', $config['db_driver']));
        $container->setParameter($this->getAlias().'.db_driver', $config['db_driver']);
        $container->setParameter($this->getAlias().'.model_manager_name', $config['model_manager_name']);
        $container->setParameter($this->getAlias().'.cronjob_class', $config['cronjob_class']);

        if ('orm' === $config['db_driver']) {
            $managerService = $this->getAlias().'.entity_manager';
            $doctrineService = 'doctrine';
        } else {
            $managerService = '';
            $doctrineService = '';
        }

        $definition = $container->getDefinition($managerService);

        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory(array(new Reference($doctrineService), 'getManager'));
        } else {
            $definition->setFactoryService($doctrineService);
            $definition->setFactoryMethod('getManager');
        }

        $container->setAlias($this->getAlias().'.manager', $config['crontab_manager']);

        $definition = $container->getDefinition($config['crontab_manager']);
        $definition->addMethodCall('registryConfigCronjobs', array($config['jobs']));


        $loader->load('services.yml');

    }

    public function getAlias ()
    {
        return 'agentsib_crontab';
    }


}
