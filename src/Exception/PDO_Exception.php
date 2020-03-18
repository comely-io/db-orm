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
 * Class PDO_Exception
 * @package Comely\Database\Exception
 */
class PDO_Exception extends DatabaseException
{
    /**
     * @param \PDOException $e
     * @return PDO_Exception
     */
    public static function Copy(\PDOException $e): self
    {
        return new self($e->getMessage(), $e->getCode());
    }
}
