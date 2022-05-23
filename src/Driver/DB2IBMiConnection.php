<?php

namespace DoctrineDbalPDOIbmi\Driver;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use Throwable;

use function sprintf;

class DB2IBMiConnection implements Connection, ServerInfoAwareConnection
{
    private PDO $conn;

    /**
     * @param array       $params
     * @param string|null $username
     * @param string|null $password
     * @param array|null  $driverOptions
     */
    public function __construct(array $params, ?string $username, ?string $password, ?array $driverOptions)
    {
        $driverOptions ??= [];

//        try {
            $this->conn = new PDO(
                $this->constructPdoDsn($params),
                $username ?? $params['user'] ?? '',
                $password ?? $params['password'] ?? '',
                $driverOptions
            );
//        } catch (PDOException $exception) {
//            throw Exception::new($exception);
//        }
//var_dump($pdo); exit;
//        $this->conn = $pdo;
//        return $pdo;
//        $this->conn = new PDO($params['connection_string'], $username, $password, $driverOptionsls);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion(): string
    {
        return $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION) ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresQueryForServerVersion(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($sql): Statement
    {
        $stmt = $this->conn->prepare($sql);

        if (! $stmt) {
            $errorInfo = $this->conn->errorInfo();

            throw new PDOException(sprintf('%s-%d-%s', $errorInfo[0], $errorInfo[1], $errorInfo[2]));
        }

        return new DB2IBMiPDOStatement($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql): Result
    {
        return $this->prepare($sql)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = ParameterType::STRING): string
    {
        if ($type === ParameterType::INTEGER) {
            return $value;
        }

        return "'" . $value . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function exec($sql): int
    {
        $stmt = $this->conn->exec($sql);

        if ($stmt === false) {
            $errorInfo = $this->conn->errorInfo();

            throw new PDOException(sprintf('%s-%d-%s', $errorInfo[0], $errorInfo[1], $errorInfo[2]));
        }

        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->conn->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        $this->conn->beginTransaction();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        try {
            return $this->conn->commit();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): bool
    {
        try {
            return $this->conn->rollBack();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->conn->errorCode();
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function errorInfo(): array
//    {
//        return $this->conn->errorInfo();
//    }

    private function constructPdoDsn(array $params): string
    {
        $dsn = 'odbc:';

        if (isset($params['driver']) && $params['driver'] !== '') {
            $dsn .= 'DRIVER=' . $params['driver'] . ';';
        }

        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'SYSTEM=' . $params['host'] . ';';
        }

        if (isset($params['naming']) && $params['naming'] !== '') {
            $dsn .= 'NAMING=' . $params['naming'] . ';';
        }

        if (isset($params['dbname']) && $params['dbname'] !== '') {
            $dsn .= 'DATABASE=' . $params['dbname'] . ';';
        }

        return $dsn;
    }
}
