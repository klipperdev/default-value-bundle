<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\DefaultValueBundle\Tests\DependencyInjection\Fixtures\Extension;

use Klipper\Component\DefaultValue\AbstractTypeExtension;
use Klipper\Component\DefaultValue\ObjectBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default Value Extension.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DefaultExtension extends AbstractTypeExtension
{
    public function buildObject(ObjectBuilderInterface $builder, array $options): void
    {
    }

    public function finishObject(ObjectBuilderInterface $builder, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'test' => null,
        ]);

        $resolver->addAllowedTypes('test', ['null', 'string']);
    }

    public function getExtendedType(): string
    {
        return 'default';
    }
}
