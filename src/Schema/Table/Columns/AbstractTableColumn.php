<?php
/*
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

/**
 * Class AbstractTableColumn
 * @package Comely\Database\Schema\Table\Columns
 * @property-read string $colName
 * @property-read bool $isNullable
 * @property-read null|string|int|float $defaultValue
 * @property-read array $attrs
 */
abstract class AbstractTableColumn
{
    /** @var string */
    protected const DATATYPE = null;

    /** @var string */
    protected string $name;
    /** @var int|float|string|null */
    private int|float|string|null $default = null;
    /** @var bool */
    private bool $nullable = false;
    /** @var array */
    protected array $attributes = [];

    /**
     * AbstractTableColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function __get($prop)
    {
        return match ($prop) {
            "isNullable" => $this->nullable,
            "attrs" => $this->attributes,
            "defaultValue" => $this->default,
            default => false,
        };
    }

    /**
     * @return $this
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return static::DATATYPE;
    }

    /**
     * @param int|string|float|null $value
     * @return $this
     */
    protected function setDefaultValue(null|int|string|float $value): self
    {
        if (is_null($value) && !$this->nullable) {
            throw new \InvalidArgumentException(
                sprintf('Default value for col "%s" cannot be NULL; Column is not nullable', $this->name)
            );
        }

        $this->default = $value;
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return string|null
     */
    public function __call($name, $arguments)
    {
        if ($name == "getColumnSQL") {
            return $this->columnSQL(strval($arguments[0] ?? ""));
        }

        throw new \DomainException('Cannot call inaccessible method');
    }

    /**
     * @param string $driver
     * @return null|string
     */
    abstract protected function columnSQL(string $driver): ?string;
}
