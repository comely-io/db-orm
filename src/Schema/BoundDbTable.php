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

namespace Comely\Database\Schema;

use Comely\Database\Database;
use Comely\Database\Exception\ORM_Exception;
use Comely\Database\Schema\Table\Columns\AbstractTableColumn;

/**
 * Class BoundDbTable
 * @package Comely\Database\Schema
 */
class BoundDbTable implements \Serializable
{
    /** @var Database */
    private $db;
    /** @var AbstractDbTable */
    private $table;

    /**
     * BoundDbTable constructor.
     * @param Database $db
     * @param AbstractDbTable $table
     */
    public function __construct(Database $db, AbstractDbTable $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @return Database
     */
    public function db(): Database
    {
        return $this->db;
    }

    /**
     * @return AbstractDbTable
     */
    public function table(): AbstractDbTable
    {
        return $this->table;
    }

    /**
     * @param string $col
     * @return AbstractTableColumn
     * @throws ORM_Exception
     */
    public function col(string $col): AbstractTableColumn
    {
        $column = $this->table->columns()->get($col);
        if (!$column) {
            throw new ORM_Exception(sprintf('Column "%s" not found in "%s" table', $col, $this->table->name));
        }

        return $column;
    }

    /**
     * @param AbstractTableColumn $col
     * @param $value
     * @throws ORM_Exception
     */
    public function validateColumnValueType(AbstractTableColumn $col, $value): void
    {
        if (is_null($value)) {
            if (!$col->nullable) {
                throw new ORM_Exception(sprintf('Column "%s.%s" cannot be NULL', $this->table()->name, $col->name));
            }
        } else {
            if (!is_scalar($value) || gettype($value) !== $col->dataType) {
                throw new ORM_Exception(
                    sprintf(
                        'Column "%s.%s" expects value of type "%s", got "%s"',
                        $this->table()->name,
                        $col->name,
                        $col->dataType,
                        gettype($value)
                    )
                );
            }
        }
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "db" => sprintf('%s@%s', $this->db->credentials()->host, $this->db->credentials()->name),
            "table" => $this->table->name
        ];
    }

    /**
     * @return void
     */
    public function __clone()
    {
        throw new \DomainException('Instance of Schema\BoundDbTable cannot be cloned');
    }

    /**
     * @return string|void
     */
    public function serialize()
    {
        throw new \DomainException('Instance of Schema\BoundDbTable cannot be serialized');
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        throw new \DomainException('Instance of Schema\BoundDbTable cannot be serialized');
    }
}
