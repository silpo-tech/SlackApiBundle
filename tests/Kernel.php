<?php

declare(strict_types=1);

namespace SlackApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    use MicroKernelTrait;

    /** @var string[] */
    protected iterable $testBundle = [];

    /** @var string[]|callable[] */
    protected iterable $testConfigs = [];

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        $this->addTestBundle(FrameworkBundle::class);
        $this->addTestConfig(__DIR__ . '/Resources/config/framework.yaml');
    }

    public function addTestBundle(string $bundleClassName): void
    {
        $this->testBundle[] = $bundleClassName;
    }

    public function addTestConfig(string|callable $config): void
    {
        $this->testConfigs[] = $config;
    }

    public function getConfigDir(): string
    {
        return $this->getProjectDir() . '/src/Resources/config';
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../var/cache/' . $this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../var/log';
    }

    protected function configureContainer(ContainerConfigurator $container, $loader): void
    {
        foreach ($this->testConfigs as $config) {
            $loader->load($config);
        }
    }

    public function registerBundles(): iterable
    {
        $this->testBundle = array_unique($this->testBundle);

        foreach ($this->testBundle as $bundle) {
            yield new $bundle();
        }
    }

    public function handleOptions(array $options): void
    {
        if (array_key_exists('config', $options) && is_callable($configCallable = $options['config'])) {
            $configCallable($this);
        }
    }

    public function shutdown(): void
    {
        parent::shutdown();

        $cacheDirectory = $this->getCacheDir();
        $logDirectory = $this->getLogDir();

        $filesystem = new Filesystem();

        if ($filesystem->exists($cacheDirectory)) {
            $filesystem->remove($cacheDirectory);
        }

        if ($filesystem->exists($logDirectory)) {
            $filesystem->remove($logDirectory);
        }
    }
}
