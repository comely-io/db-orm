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

namespace Comely\Database\Server;

/**
 * Enum DbDrivers
 * @package Comely\Database\Server
 */
enum DbDrivers: string
{
    case MYSQL = "mysql";
    case SQLITE = "sqlite";
    case PGSQL = "pgsql";
}

