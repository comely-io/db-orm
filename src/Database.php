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

use Comely\Database\Queries\Query;
use Comely\Database\Queries\QueryBuilder;
use Comely\Database\Queries\Result\Fetch;
use Comely\Database\Server\DbCredentials;
use Comely\Database\Server\PdoAdapter;

/**
 * Class Database
 * @package Comely\Database
 */
class Database extends PdoAdapter implements ConstantsInterface
{
    /** @var Queries */
    private $queries;

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
     * @param array|null $data
     * @return Fetch
     * @throws Exception\QueryExecuteException
     * @throws Exception\QueryBuildException
     */
    public function fetch(string $query, ?array $data = null): Fetch
    {
        return new Fetch($this, new Query(Query::FETCH, $query, $data));
    }

    /**
     * @param string $query
     * @param array|null $data
     * @return Query
     * @throws Exception\QueryBuildException
     * @throws Exception\QueryExecuteException
     */
    public function exec(string $query, ?array $data = null): Query
    {
        $query = new Query(Query::EXEC, $query, $data);
        $query->execute($this);
        return $query;
    }
}
