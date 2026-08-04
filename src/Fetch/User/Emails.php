<?php
namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Enums\UserTables;
use PDO;
use Exception;

class Emails {
    public static string $sql;
    public static UserTables $table = UserTables::UserEmails;
    public static string $userQuery;
    public static int $userID;
    public static bool $isVerified = false;
    public static int $fetchMethod = PDO::FETCH_OBJ;
    private static PDO $pdo;
    private static bool $settingsCalled = false;

    /**
     * Settings method to set up the class with user query, fetch method, and PDO instance
     * @param string|int $userQuery The username or user ID to search for
     * @param bool $isVerified Whether to filter by verified emails
     * @param int $fetchMethod The fetch method to use (default is PDO::FETCH_OBJ)
     * @param PDO|null $pdo The PDO instance to use (default is null)
     * @return self
     * @throws Exception If any of the parameters are null or invalid
     */
    public static function Settings(string|int $userQuery, bool $isVerified = false, int $fetchMethod = PDO::FETCH_OBJ, ?PDO $pdo = null): self {
        if(is_null($userQuery)) {
            throw new Exception('Settings(userQuery: $userQuery) is null!');
        }

        self::$userQuery = $userQuery;

        if((self::$userID = \ayhanerdm\Core\Tools\SearchUserID::Search($userQuery, $pdo)) === false) {
            throw new Exception('Settings(userQuery: $userQuery) couldn\'t be found in database!');
        }

        if($isVerified) self::$isVerified = $isVerified;

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

    /**
     * Fetch all rows for the user from the emails table
     * @return mixed The fetched rows or false if no rows are found
     * @throws Exception If Settings() method is not called or its arguments are not set
     */
    public static function getRows(): mixed {
        if(!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
        }

        self::$sql = 'select * from '. self::$table->value .' where user_id = :user_id' . (self::$isVerified ? ' and is_verified = 1' : '');

        $prep = self::$pdo->prepare(self::$sql);
        $prep->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $prep->execute();

        return $prep->fetchAll(self::$fetchMethod) ?: false;
    }

    /**
     * Fetch the default row for the user from the emails table
     * @return mixed The fetched row or null if no row is found
     * @throws Exception If Settings() method is not called or its arguments are not set
     */
    public static function getDefaultRow(): mixed {
        if(!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
        }

        self::$sql = 'select * from '. self::$table->value .' where user_id = :user_id and is_default = 1 limit 1';

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(self::$fetchMethod) ?: null;
    }
}
