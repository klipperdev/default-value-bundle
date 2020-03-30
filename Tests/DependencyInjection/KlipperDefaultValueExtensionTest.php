<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\DefaultValueBundle\Tests\DependencyInjection;

use Klipper\Bundle\DefaultValueBundle\DependencyInjection\KlipperDefaultValueExtension;
use Klipper\Bundle\DefaultValueBundle\KlipperDefaultValueBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Bundle Extension Tests.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @internal
 */
final class KlipperDefaultValueExtensionTest extends TestCase
{
    public function testCompileContainerWithExtension(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->hasDefinition('klipper_default_value.extension'));
        $this->assertTrue($container->hasDefinition('klipper_default_value.registry'));
        $this->assertTrue($container->hasDefinition('klipper_default_value.resolved_type_factory'));
    }

    public function testCompileContainerWithoutExtension(): void
    {
        $container = $this->getContainer(true);
        $this->assertFalse($container->hasDefinition('klipper_default_value.extension'));
        $this->assertFalse($container->hasDefinition('klipper_default_value.registry'));
        $this->assertFalse($container->hasDefinition('klipper_default_value.resolved_type_factory'));
    }

    public function testLoadExtensionWithoutClassname(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The service id "test.klipper_default_value.type.invalid" must an instance of "Klipper\\Component\\DefaultValue\\ObjectTypeInterface"');

        $this->getContainer(false, 'container_exception');
    }

    public function testLoadDefaultExtensionWithClassname(): void
    {
        $container = $this->getContainer(false, 'container_extension');
        $this->assertTrue($container->hasDefinition('test.klipper_default_value.type.default'));
    }

    public function testLoadDefaultExtensionWithoutClassname(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The service id "test.klipper_default_value.type.default" must have the "class" parameter in the "klipper_default_value.type_extension');

        $this->getContainer(false, 'container_extension_exception');
    }

    public function testLoadDefaultTypeWithSimpleType(): void
    {
        $container = $this->getContainer(false, 'container_custom_simple');
        $this->assertTrue($container->hasDefinition('test.klipper_default_value.type.simple'));
    }

    public function testLoadDefaultTypeWithCustomConstructor(): void
    {
        $container = $this->getContainer(false, 'container_custom');
        $this->assertTrue($container->hasDefinition('test.klipper_default_value.type.custom'));
    }

    public function testLoadDefaultTypeWithCustomConstructorAndResolveTarget(): void
    {
        $container = $this->getContainer(false, 'container_custom_resolve_target', [
            'Foo\BarInterface' => 'Foo\Bar',
        ]);
        $this->assertTrue($container->hasDefinition('test.klipper_default_value.type.custom'));
    }

    public function testLoadDefaultTypeWithCustomConstructorWithoutClassname(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The service id "test.klipper_default_value.type.custom" must have the "class" parameter in the "klipper_default_value.type" tag.');

        $this->getContainer(false, 'container_custom_exception');
    }

    /**
     * Gets the container.
     *
     * @param bool   $empty          Compile container without extension
     * @param string $services       The services definition
     * @param array  $resolveTargets The doctrine resolve targets
     *
     * @return ContainerBuilder
     */
    protected function getContainer($empty = false, $services = null, array $resolveTargets = [])
    {
        $container = new ContainerBuilder();
        $bundle = new KlipperDefaultValueBundle();
        $bundle->build($container); // Attach all default factories

        if (!$empty) {
            $extension = new KlipperDefaultValueExtension();
            $container->registerExtension($extension);
            $config = [];
            $extension->load([$config], $container);
        }

        if (!empty($resolveTargets)) {
            $resolveDef = new Definition(\stdClass::class);
            $container->setDefinition('doctrine.orm.listeners.resolve_target_entity', $resolveDef);

            foreach ($resolveTargets as $class => $target) {
                $resolveDef->addMethodCall('addResolveTargetEntity', [$class, $target]);
            }
        }

        if (null !== $services) {
            $load = new XmlFileLoader($container, new FileLocator(__DIR__.'/Fixtures/Resources/config'));
            $load->load($services.'.xml');
        }

        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
