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

namespace Comely\Database\Server;

use Comely\Database\Exception\DbConnectionException;

/**
 * Class DbCredentials
 * @package Comely\Database\Server
 * @property-read string $driver
 * @property-read string $host
 * @property-read null|int $port
 * @property-read null|string $name
 * @property-read null|string $username
 * @property-read null|string $password
 * @property-read bool $persistent
 */
class DbCredentials
{
    /** @var string */
    private $driver;
    /** @var string */
    private $name;
    /** @var string */
    private $host;
    /** @var null|int */
    private $port;
    /** @var null|string */
    private $username;
    /** @var null|string */
    private $password;
    /** @var bool */
    private $persistent;

    /**
     * DbCredentials constructor.
     * @param string $driver
     * @throws DbConnectionException
     */
    public function __construct(string $driver)
    {
        $this->driver = strtolower($driver);
        if (!in_array($this->driver, \PDO::getAvailableDrivers())) {
            throw new DbConnectionException('Invalid database driver or is not supported');
        }

        $this->host = "localhost";
        $this->persistent = false;
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "driver":
            case "name":
            case "host":
            case "port":
            case "username":
            case "password":
            case "persistent":
                return $this->$prop;
        }

        throw new \OutOfBoundsException('Cannot access inaccessible property');
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "driver" => $this->driver,
            "database" => $this->name,
            "persistent" => $this->persistent
        ];
    }

    /**
     * @return string
     * @throws DbConnectionException
     */
    public function dsn(): string
    {
        if (!$this->name) {
            throw new DbConnectionException('Cannot get DSN; Database name is not set');
        }

        switch ($this->driver) {
            case "sqlite":
                return sprintf('sqlite:%s', $this->name);
            default:
                return sprintf('%s:host=%s;dbname=%s;charset=utf8mb4', $this->driver, $this->host, $this->name);
        }
    }

    /**
     * @param string $host
     * @param int|null $port
     * @return DbCredentials
     */
    public function server(string $host, ?int $port = null): self
    {
        $this->host = $host;
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $name
     * @return DbCredentials
     */
    public function database(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $username
     * @param string|null $password
     * @return DbCredentials
     */
    public function credentials(string $username, ?string $password = null): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * @return DbCredentials
     */
    public function persistent(): self
    {
        $this->persistent = true;
        return $this;
    }
}
