<?php
namespace App\Database;

use mysqli;
use Exception;

class DB
{
    const HOST = 'localhost';
    const USER = 'root';
    const PASSWORD = null;
    const DATABASE = 'postoffice';

    public $mysqli;

    public function __construct(
        $host = self::HOST,
        $user = self::USER,
        $password = self::PASSWORD,
        $database = self::DATABASE
    ) {
        try {
            $this->mysqli = new mysqli(
                $host,
                $user,
                $password,
                $database
            );

            if ($this->mysqli->connect_error) {
                throw new Exception("Connection failed: " . $this->mysqli->connect_error);
            }

            $this->mysqli->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            // Handle the error gracefully
            die("Database connection error, please try again later.");
        }
    }

    public function __destruct()
    {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }
}
