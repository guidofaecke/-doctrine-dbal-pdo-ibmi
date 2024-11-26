# Doctrine DBAL PDO IBM i

## Doctrine DBAL PDO implementation against DB2 on your IBM i platform.
It can be used directly on your IBM i or directly on your local machine or within a Docker container.

## Usage
It can be used :
- On your IBM i 
- On your local machine 
- Within a Docker container

## Requirements
- PHP >= 8.3
- ext-pdo
- unixodbc
- [ODBC driver for IBM i Access Client Solutions](Ohttps://www.ibm.com/support/pages/odbc-driver-ibm-i-access-client-solutions)

## Installation
```bash
composer require guidofaecke/doctrine-dbal-pdo-ibmi
```

## Configuration example
```php
<?php

use Doctrine\DBAL\IBMIDB2PDO\Driver\Driver as IBMIDB2PDODriver;

return [
    'doctrine' => [
        'connection'  => [
            'orm_default' => [
                'driver_class' => IBMIDB2PDODriver::class,
                'params'       => [
                    'host'     => '10.10.10.10',
                    'dbname'   => 'S1234abc',
                    'user'     => 'db_user',
                    'password' => 'password',
                    'charset'  => 'utf8',
                    'naming'   => '1',
                ],
            ],
        ],
    ],
];
