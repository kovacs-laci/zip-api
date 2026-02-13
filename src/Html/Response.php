<?php

namespace App\Html;

class Response
{
    public static function ok(array $data = []): void {
        self::send($data, 200);
    }

    public static function created(array $data = []): void {
        self::send($data, 201);
    }

    public static function deleted(array $data = []): void {
        self::send($data, 204);
    }

    public static function updated(array $data = []): void {
        self::send($data, 202);
    }

    public static function error(string $message, int $code = 400): void {
        self::send(['error' => $message], $code);
    }

    private static function send(array $data, int $code): void {
        http_response_code($code);
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        header('Content-Type: application/json');
        echo json_encode(['code' => $code] + $data, JSON_THROW_ON_ERROR);
        exit;
    }
}