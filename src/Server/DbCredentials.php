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

declare(strict_types=1);

namespace Comely\Database\Server;

use Comely\Database\Exception\DbConnectionException;

/**
 * Class DbCredentials
 * @package Comely\Database\Server
 */
class DbCredentials
{
    /** @var string */
    public string $driver;
    /** @var string */
    public string $dbname;
    /** @var string */
    public string $host;
    /** @var null|int */
    public ?int $port = null;
    /** @var null|string */
    public ?string $username = null;
    /** @var null|string */
    public ?string $password = null;
    /** @var bool */
    public bool $persistent = false;

    /**
     * @param DbDrivers $driver
     * @param string $dbname
     * @param string $host
     * @param int|null $port
     * @throws DbConnectionException
     */
    public function __construct(DbDrivers $driver, string $dbname, string $host = "localhost", ?int $port = null)
    {
        $this->driver = strtolower($driver->value);
        if (!in_array($this->driver, \PDO::getAvailableDrivers())) {
            throw new DbConnectionException('Invalid database driver or is not supported');
        }

        $this->dbname = $dbname;
        $this->host = $host ?? "localhost";
        $this->port = $port;
    }

    /**
     * @return string
     * @throws DbConnectionException
     */
    public function dsn(): string
    {
        if (!$this->dbname) {
            throw new DbConnectionException('Cannot get DSN; Database name is not set');
        }

        switch ($this->driver) {
            case "sqlite":
                return sprintf('sqlite:%s', $this->dbname);
            default:
                $port = $this->port ? "port=" . $this->port . ";" : "";
                return sprintf('%s:host=%s;%sdbname=%s;charset=utf8mb4', $this->driver, $this->host, $port, $this->dbname);
        }
    }

    /**
     * @param string $username
     * @param string|null $password
     * @return DbCredentials
     */
    public function login(string $username, ?string $password = null): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }
}
