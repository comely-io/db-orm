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
 * Class ORM_ModelLockException
 * @package Comely\Database\Exception
 */
class ORM_ModelLockException extends ORM_ModelQueryException
{
    public const ERR_QUERY = 0x03e8;
    public const ERR_CROSSCHECK = 0x07d0;
}
