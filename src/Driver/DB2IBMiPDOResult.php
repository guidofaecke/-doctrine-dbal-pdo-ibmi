<?php

declare(strict_types=1);

namespace DoctrineDbalPDOIbmi\Driver;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\IBMDB2\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;

use PDO;
use PDOStatement;

use function db2_fetch_array;
use function db2_fetch_assoc;
use function db2_free_result;
use function db2_num_fields;
use function db2_num_rows;
use function db2_stmt_error;

final class DB2IBMiPDOResult implements ResultInterface
{
    /** @var PDOStatement */
    private $statement;

    /**
     * @internal The result can be only instantiated by its driver connection or statement.
     *
     * @param PDOStatement $statement
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
//        $row = @db2_fetch_array($this->statement);
        $row = $this->statement->fetch(PDO::FETCH_NUM);

        if ($row === false && $this->statement->errorCode() !== '02000') {
            throw StatementError::new($this->statement);
        }

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
//        $row = @db2_fetch_assoc($this->statement);
        $row = $this->statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false && $this->statement->errorCode() !== '02000') {
            throw StatementError::new($this->statement);
        }

        return $row;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return FetchUtils::fetchOne($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        return $this->statement->rowCount();
//        return @db2_num_rows($this->statement);
    }

    public function columnCount(): int
    {
        $count = $this->statement->columnCount();
//        $count = db2_num_fields($this->statement);

//        if ($count !== false) {
            return $count;
//        }

//        return 0;
    }

    public function free(): void
    {
        $this->statement->closeCursor();
//        db2_free_result($this->statement);
    }
}
