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
                ->scalarNode('base_url')->defaultValue('')->end()
                ->scalarNode('api_key')->defaultValue('')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
