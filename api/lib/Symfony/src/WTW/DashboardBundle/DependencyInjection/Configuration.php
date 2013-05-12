<?php

namespace WTW\DashboardBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wtw_dashboard');

        $rootNode
            ->children()
                ->booleanNode('offline_mode')
                    ->info('Prevents network-dependent installation of packages on cache warm-up')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
