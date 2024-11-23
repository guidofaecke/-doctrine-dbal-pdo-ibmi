<?php

namespace Doctrine\DBAL\IBMIDB2PDO\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\IBMDB2;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;

use Doctrine\DBAL\ServerVersionProvider;
use Doctrine\DBAL\IBMIDB2PDO\Platforms\IBMIDB2PDOPlatform;

use Doctrine\DBAL\IBMIDB2PDO\Schema\IBMIDB2LUWIBMiPDOSchemaManager;

use function assert;

/**
 * Abstract base implementation of the {@see Doctrine\DBAL\Driver} interface for IBM DB2 based drivers.
 */
abstract class AbstractIBMIDB2PDODriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): IBMIDB2PDOPlatform
    {
//        return new DB2Platform();
        return new IBMIDB2PDOPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        assert($platform instanceof DB2Platform);

//        return new DB2SchemaManager($conn, $platform);
        return new IBMIDB2LUWIBMiPDOSchemaManager(); //SchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new IBMDB2\ExceptionConverter();
    }
}
