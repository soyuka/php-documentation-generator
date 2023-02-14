<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator;

class ReflectionNamedType extends \ReflectionNamedType
{
    private string $_name;

    public function __construct(string $name)
    {
        $this->_name = $name;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function isBuiltin(): bool
    {
        return true;
    }

    public function __toString()
    {
        return $this->_name;
    }
}
