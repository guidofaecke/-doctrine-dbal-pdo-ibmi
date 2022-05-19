<?php

namespace DoctrineDbalPDOIbmi\Driver;

class DB2IBMiPDODriver extends AbstractDB2Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
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

        return new DB2IBMiPDOConnection($params, $username, $password, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ibmi_db2_pdo';
    }
}
