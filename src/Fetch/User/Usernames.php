<?php
namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Enums\{ UserTables, Domains };
use ayhanerdm\Core\Tools\SearchUserID;
use PDO, Exception;

class Usernames {
    public static string $sql;
    public static UserTables $table = UserTables::UserUsernames;
    public static string $userQuery;
    public static int $userID;
    public static bool $https = true;
    public static int $fetchMethod = PDO::FETCH_OBJ;
    private static PDO $pdo;
    private static bool $settingsCalled = false;

    public static function Settings(string|int $userQuery, bool $https = true, int $fetchMethod = PDO::FETCH_OBJ, ?PDO $pdo = null): self {
        if(is_null($userQuery)) {
            throw new Exception('Settings(userQuery: $userQuery) is null!');
        }

        self::$userQuery = $userQuery;

        if((self::$userID = SearchUserID::Search($userQuery, $pdo)) === false) {
            throw new Exception('Settings(userQuery: $userQuery) couldn\'t be found in database!');
        }

        if(!is_null($https)) self::$https = $https;

        if(is_null($fetchMethod)) {
            throw new Exception('Settings(fetchMethod: $fetchMethod) is null!');
        }

        self::$fetchMethod = $fetchMethod;

        if(is_null($pdo)) {
            throw new Exception('Settings(pdo: $pdo) is null!');
        }

        self::$pdo = $pdo;

        self::$settingsCalled = true;    

        return new self();
    }

    public static function getRows(): mixed {
        if(!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        self::$sql = "select * from " . self::$table->value . " where user_id = :user_id";

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(self::$fetchMethod) ?: null;
    }

    public static function getDefaultRow(): mixed {
        if(!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        self::$sql = "select * from " . self::$table->value . " where user_id = :user_id and is_default = 1 limit 1";

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(self::$fetchMethod) ?: null;
    }
}
