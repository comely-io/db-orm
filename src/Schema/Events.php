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

use Comely\Database\Exception\SchemaException;

/**
 * Class Events
 * @package Comely\Database\Schema
 */
class Events
{
    public const ON_ORM_QUERY_FAIL = "orm_query_fail";

    /** @var array */
    private $events;

    /**
     * Events constructor.
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * @param callable $callback
     */
    public function on_ORM_ModelQueryFail(callable $callback): void
    {
        $this->register(self::ON_ORM_QUERY_FAIL, $callback);
    }

    /**
     * @param string $event
     * @param array|null $args
     * @throws SchemaException
     */
    public function trigger(string $event, ?array $args = null): void
    {
        if (!$this->events[$event]) {
            throw new SchemaException('No such Schema event is registered');
        }

        call_user_func_array($this->events[$event], $args);
    }

    /**
     * @param string $event
     * @param callable $callback
     */
    private function register(string $event, callable $callback): void
    {
        $this->events[$event] = $callback;
    }
}