<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSqliteMathFunctions();
    }

    private function registerSqliteMathFunctions(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        /** @var \PDO $pdo */
        $pdo = DB::connection()->getPdo();

        $pdo->sqliteCreateFunction('RADIANS', fn (float $degrees): float => deg2rad($degrees), 1);
        $pdo->sqliteCreateFunction('SIN', fn (float $x): float => sin($x), 1);
        $pdo->sqliteCreateFunction('COS', fn (float $x): float => cos($x), 1);
        $pdo->sqliteCreateFunction('ASIN', fn (float $x): float => asin($x), 1);
        $pdo->sqliteCreateFunction('SQRT', fn (float $x): float => sqrt(max(0, $x)), 1);
        $pdo->sqliteCreateFunction('POWER', fn (float $x, float $y): float => pow($x, $y), 2);
    }
}
