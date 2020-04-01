<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\DefaultValueBundle\DependencyInjection\Compiler;

use Klipper\Component\DefaultValue\AbstractSimpleType;
use Klipper\Component\DefaultValue\ObjectTypeExtensionInterface;
use Klipper\Component\DefaultValue\ObjectTypeInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Adds all services with the tags "klipper_default_value.type" as arguments of
 * the "klipper_default_value.extension" service.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DefaultValuePass implements CompilerPassInterface
{
    /**
     * @var null|array
     */
    private $resolveTargets;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('klipper_default_value.extension')) {
            return;
        }

        // Builds an array with service IDs as keys and tag class names as values
        $this->findTags($container, 'klipper_default_value.type', 0);
        $this->findTags($container, 'klipper_default_value.type_extension', 1, true);
    }

    /**
     * Find service tags.
     *
     * @param string $tagName
     * @param int    $argumentPosition
     * @param bool   $ext
     *
     * @throws InvalidConfigurationException
     */
    protected function findTags(ContainerBuilder $container, $tagName, $argumentPosition, $ext = false): void
    {
        $services = [];

        foreach ($container->findTaggedServiceIds($tagName) as $serviceId => $tag) {
            $class = isset($tag[0]['class'])
                ? $this->getRealClassName($container, $tag[0]['class'])
                : $this->getClassName($container, $serviceId, $tagName);
            $class = $this->findResolveTarget($container, $class);
            $this->replaceResolveTargetClass($container, $tagName, $serviceId, $class);

            // Flip, because we want tag classe names (= type identifiers) as keys
            if ($ext) {
                $services[$class][] = $serviceId;
            } else {
                $services[$class] = $serviceId;
            }
        }

        $container->getDefinition('klipper_default_value.extension')->replaceArgument($argumentPosition, $services);
    }

    /**
     * Get the real class name.
     *
     * @param ContainerBuilder $container The container
     * @param string           $classname The class name or the parameter name of classname
     *
     * @return string
     */
    protected function getRealClassName(ContainerBuilder $container, $classname)
    {
        return 0 === strpos($classname, '%') ? $container->getParameter(trim($classname, '%')) : $classname;
    }

    /**
     * Get the class name of default value type.
     *
     * @param ContainerBuilder $container The container service
     * @param string           $serviceId The service id of default value type
     * @param string           $tagName   The tag name
     *
     * @throws InvalidConfigurationException When the service is not an instance of Klipper\Component\DefaultValue\ObjectTypeInterface
     *
     * @return string
     */
    protected function getClassName(ContainerBuilder $container, $serviceId, $tagName)
    {
        $type = $container->getDefinition($serviceId);
        $interfaces = class_implements($type->getClass());

        if (\in_array(ObjectTypeExtensionInterface::class, $interfaces, true)) {
            throw new InvalidConfigurationException(sprintf('The service id "%s" must have the "class" parameter in the "%s" tag.', $serviceId, $tagName));
        }
        if (!\in_array(ObjectTypeInterface::class, $interfaces, true)) {
            throw new InvalidConfigurationException(sprintf('The service id "%s" must an instance of "%s"', $serviceId, 'Klipper\Component\DefaultValue\ObjectTypeInterface'));
        }

        return $this->buildInstanceType($type, $serviceId, $tagName)->getClass();
    }

    /**
     * Build the simple default type instance.
     *
     * @param Definition $type      The definition of default value type
     * @param string     $serviceId The service id of default value type
     * @param string     $tagName   The tag name
     *
     * @return ObjectTypeInterface
     */
    protected function buildInstanceType(Definition $type, $serviceId, $tagName)
    {
        $parents = class_parents($type->getClass());
        $args = $type->getArguments();
        $ref = new \ReflectionClass($type);

        if (\in_array(AbstractSimpleType::class, $parents, true)
                && (0 === \count($args) || (1 === \count($args) && \is_string($args[0])))) {
            return $ref->newInstanceArgs($args);
        }

        throw new InvalidConfigurationException(sprintf('The service id "%s" must have the "class" parameter in the "%s" tag.', $serviceId, $tagName));
    }

    /**
     * Find the resolve target of class.
     *
     * @param ContainerBuilder $container The container
     * @param string           $class     The class name
     *
     * @return string
     */
    private function findResolveTarget(ContainerBuilder $container, $class)
    {
        $resolveTargets = $this->getResolveTargets($container);

        if (isset($resolveTargets[$class])) {
            $class = $resolveTargets[$class];
        }

        return $class;
    }

    /**
     * Get the resolve target classes.
     *
     * @param ContainerBuilder $container The container
     *
     * @return array
     */
    private function getResolveTargets(ContainerBuilder $container)
    {
        if (null === $this->resolveTargets) {
            $this->resolveTargets = [];

            if ($container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
                $def = $container->getDefinition('doctrine.orm.listeners.resolve_target_entity');

                foreach ($def->getMethodCalls() as $call) {
                    if ('addResolveTargetEntity' === $call[0]) {
                        $this->resolveTargets[$call[1][0]] = $call[1][1];
                    }
                }
            }
        }

        return $this->resolveTargets;
    }

    /**
     * Replace the resolve target class.
     *
     * @param ContainerBuilder $container The container service
     * @param string           $tagName   The tag name
     * @param string           $serviceId The service id of default value type
     * @param string           $class     The class name
     */
    private function replaceResolveTargetClass(ContainerBuilder $container, $tagName, $serviceId, $class): void
    {
        $def = $container->getDefinition($serviceId);

        $this->replaceClassInArguments($container, $def, $class);
        $this->replaceClassInTags($def, $tagName, $class);
    }

    /**
     * Replace the resolve target class in the arguments of default value service.
     *
     * @param Definition $definition The service definition of default value
     * @param string     $tagName    The tag name
     * @param string     $class      The class name
     */
    private function replaceClassInTags(Definition $definition, $tagName, $class): void
    {
        $tags = $definition->getTag($tagName);

        $definition->clearTag($tagName);

        foreach ($tags as &$tag) {
            if (isset($tag['class'])) {
                $tag['class'] = $class;
            }

            $definition->addTag($tagName, $tag);
        }
    }

    /**
     * Replace the resolve target class in the arguments of default value service.
     *
     * @param ContainerBuilder $container  The container service
     * @param Definition       $definition The service definition of default value
     * @param string           $class      The class name
     */
    private function replaceClassInArguments(ContainerBuilder $container, Definition $definition, $class): void
    {
        $targets = $this->getResolveTargets($container);
        $args = $definition->getArguments();

        foreach ($args as &$arg) {
            if (\is_string($arg) && isset($targets[$arg])) {
                $arg = $class;
            }
        }

        $definition->setArguments($args);
    }
}
