<?php

declare(strict_types=1);

namespace SlackApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('slack_api');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('token')->end()
                ->arrayNode('options')
                    ->children()
                        ->booleanNode('allow_private_channels')
                            ->info("This option will adds private_channels scope to conversationList request")
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('allow_public_channels')
                            ->info("This option will adds public_channels scope to conversationList request")
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
