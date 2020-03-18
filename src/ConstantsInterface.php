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

/**
 * Interface ConstantsInterface
 * @package Comely\Database
 */
interface ConstantsInterface
{
    /** string Version (Major.Minor.Release-Suffix) */
    public const VERSION = "1.2.10";
    /** int Version (Major * 10000 + Minor * 100 + Release) */
    public const VERSION_ID = 10210;
}
