<?php
namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\Enums\UserTables;
use PDO, Exception;

class Posts
{
    public static int|string $userQuery;
    public static int $userID;
    public static int $fetchMethod = \PDO::FETCH_OBJ;
    public static PDO $pdo;

    public static UserTables $table = UserTables::UserPosts;
    public static bool $settingsCalled = false;
    public static string $sql;


    public static function Settings(
        string|int $userQuery,
        int $fetchMethod = PDO::FETCH_OBJ,
        ?PDO $pdo = null
    ): self {
        self::$userQuery = $userQuery;

        if ((self::$userID = SearchUserID::Search($userQuery, $pdo)) === false) {
            throw new Exception('Settings(userQuery: $userQuery) couldn\'t be found in database!');
        }

        if($fetchMethod) {
            self::$fetchMethod = $fetchMethod;
        }

        if (is_null($pdo)) {
            throw new Exception('Settings(pdo: $pdo) is null!');
        }

        self::$pdo = $pdo;
        self::$settingsCalled = true;

        return new self();
    }

    public static function getRows(): mixed
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and its arguments must be set!');
        }

        self::$sql = "select * from " . self::$table->value . " where user_id = :user_id";

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(self::$fetchMethod) ?: null;
    }

    public static function getRow(int $postID): mixed
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and its arguments must be set!');
        }

        self::$sql = "select * from " . self::$table->value . " where user_id = :user_id and id = :post_id limit 1";

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->bindValue(':post_id', $postID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(self::$fetchMethod) ?: null;
    }
}