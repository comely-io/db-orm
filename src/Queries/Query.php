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

namespace Comely\Database\Queries;

use Comely\Database\Database;
use Comely\Database\Exception\QueryExecuteException;
use Comely\Database\Exception\QueryBuildException;
use Comely\Database\Exception\QueryNotSuccessException;
use Comely\Database\Server\PdoError;

/**
 * Class Query
 * @package Comely\Database\Queries
 */
class Query
{
    public const FETCH = 0x0a;
    public const EXEC = 0x14;

    /** @var int */
    private $type;
    /** @var string */
    private $query;
    /** @var array */
    private $data;
    /** @var bool */
    private $executed;
    /** @var int */
    private $rows;
    /** @var null|PdoError */
    private $error;

    /**
     * @param string $query
     * @param array|null $data
     * @return Query
     * @throws QueryBuildException
     */
    public static function fetchQuery(string $query, ?array $data = null): self
    {
        return new self(self::FETCH, $query, $data);
    }

    /**
     * @param string $query
     * @param array|null $data
     * @return Query
     * @throws QueryBuildException
     */
    public static function execQuery(string $query, ?array $data = null): self
    {
        return new self(self::EXEC, $query, $data);
    }

    /**
     * Query constructor.
     * @param int $type
     * @param string $query
     * @param array|null $data
     * @throws QueryBuildException
     */
    public function __construct(int $type, string $query, ?array $data = null)
    {
        if (!in_array($type, [self::FETCH, self::EXEC])) {
            throw new \OutOfBoundsException('Invalid query type');
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!is_scalar($value) && !is_null($value)) {
                    throw new QueryBuildException(sprintf('Query data contains an illegal value of type "%s"', gettype($value)));
                }
            }

            $this->data = $data;
        }

        $this->type = $type;
        $this->query = $query;
        $this->data = $this->data ?? [];
        $this->executed = false;
        $this->rows = 0;
        $this->error = null;
    }

    /**
     * @return string
     */
    public function query(): string
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function executed(): bool
    {
        return $this->executed;
    }

    /**
     * @return int
     */
    public function rows(): int
    {
        return $this->rows;
    }

    /**
     * @return PdoError|null
     */
    public function error(): ?PdoError
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function isFetchQuery(): bool
    {
        return $this->type === self::FETCH ? true : false;
    }

    /**
     * @param bool $expectPositiveRowCount
     * @return bool
     */
    public function isSuccess(bool $expectPositiveRowCount = true): bool
    {
        if (!$this->executed) {
            return false;
        }

        if (!$this->error) {
            $expectedRowsAbove = $expectPositiveRowCount ? 1 : 0;
            if ($this->rows >= $expectedRowsAbove) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $expectPositiveRowCount
     * @throws QueryNotSuccessException
     */
    public function checkSuccess(bool $expectPositiveRowCount = true): void
    {
        if (!$this->executed) {
            throw QueryNotSuccessException::NotExecuted();
        }

        if ($this->error) {
            throw QueryNotSuccessException::HasError();
        }

        $expectedRowsAbove = $expectPositiveRowCount ? 1 : 0;
        if ($expectedRowsAbove > $this->rows) {
            throw QueryNotSuccessException::RowCount($expectedRowsAbove, $this->rows);
        }
    }

    /**
     * @param Database $db
     * @return array|int
     * @throws QueryExecuteException
     */
    public function execute(Database $db)
    {
        // Append into Queries
        $db->queries()->append($this);

        // Mark as executed
        $this->executed = true;

        try {
            // Prepare statement
            $stmnt = $db->pdo()->prepare($this->query);
            if (!$stmnt) {
                $this->error = $db->error();
            }
        } catch (\PDOException $e) {
            $this->error = $db->error();
            throw new QueryExecuteException($this, $e->getMessage());
        }

        // Execute query
        try {
            // Bind params
            foreach ($this->data as $key => $value) {
                if (is_int($key)) {
                    $key++; // Indexed arrays get +1 to numeric keys so they don't start with 0
                }

                $stmnt->bindValue($key, $value, $this->valueDataType($value));
            }

            // Execute
            $exec = $stmnt->execute();
            if (!$exec || $stmnt->errorCode() !== "00000") {
                $this->error = new PdoError($stmnt->errorInfo());
                throw new QueryExecuteException($this, 'Failed to execute DB query');
            }

            if ($this->type === self::FETCH) { // Fetch Query
                $rows = $stmnt->fetchAll(\PDO::FETCH_ASSOC);
                if (!is_array($rows)) {
                    throw new QueryExecuteException($this, 'Failed to fetch rows from executed query');
                }

                $this->rows = $stmnt->rowCount();
                return $rows;
            }

            $this->rows = $stmnt->rowCount();
            return $this->rows;
        } catch (\PDOException $e) {
            $stmtError = new PdoError($stmnt->errorInfo());
            if ($stmtError->info || $stmtError->code) {
                $this->error = $stmtError;
            }

            throw new QueryExecuteException($this, $e->getMessage());
        }
    }

    /**
     * @param $value
     * @return int
     */
    private function valueDataType($value): int
    {
        $type = gettype($value);
        switch ($type) {
            case "boolean":
                return \PDO::PARAM_BOOL;
            case "integer":
                return \PDO::PARAM_INT;
            case "NULL":
                return \PDO::PARAM_NULL;
            default:
                return \PDO::PARAM_STR;
        }
    }
}
