<?php
/**
 * Single shared PDO connection. Credentials come from config.php,
 * never from the source code.
 */
class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $host = Config::get('db.host', '127.0.0.1');
            $port = Config::get('db.port', 3306);
            $name = Config::get('db.name');

            self::$connection = new PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                Config::get('db.user'),
                Config::get('db.pass'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // errors become exceptions
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
                    PDO::MYSQL_ATTR_FOUND_ROWS   => true,                    // rowCount() = matched rows
                ]
            );
        }
        return self::$connection;
    }
}
