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
use Comely\Database\Exception\PDO_Exception;

/**
 * Class PdoAdapter
 * @package Comely\Database\Server
 */
abstract class PdoAdapter
{
    /** @var \PDO */
    private $pdo;
    /** @var DbCredentials */
    private $credentials;

    /**
     * PdoAdapter constructor.
     * @param DbCredentials $credentials
     * @throws DbConnectionException
     */
    public function __construct(DbCredentials $credentials)
    {
        $options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
        if ($credentials->persistent === true) {
            $options[\PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $this->pdo = new \PDO($credentials->dsn(), $credentials->username, $credentials->password, $options);
        } catch (\PDOException $e) {
            throw new DbConnectionException($e->getMessage(), $e->getCode());
        }

        $this->credentials = $credentials;
    }

    /**
     * @return DbCredentials
     */
    public function credentials(): DbCredentials
    {
        return $this->credentials;
    }

    /**
     * @return \PDO
     */
    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * @return PdoError
     */
    public function error(): PdoError
    {
        return new PdoError($this->pdo->errorInfo());
    }

    /**
     * @return int
     * @throws PDO_Exception
     */
    public function lastInsertId(): int
    {
        return intval($this->lastInsertSeq());
    }

    /**
     * @param string|null $seq
     * @return string
     * @throws PDO_Exception
     */
    public function lastInsertSeq(?string $seq = null): string
    {
        try {
            return $this->pdo->lastInsertId($seq);
        } catch (\PDOException $e) {
            throw PDO_Exception::Copy($e);
        }
    }

    /**
     * @return bool
     * @throws PDO_Exception
     */
    public function inTransaction(): bool
    {
        try {
            return $this->pdo->inTransaction();
        } catch (\PDOException $e) {
            throw PDO_Exception::Copy($e);
        }
    }

    /**
     * @throws PDO_Exception
     */
    public function beginTransaction(): void
    {
        try {
            if (!$this->pdo->beginTransaction()) {
                throw new PDO_Exception('Failed to begin database transaction');
            }
        } catch (\PDOException $e) {
            throw PDO_Exception::Copy($e);
        }
    }

    /**
     * @throws PDO_Exception
     */
    public function rollBack(): void
    {
        try {
            if (!$this->pdo->rollBack()) {
                throw new PDO_Exception('Failed to roll back transaction');
            }
        } catch (\PDOException $e) {
            throw PDO_Exception::Copy($e);
        }
    }

    /**
     * @throws PDO_Exception
     */
    public function commit(): void
    {
        try {
            if (!$this->pdo->commit()) {
                throw new PDO_Exception('Failed to commit transaction');
            }
        } catch (\PDOException $e) {
            throw PDO_Exception::Copy($e);
        }
    }
}
