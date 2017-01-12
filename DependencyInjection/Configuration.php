<?php

namespace Prime\NavigationBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('prime_navigation');

        $rootNode
            ->children()
                ->scalarNode('template')
                    ->defaultValue('PrimeNavigationBundle:Navigation:simple.html.twig')
                ->end()
                ->scalarNode('breadcrumbs_template')
                    ->defaultValue('PrimeNavigationBundle:Navigation:breadcrumbs.html.twig')
                ->end()
            ->end();
        ;

        return $treeBuilder;
    }
}
