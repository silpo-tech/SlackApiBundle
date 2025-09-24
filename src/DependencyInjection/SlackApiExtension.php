<?php

declare(strict_types=1);

namespace SlackApiBundle\DependencyInjection;

use JoliCode\Slack\Client;
use JoliCode\Slack\ClientFactory;
use SlackApiBundle\SlackApi;
use SlackApiBundle\SlackApiOptions;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class SlackApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $token = $config['token'] ?? [];

        $container->setParameter('slackapi.token', $token);
        $container->setDefinition(SlackApiOptions::class, (new Definition(SlackApiOptions::class))
            ->setPublic(true)
            ->setFactory([SlackApiOptions::class, 'create'])
            ->addArgument($config['options']));
        $container->setDefinition(SlackApi::class, (new Definition(SlackApi::class))
            ->setPublic(true)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addArgument(new Reference(Client::class))
            ->addArgument(new Reference(SlackApiOptions::class)));
        $container->setDefinition(Client::class, (new Definition(Client::class))
            ->setPublic(true)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setFactory([ClientFactory::class, 'create'])
            ->addArgument($token));
    }
}
