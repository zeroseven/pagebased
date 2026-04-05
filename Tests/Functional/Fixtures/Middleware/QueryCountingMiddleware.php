<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\Fixtures\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * Doctrine DBAL 3.x middleware that counts every prepared statement execution
 * and every direct query. Register via TYPO3_CONF_VARS:
 *
 *   $configurationToUseInTestInstance = [
 *       'DB' => ['Connections' => ['Default' => ['driverMiddlewares' => [
 *           QueryCountingMiddleware::class,
 *       ]]]],
 *   ];
 *
 * Use QueryCountingMiddleware::reset() before the section under test, then
 * QueryCountingMiddleware::getCount() to read the number of SQL statements.
 */
final class QueryCountingMiddleware implements Middleware
{
    private static int $count = 0;

    public static function reset(): void
    {
        self::$count = 0;
    }

    public static function getCount(): int
    {
        return self::$count;
    }

    /** @internal called by inner classes only */
    public static function increment(): void
    {
        self::$count++;
    }

    public function wrap(Driver $driver): Driver
    {
        return new class ($driver) extends AbstractDriverMiddleware {
            public function connect(array $params): Driver\Connection
            {
                return new class (parent::connect($params)) extends AbstractConnectionMiddleware {
                    public function prepare(string $sql): Statement
                    {
                        return new class (parent::prepare($sql)) extends AbstractStatementMiddleware {
                            public function execute($params = null): Result
                            {
                                QueryCountingMiddleware::increment();

                                return parent::execute($params);
                            }
                        };
                    }

                    public function query(string $sql): Result
                    {
                        QueryCountingMiddleware::increment();

                        return parent::query($sql);
                    }
                };
            }
        };
    }
}
