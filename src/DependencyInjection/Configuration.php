<?php

namespace Medelse\DimplBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('medelse_dimpl');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('base_url')->end()
                ->scalarNode('api_key')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
