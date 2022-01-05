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

namespace Comely\Database\Schema;

use Comely\Database\Schema;
use Comely\Database\Schema\ORM\FindQuery;
use Comely\Database\Schema\Table\Columns;
use Comely\Database\Schema\Table\Constraints;

/**
 * Class AbstractDbTable
 * @package Comely\Database\Schema
 * @property-read null|string $name
 * @property-read null|string $engine
 * @property-read null|string $ormClass
 */
abstract class AbstractDbTable
{
    /** @var string Table name */
    public const TABLE = null;
    /** @var string|null ORM models class */
    public const ORM_CLASS = null;
    /** @var string */
    public const ENGINE = "InnoDB";

    /** @var Columns */
    protected Columns $columns;
    /** @var Constraints */
    protected Constraints $constraints;
    /** @var string */
    protected string $name;
    /** @var string */
    protected string $engine;
    /** @var string|null */
    protected ?string $ormClass = null;

    /**
     * AbstractDbTable constructor.
     */
    final public function __construct()
    {
        $this->columns = new Columns();
        $this->constraints = new Constraints();

        // Get table names and engine
        $this->name = static::TABLE;
        if (!is_string($this->name) || !$this->name) {
            throw new \InvalidArgumentException(sprintf('Invalid TABLE const for table "%s"', static::class));
        }

        $this->engine = static::ENGINE;
        if (!is_string($this->engine) || !$this->engine) {
            throw new \InvalidArgumentException(sprintf('Invalid ENGINE const for table "%s"', static::class));
        }

        // Models class
        $this->ormClass = static::ORM_CLASS;
        if (!is_null($this->ormClass)) {
            if (!class_exists($this->ormClass)) {
                throw new \InvalidArgumentException(
                    sprintf('defined ORM_CLASS for table "%s" does not exist', static::class)
                );
            }

            try {
                $reflect = new \ReflectionClass($this->ormClass);
                $isValidORMClass = $reflect->isSubclassOf('Comely\Database\Schema\ORM\Abstract_ORM_Model');
            } catch (\Exception) {
            }

            if (!isset($isValidORMClass) || !$isValidORMClass) {
                throw new \InvalidArgumentException(
                    sprintf('define ORM_CLASS for table "%s" is not subclass of ORM', static::class)
                );
            }
        }

        // On Construct Callback
        $this->onConstruct();

        // Callback schema method for table structure
        $this->structure($this->columns, $this->constraints);
    }

    /**
     * @return void
     */
    abstract protected function onConstruct(): void;

    /**
     * @param Columns $cols
     * @param Constraints $constraints
     */
    abstract public function structure(Columns $cols, Constraints $constraints): void;

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "name":
            case "engine":
                return $this->$prop;
            case "ormClass":
                return $this->ormClass;
        }

        throw new \DomainException('Cannot get value of inaccessible property');
    }

    /**
     * @return Columns
     */
    public function columns(): Columns
    {
        return $this->columns;
    }

    /**
     * @return Constraints
     */
    public function constraints(): Constraints
    {
        return $this->constraints;
    }

    /**
     * @param array|null $match
     * @return FindQuery
     * @throws \Comely\Database\Exception\SchemaTableException
     */
    public static function Find(?array $match = null): FindQuery
    {
        $modelFindQuery = new FindQuery(Schema::Table(strval(static::TABLE)));
        return $match ? $modelFindQuery->match($match) : $modelFindQuery;
    }
}
