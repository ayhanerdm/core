<?php
namespace ayhanerdm\Core\Traits;

use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Tools\Crypto;
use \ayhanerdm\Core\Exception\CustomException;
use \PDO, \Exception;

Trait ConnectsDatabaseBeta {

    private const int DEFAULT_FETCH_METHOD = PDO::FETCH_OBJ;
    private const string UNSET = '__UNSET__';

    private static PDO $pdo;
    
    public static array $options = [
        'fetch_method' => self::DEFAULT_FETCH_METHOD,
        'table_name' => null,
        'time_format' => null,
        'database_connection' => null,
        'crypto_class' => null,
    ];

    public static array $fetch_details = [
        'sql' => null,
        'result' => null,
    ];

    public static function setOptions(array $options) { self::$options = array_merge(self::$options, $options); }
    public static function setOption(string $key, mixed $value) { self::setOptions([$key => $value]); }

    private static function getOptions(): array { return self::$options ?? self::UNSET; }
    private static function getOption(string $option_name): mixed { return self::$options[$option_name] ?? null; }

    public static function showCreate(): string {
        return self::getOption('database_connection')->query('show create table ' . self::getOption('table_name'))->fetchColumn(1);
    }
}