<?php
/**
 * This file is a part of "comely-io/db-orm" package.
 * https://github.com/comely-io/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/db-orm/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Database\Schema\Table\Constraints;

/**
 * Class AbstractTableConstraint
 * @package Comely\Database\Schema\Table\Constraints
 * @property-read string $name
 */
abstract class AbstractTableConstraint
{
    /** @var string */
    protected $name;

    /**
     * AbstractTableConstraint constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param $prop
     * @return bool|string
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "name":
                return $this->name;
        }

        return false;
    }

    /**
     * @param $name
     * @param $arguments
     * @return string|null
     */
    public function __call($name, $arguments)
    {
        switch ($name) {
            case "getConstraintSQL":
                return $this->constraintSQL(strval($arguments[0] ?? ""));
        }

        throw new \DomainException('Cannot call inaccessible method');
    }

    /**
     * @param string $driver
     * @return string|null
     */
    abstract protected function constraintSQL(string $driver): ?string;
}
