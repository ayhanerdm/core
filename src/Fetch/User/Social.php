<?php
namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Enums\UserTables;
use PDO;
use Exception;

class Social {
    public static string $sql;
    public static UserTables $table = UserTables::UserSocials;
    public static string $userQuery;
    public static int $userID;
    public static int $fetchMethod = PDO::FETCH_OBJ;
    private static PDO $pdo;
    private static bool $settingsCalled = false;

    public static function Settings(string|int $userQuery, int $fetchMethod = PDO::FETCH_OBJ, ?PDO $pdo = null): self {
        if (is_null($userQuery)) {
            throw new Exception('Settings(userQuery: $userQuery) is null!');
        }
        self::$userQuery = $userQuery;
        if ((self::$userID = \ayhanerdm\Core\Tools\SearchUserID::Search($userQuery, $pdo)) === false) {
            throw new Exception('Settings(userQuery: $userQuery) couldn\'t be found in database!');
        }
        if (is_null($fetchMethod)) {
            throw new Exception('Settings(fetchMethod: $fetchMethod) is null!');
        }
        self::$fetchMethod = $fetchMethod;
        if (is_null($pdo)) {
            throw new Exception('Settings(pdo: $pdo) is null!');
        }
        self::$pdo = $pdo;
        self::$settingsCalled = true;
        return new self();
    }

    public static function getRows(): mixed {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
        }
        self::$sql = 'select * from ' . self::$table->value . ' where user_id = :user_id';
        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(self::$fetchMethod) ?: null;
    }
}
