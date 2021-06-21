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

namespace Comely\Database\Schema\Table;

use Comely\Database\Schema\Table\Columns\AbstractTableColumn;
use Comely\Database\Schema\Table\Columns\BinaryColumn;
use Comely\Database\Schema\Table\Columns\BlobColumn;
use Comely\Database\Schema\Table\Columns\DecimalColumn;
use Comely\Database\Schema\Table\Columns\DoubleColumn;
use Comely\Database\Schema\Table\Columns\EnumColumn;
use Comely\Database\Schema\Table\Columns\FloatColumn;
use Comely\Database\Schema\Table\Columns\IntegerColumn;
use Comely\Database\Schema\Table\Columns\StringColumn;
use Comely\Database\Schema\Table\Columns\TextColumn;

/**
 * Class Columns
 * @package Comely\Database\Schema\Table
 */
class Columns implements \Countable, \Iterator
{
    /** @var array */
    private array $columns = [];
    /** @var int */
    private int $count = 0;
    /** @var string */
    private string $defaultCharset = "utf8mb4";
    /** @var string */
    private string $defaultCollate = "utf8mb4_unicode_ci";
    /** @var null|string */
    private ?string $primaryKey = null;

    /**
     * @return array
     */
    public function names(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @param string|null $charset
     * @param string|null $collate
     * @return $this
     */
    public function defaults(?string $charset = null, ?string $collate = null): self
    {
        if (is_string($charset) && $charset) {
            $this->defaultCharset = $charset;
        }

        if (is_string($collate) && $collate) {
            $this->defaultCollate = $collate;
        }

        return $this;
    }

    /**
     * @param AbstractTableColumn $column
     */
    private function append(AbstractTableColumn $column): void
    {
        $this->columns[$column->name()] = $column;
        $this->count++;
    }

    /**
     * @param string $name
     * @return AbstractTableColumn|null
     */
    public function get(string $name): ?AbstractTableColumn
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @param string $name
     * @return IntegerColumn
     */
    public function int(string $name): IntegerColumn
    {
        $col = new IntegerColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return StringColumn
     */
    public function string(string $name): StringColumn
    {
        $col = new StringColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset)
            ->collation($this->defaultCollate);
    }

    /**
     * @param string $name
     * @return BinaryColumn
     */
    public function binary(string $name): BinaryColumn
    {
        $col = new BinaryColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return TextColumn
     */
    public function text(string $name): TextColumn
    {
        $col = new TextColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset)
            ->collation($this->defaultCollate);
    }

    /**
     * @param string $name
     * @return BlobColumn
     */
    public function blob(string $name): BlobColumn
    {
        $col = new BlobColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return DecimalColumn
     */
    public function decimal(string $name): DecimalColumn
    {
        $col = new DecimalColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return FloatColumn
     */
    public function float(string $name): FloatColumn
    {
        $col = new FloatColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return DoubleColumn
     */
    public function double(string $name): DoubleColumn
    {
        $col = new DoubleColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return EnumColumn
     */
    public function enum(string $name): EnumColumn
    {
        $col = new EnumColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $col
     */
    public function primaryKey(string $col): void
    {
        /** @var AbstractTableColumn $column */
        $column = $this->columns[$col] ?? null;
        if (!$column) {
            throw new \InvalidArgumentException(sprintf('Column "%s" not defined in table', $col));
        }

        if ($column->isNullable) {
            throw new \InvalidArgumentException(sprintf('Primary key "%s" cannot be nullable', $col));
        }

        if (is_null($column->defaultValue)) {
            if (!$column instanceof IntegerColumn || !$column->autoIncrement) {
                throw new \InvalidArgumentException(sprintf('Primary key "%s" default value cannot be NULL', $col));
            }
        }

        $this->primaryKey = $col;
    }

    /**
     * @return string|null
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        reset($this->columns);
    }

    /**
     * @return AbstractTableColumn
     */
    public function current(): AbstractTableColumn
    {
        return current($this->columns);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return key($this->columns);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        next($this->columns);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return !is_null(key($this->columns));
    }
}
