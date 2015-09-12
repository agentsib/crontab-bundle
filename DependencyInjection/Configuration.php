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
        $rootNode
            ->children()
                ->arrayNode('jobs')
                ->useAttributeAsKey('job_id')->cannotBeOverwritten()
                ->prototype('array')
                    ->children()
                        ->scalarNode('expression')->example('0 1 * * *')->isRequired()->end()
                        ->scalarNode('command')->example('symfony:command')->isRequired()->end()
                        ->arrayNode('arguments')->example('["--test", "test2"]')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->scalarNode('log_file')->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
