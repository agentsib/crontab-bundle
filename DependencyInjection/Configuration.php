<?php

namespace AgentSIB\CrontabBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('agentsib_crontab');

        $supportedDrivers = array('orm');

        $rootNode
            ->children()
                ->scalarNode('db_driver')
                    ->validate()
                        ->ifNotInArray($supportedDrivers)
                        ->thenInvalid('The driver %s is not supported. Please choose one of '.json_encode($supportedDrivers))
                    ->end()
                    ->defaultValue('orm')
                    ->cannotBeOverwritten()
                    //->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('cronjob_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('model_manager_name')->defaultNull()->end()
                ->scalarNode('crontab_manager')->defaultValue('agentsib_crontab.manager.default')->end()
                ->arrayNode('jobs')
                ->useAttributeAsKey('job_id')
                ->prototype('array')
                    ->children()
                        ->scalarNode('expression')->example('0 1 * * *')->defaultNull()->end()
                        ->scalarNode('command')->example('symfony:command')->isRequired()->end()
                        ->arrayNode('arguments')->example('["--test", "test2"]')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->integerNode('execute_timeout')->min(0)->max(3600)->defaultValue(60)->end()
                        ->scalarNode('log_file')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
