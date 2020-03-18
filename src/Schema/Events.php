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

namespace Comely\Database\Schema;

use Comely\Utils\Events\Event;
use Comely\Utils\Events\EventsRegister;

/**
 * Class Events
 * @package Comely\Database\Schema
 */
class Events
{
    public const ON_ORM_QUERY_FAIL = "orm_query_fail";
    public const ON_DB_QUERY_EXEC_FAIL = "db_query_exec_fail";

    /** @var array */
    private $events;

    /**
     * Events constructor.
     */
    public function __construct()
    {
        $this->events = new EventsRegister();
    }

    /**
     * @return Event
     */
    public function on_ORM_ModelQueryFail(): Event
    {
        return $this->events->on(self::ON_ORM_QUERY_FAIL);
    }

    /**
     * @return Event
     */
    public function on_DB_QueryExecFail(): Event
    {
        return $this->events->on(self::ON_DB_QUERY_EXEC_FAIL);
    }
}
