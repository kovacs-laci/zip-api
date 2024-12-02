<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Database\DB;
use mysqli;

class DBTest extends TestCase
{
    public function testConnectionSuccess()
    {
        $db = new DB();

        // Assert that mysqli was successfully connected
        $this->assertInstanceOf(mysqli::class, $db->mysqli);
    }
}
