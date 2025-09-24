<?php

declare(strict_types=1);

namespace SlackApiBundle\Tests\TestCase\Integration\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use SlackApiBundle\SlackApiBundle;
use SlackApiBundle\SlackApiOptions;
use SlackApiBundle\Tests\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigurationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var Kernel $kernel
         */
        $kernel = parent::createKernel($options);

        $kernel->addTestBundle(SlackApiBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    #[DataProvider('okDataProvider')]
    public function testOk(array $configs, array $bundles, array $expected): void
    {
        $kernel = static::bootKernel(['config' => static function (Kernel $kernel) use ($configs, $bundles) {
            foreach ($bundles as $bundle) {
                $kernel->addTestBundle($bundle);
            }
            foreach ($configs as $config) {
                $kernel->addTestConfig($config);
            }
        }]);

        /** @var Container $container */
        $container = $kernel->getContainer();
        /** @var SlackApiOptions $options */
        $options = $container->get(SlackApiOptions::class);

        $this->assertEquals($expected['token'], $container->getParameter('slackapi.token'));
        $this->assertEquals($expected['options']['allowPrivateChannels'], $options->allowPrivateChannels);
        $this->assertEquals($expected['options']['allowPublicChannels'], $options->allowPublicChannels);
    }

    public static function okDataProvider(): iterable
    {
        yield 'config options' => [
            'configs' => [
                __DIR__.'/../../../Resources/config/packages/slack_api.yaml',
            ],
            'bundles' => [SlackApiBundle::class],
            'expected' => [
                'token' => 'test-token',
                'options' => [
                    'allowPrivateChannels' => false,
                    'allowPublicChannels' => true,
                ],
            ],
        ];
    }
}
