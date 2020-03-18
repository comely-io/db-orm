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

/**
 * Class QueryNotSuccessException
 * @package Comely\Database\Exception
 */
class QueryNotSuccessException extends DbQueryException
{
    public const NOT_EXECUTED = 0x0a;
    public const HAS_ERROR = 0x0b;
    public const ROW_COUNT = 0x0c;

    /**
     * @return QueryNotSuccessException
     */
    public static function NotExecuted(): self
    {
        return new self('Query has not been executed', self::NOT_EXECUTED);
    }

    /**
     * @return QueryNotSuccessException
     */
    public static function HasError(): self
    {
        return new self('Query failed with an error', self::HAS_ERROR);
    }

    /**
     * @param int $exp
     * @param int $got
     * @return QueryNotSuccessException
     */
    public static function RowCount(int $exp, int $got): self
    {
        return new self(sprintf('Expected row count %d or more, got %d', $exp, $got), self::ROW_COUNT);
    }
}
