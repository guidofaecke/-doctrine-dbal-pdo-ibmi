<?php

namespace DoctrineDbalPDOIbmi\Driver\PDO;

use DoctrineDbalPDOIbmi\Driver\AbstractIBMiDriver;
use DoctrineDbalPDOIbmi\Driver\Connection;

class DriverIBMi extends AbstractIBMiDriver
{
    public const NAMING_SYSTEM = 1;
    public const NAMING_SQL = 0;

    public const COMMIT_NO_COMMIT = 0;
    public const COMMIT_READ_UNCOMMITED = 1;
    public const COMMIT_READ_COMMITED = 2;
    public const COMMIT_REPEATABLE_READ = 3;
    public const COMMIT_SERIALIZABLE = 4;

    /**
     * {@inheritdoc}
     */
    public function connect(
        array $params,
        $username = null,
        $password = null,
        array $driverOptions = []
    ) {
        if (! isset($params['protocol'])) {
            $params['protocol'] = 'TCPIP';
        }

        if (! isset($params['naming'])) {
            $params['naming'] = 1;
        }

        if ($params['host'] !== 'localhost' && $params['host'] !== '127.0.0.1') {
            // if the host isn't localhost, use extended connection params
//            $params['dbname'] = 'odbc:DRIVER={IBM i Access ODBC DRIVER 64-bit}' .
//            $params['connection_string'] = 'odbc:dev'; // .
//                ';DATABASE=' . $params['dbname'] .
//                ';SYSTEM=' . $params['host'] .
//                ';PROTOCOL=' . $params['protocol'] .
//                ';UID=' . $username .
//                ';PWD=' . $password ; //.
//                ';NAMING=' . $params['naming'];
            if (isset($params['port'])) {
                $params['connection_string'] .= ';PORT=' . $params['port'];
            }

            $username = null;
            $password = null;
        }

        return new Connection($params, $username, $password, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ibmi_db2_pdo';
    }
}
