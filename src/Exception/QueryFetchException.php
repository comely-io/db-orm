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

namespace Comely\Database\Exception;

use Comely\Database\Queries\DbQueryExec;

/**
 * Class QueryFetchException
 * @package Comely\Database\Exception
 */
class QueryFetchException extends DbQueryException
{
    /**
     * QueryFetchException constructor.
     * @param DbQueryExec $query
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(private DbQueryExec $query, string $message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return DbQueryExec
     */
    public function query(): DbQueryExec
    {
        return $this->query;
    }
}
