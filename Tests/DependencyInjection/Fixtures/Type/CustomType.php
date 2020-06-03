<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\DefaultValueBundle\Tests\DependencyInjection\Fixtures\Type;

use Klipper\Component\DefaultValue\AbstractSimpleType;

/**
 * Invalid default value type.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class CustomType extends AbstractSimpleType
{
    protected bool $foo;

    public function __construct(string $class, bool $foo)
    {
        parent::__construct($class);

        $this->foo = $foo;
    }
}
