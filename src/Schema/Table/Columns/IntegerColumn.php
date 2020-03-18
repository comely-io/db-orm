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

namespace Comely\Database\Schema\Table\Columns;

use Comely\Database\Schema\Table\Traits\NumericValueTrait;
use Comely\Database\Schema\Table\Traits\UniqueColumnTrait;

/**
 * Class IntegerColumn
 * @package Comely\Database\Schema\Table\Columns
 * @property-read bool $autoIncrement
 */
class IntegerColumn extends AbstractTableColumn
{
    /** @var int */
    private $size;
    /** @var bool */
    private $autoIncrement;

    use NumericValueTrait;
    use UniqueColumnTrait;

    /**
     * IntegerColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->dataType = "integer";
        $this->attributes["unsigned"] = 0;
        $this->size = 4; // Default 4 byte integer
        $this->autoIncrement = false;
    }

    /**
     * @param $prop
     * @return bool|mixed
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "autoIncrement":
                return $this->autoIncrement;
        }

        return parent::__get($prop);
    }

    /**
     * @param int $byte
     * @return IntegerColumn
     */
    public function size(int $byte): self
    {
        if (!in_array($byte, [1, 2, 3, 4, 8])) {
            throw new \OutOfBoundsException('Invalid integer size');
        }

        $this->size = $byte;
        return $this;
    }

    /**
     * @param int $byte
     * @return IntegerColumn
     */
    public function bytes(int $byte): self
    {
        return $this->size($byte);
    }

    /**
     * @param int $value
     * @return IntegerColumn
     */
    public function default(int $value): self
    {
        if ($value < 0 && $this->attributes["unsigned"] === 1) {
            throw new \InvalidArgumentException('Cannot set signed integer as default value');
        }

        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @return IntegerColumn
     */
    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        switch ($driver) {
            case "mysql":
                switch ($this->size) {
                    case 1:
                        return "tinyint";
                    case 2:
                        return "smallint";
                    case 3:
                        return "mediumint";
                    case 8:
                        return "bigint";
                    default:
                        return "int";
                }
            case "sqlite":
            default:
                return "integer";
        }
    }
}
