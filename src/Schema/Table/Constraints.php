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

namespace Comely\Database\Schema\Table;

use Comely\Database\Schema\Table\Constraints\AbstractTableConstraint;
use Comely\Database\Schema\Table\Constraints\ForeignKeyConstraint;
use Comely\Database\Schema\Table\Constraints\UniqueKeyConstraint;

/**
 * Class Constraints
 * @package Comely\Database\Schema\Table
 */
class Constraints implements \Countable, \Iterator
{
    /** @var array */
    private $constraints;
    /** @var int */
    private $count;

    /**
     * Constraints constructor.
     */
    public function __construct()
    {
        $this->constraints = [];
        $this->count = 0;
    }

    /**
     * @param string $key
     * @return UniqueKeyConstraint
     */
    public function uniqueKey(string $key): UniqueKeyConstraint
    {
        $constraint = new UniqueKeyConstraint($key);
        $this->constraints[$key] = $constraint;
        $this->count++;
        return $constraint;
    }

    /**
     * @param string $key
     * @return ForeignKeyConstraint
     */
    public function foreignKey(string $key): ForeignKeyConstraint
    {
        $constraint = new ForeignKeyConstraint($key);
        $this->constraints[$key] = $constraint;
        $this->count++;
        return $constraint;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        reset($this->constraints);
    }

    /**
     * @return AbstractTableConstraint
     */
    public function current(): AbstractTableConstraint
    {
        return current($this->constraints);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return key($this->constraints);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        next($this->constraints);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return is_null(key($this->constraints)) ? false : true;
    }
}
