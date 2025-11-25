<?php
declare(strict_types=1);

namespace CtwTest\Middleware\ResponseTimeMiddleware;

use Ctw\Middleware\ResponseTimeMiddleware\ConfigProvider;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddlewareFactory;

final class ConfigProviderTest extends AbstractCase
{
    /**
     * Test that config provider returns correct structure
     */
    public function testConfigProvider(): void
    {
        $configProvider = new ConfigProvider();

        $expected = [
            'dependencies' => [
                'factories' => [
                    ResponseTimeMiddleware::class => ResponseTimeMiddlewareFactory::class,
                ],
            ],
        ];

        self::assertSame($expected, $configProvider->__invoke());
    }

    /**
     * Test that invoke returns array with dependencies key
     */
    public function testInvokeReturnsDependenciesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();

        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that dependencies contains factories key
     */
    public function testDependenciesContainsFactoriesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that getDependencies returns array with factories
     */
    public function testGetDependenciesReturnsFactories(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that middleware class is registered in factories
     */
    public function testMiddlewareClassIsRegisteredInFactories(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertArrayHasKey(ResponseTimeMiddleware::class, $factories);
    }

    /**
     * Test that factory class is correctly mapped
     */
    public function testFactoryClassIsCorrectlyMapped(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertSame(ResponseTimeMiddlewareFactory::class, $factories[ResponseTimeMiddleware::class]);
    }

    /**
     * Test that config provider can be instantiated
     */
    public function testConfigProviderCanBeInstantiated(): void
    {
        $configProvider = new ConfigProvider();

        // @phpstan-ignore-next-line
        self::assertInstanceOf(ConfigProvider::class, $configProvider);
    }

    /**
     * Test that getDependencies is consistent with invoke result
     */
    public function testGetDependenciesIsConsistentWithInvoke(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertSame($configProvider->getDependencies(), $dependencies);
    }
}
