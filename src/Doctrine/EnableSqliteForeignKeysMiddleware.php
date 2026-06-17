<?php

namespace App\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * SQLite leaves foreign-key enforcement OFF for each new connection. Without this,
 * Doctrine's ON DELETE CASCADE clauses are recorded in the schema but ignored at
 * runtime — deleting a client would leave orphaned journeys behind.
 */
final class EnableSqliteForeignKeysMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware {
            public function connect(array $params): Connection
            {
                $connection = parent::connect($params);
                $driverName = $params['driver'] ?? null;
                if ($driverName === 'pdo_sqlite' || $driverName === 'sqlite3') {
                    $connection->exec('PRAGMA foreign_keys = ON');
                }
                return $connection;
            }
        };
    }
}
