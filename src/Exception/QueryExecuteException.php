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

namespace Comely\Database\Exception;

use Comely\Database\Queries\Query;
use Comely\Database\Schema;
use Throwable;

/**
 * Class DbQueryException
 * @package Comely\Database\Exception
 */
class QueryExecuteException extends DbQueryException
{
    /** @var Query */
    private $query;

    /**
     * DbQueryException constructor.
     * @param Query|null $query
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(Query $query, string $message = "", int $code = 0, Throwable $previous = null)
    {
        $this->query = $query;
        parent::__construct($message, $code, $previous);
        Schema::Events()->on_DB_QueryExecFail()->trigger([$query]);
    }

    /**
     * @return Query
     */
    public function query(): Query
    {
        return $this->query;
    }
}
