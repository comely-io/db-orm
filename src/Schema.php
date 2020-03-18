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

namespace Comely\Database;

use Comely\Database\Exception\SchemaTableException;
use Comely\Database\Schema\AbstractDbTable;
use Comely\Database\Schema\BoundDbTable;
use Comely\Database\Schema\Events;
use Comely\Database\Schema\Migration;
use Comely\Utils\OOP\OOP;

/**
 * Class Schema
 * @package Comely\Database
 */
class Schema implements ConstantsInterface
{
    /** @var array */
    private static $tables = [];
    /** @var array */
    private static $index = [];
    /** @var null|Events */
    private static $events;

    /**
     * @param Database $db
     * @param string $table
     */
    public static function Bind(Database $db, string $table): void
    {
        if (!OOP::isValidClass($table)) {
            throw new \InvalidArgumentException('Table class does not exist');
        } elseif (!is_subclass_of($table, 'Comely\Database\Schema\AbstractDbTable', true)) {
            throw new \InvalidArgumentException(
                sprintf('Table class "%s" is not subclass of "Schema\AbstractTable"', $table)
            );
        }

        /** @var AbstractDbTable $dbTable */
        $dbTable = new $table();
        $boundTable = new BoundDbTable($db, $dbTable);
        static::$tables[$dbTable->name] = $boundTable;
        static::$index[get_class($dbTable)] = $dbTable->name;
    }

    /**
     * @param string $nameOrClassName
     * @return BoundDbTable
     * @throws SchemaTableException
     */
    public static function Table(string $nameOrClassName): BoundDbTable
    {
        $boundTable = static::$tables[$nameOrClassName] ?? static::$tables[static::$index[$nameOrClassName] ?? ""] ?? null;
        if (!$boundTable) {
            throw new SchemaTableException(sprintf('Table "%s" is not bound with database', $nameOrClassName));
        }

        return $boundTable;
    }

    /**
     * @param string $tableNameOrClassName
     * @return Migration
     * @throws SchemaTableException
     */
    public static function Migration(string $tableNameOrClassName): Migration
    {
        return new Migration(self::Table($tableNameOrClassName));
    }

    /**
     * @return Events
     */
    public static function Events(): Events
    {
        if (!static::$events) {
            static::$events = new Events();
        }

        return static::$events;
    }
}
