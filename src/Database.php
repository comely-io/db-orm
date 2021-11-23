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

namespace Comely\Database;

use Comely\Database\Exception\QueryExecuteException;
use Comely\Database\Queries\DbFetch;
use Comely\Database\Queries\DbQueryExec;
use Comely\Database\Queries\QueryBuilder;
use Comely\Database\Server\DbCredentials;
use Comely\Database\Server\PdoAdapter;
use Comely\Database\Server\PdoError;
use Comely\Utils\OOP\Traits\NotCloneableTrait;
use Comely\Utils\OOP\Traits\NotSerializableTrait;

/**
 * Class Database
 * @package Comely\Database
 */
class Database extends PdoAdapter implements ConstantsInterface
{
    /** @var Queries */
    private Queries $queries;

    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * Database constructor.
     * @param DbCredentials $credentials
     * @throws Exception\DbConnectionException
     */
    public function __construct(DbCredentials $credentials)
    {
        parent::__construct($credentials);
        $this->queries = new Queries();
    }

    /**
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * @return Queries
     */
    public function queries(): Queries
    {
        return $this->queries;
    }

    /**
     * @param string $query
     * @param array $data
     * @param bool $throwOnFail
     * @return DbQueryExec
     * @throws QueryExecuteException
     */
    public function exec(string $query, array $data = [], bool $throwOnFail = true): DbQueryExec
    {
        return $this->queryExec($query, $data, $throwOnFail, false);
    }

    /**
     * @param string $query
     * @param array $data
     * @param bool $throwOnFail
     * @return DbFetch
     * @throws QueryExecuteException
     */
    public function fetch(string $query, array $data = [], bool $throwOnFail = true): DbFetch
    {
        return $this->queryExec($query, $data, $throwOnFail, true);
    }

    /**
     * @param string $query
     * @param array $data
     * @param bool $throwOnFail
     * @param bool $fetchQuery
     * @return DbQueryExec|DbFetch
     * @throws QueryExecuteException
     */
    private function queryExec(string $query, array $data, bool $throwOnFail, bool $fetchQuery): DbQueryExec|DbFetch
    {
        // Execute query
        try {
            // Prepare statement
            $stmt = $this->pdo()->prepare($query);
            if (!$stmt) {
                throw new \RuntimeException('Failed to prepare PDO statement');
            }

            // Bind params
            $boundData = [];
            foreach ($data as $key => $value) {
                $type = match (gettype($value)) {
                    "boolean" => \PDO::PARAM_BOOL,
                    "integer" => \PDO::PARAM_INT,
                    "NULL" => \PDO::PARAM_NULL,
                    "string", "double" => \PDO::PARAM_STR,
                    default => throw new \RuntimeException('Cannot bind value of type ' . gettype($value))
                };

                if (is_int($key)) {
                    $key++; // Indexed arrays get +1 to numeric keys so that they don't start with 0
                }

                $boundData[$key] = $value;
                $stmt->bindValue($key, $value, $type);
            }

            // Execute
            $execQuery = new DbQueryExec($stmt, $query, $boundData);
            $this->queries->append($execQuery); // Append query
            if ($throwOnFail && !$execQuery->isSuccess(false)) {
                $errorMsg = $execQuery->error()?->info;
                if (!is_string($errorMsg) || !$errorMsg) {
                    $errorMsg = 'Failed to execute DB query';
                }

                throw QueryExecuteException::Query($query, $boundData, $execQuery->error(), msg: $errorMsg);
            }

            // Fetch query?
            if ($fetchQuery) {
                return new DbFetch($execQuery, $stmt);
            }

            return $execQuery;
        } catch (QueryExecuteException $e) {
            throw $e;
        } catch (\Exception $e) {
            $error = isset($stmt) ? new PdoError($stmt->errorInfo()) : $this->error();
            throw QueryExecuteException::Query($query, $boundData ?? [], $error, $e);
        }
    }
}
