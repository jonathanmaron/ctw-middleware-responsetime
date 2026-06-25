<?php
declare(strict_types=1);

namespace CtwTest\Middleware\ResponseTimeMiddleware;

use Ctw\Middleware\ResponseTimeMiddleware\ConfigProvider;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddleware;
use Ctw\Middleware\ResponseTimeMiddleware\ResponseTimeMiddlewareFactory;

final class ConfigProviderTest extends AbstractCase
{
    /**
     * Test that __invoke returns the complete dependency configuration structure when called.
     */
    public function testInvokeReturnsCompleteConfigurationStructure(): void
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
     * Test that __invoke returns an array containing the 'dependencies' key when called.
     */
    public function testInvokeReturnsArrayWithDependenciesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();

        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that the dependencies array contains the 'factories' key when produced by __invoke.
     */
    public function testInvokeDependenciesContainFactoriesKey(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that getDependencies returns an array containing the 'factories' key when called.
     */
    public function testGetDependenciesReturnsArrayWithFactoriesKey(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that the middleware class is registered as a factory key when getDependencies is called.
     */
    public function testGetDependenciesRegistersMiddlewareInFactories(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertArrayHasKey(ResponseTimeMiddleware::class, $factories);
    }

    /**
     * Test that the middleware class maps to its factory class when getDependencies is called.
     */
    public function testGetDependenciesMapsMiddlewareToFactory(): void
    {
        $configProvider = new ConfigProvider();
        $dependencies   = $configProvider->getDependencies();
        $factories      = $dependencies['factories'];
        assert(is_array($factories));

        self::assertSame(ResponseTimeMiddlewareFactory::class, $factories[ResponseTimeMiddleware::class]);
    }

    /**
     * Test that getDependencies returns the same data as the 'dependencies' entry of __invoke.
     */
    public function testGetDependenciesMatchesInvokeDependencies(): void
    {
        $configProvider = new ConfigProvider();
        $config         = $configProvider();
        $dependencies   = $config['dependencies'];
        assert(is_array($dependencies));

        self::assertSame($configProvider->getDependencies(), $dependencies);
    }
}
