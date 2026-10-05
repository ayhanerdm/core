<?php
namespace ayhanerdm\Core\Traits;

use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\Enums\UserTables;
use \PDO, \Exception;

Trait ConnectsDatabase {

    /**
     * @var PDO $pdo The PDO instance for database connection, set via setDatabase() method.
     */
    private static PDO $pdo;

    /**
     * @var string $userTable Table name for the consuming class.
     */
    // private static string $userTable;

    /**
     * @var const int $DEFAULT_FETCH_METHOD The default fetch method for PDO queries.
     */
    private const DEFAULT_FETCH_METHOD = PDO::FETCH_OBJ;

    /**
     * @var int $fetchMethod The fetch method for PDO queries, default is PDO::FETCH_OBJ.
     */
    private static $fetchMethod = self::DEFAULT_FETCH_METHOD;

    /**
     * @var int|string $userQuery The user quer can be user_id, Turkish Government ID, email, username, phone number.
     */
    private static int|string $userQuery;

    /**
     * @var int $userID The user ID found by and set by SearchUserID::Search() method.
     * ayhanerdm\Core\Tools\SearchUserID::Search($userQuery, $pdo);
     */
    // #[\Depracated]
    private static int $userID;

    /**
     * 
     */
    public ?string $user_uuid;

    /**
     * @var int $lastAffectedID The last affected user_id after an insert, update, delete or fetch operation.
     * This is set by the consuming class via setLastAffectedId() method.
     */
    private static int $lastAffectedID;

    /**
     * @var object|array $user to hold user object after a Fetch method is called.
     */
    private static object|array $user;

    /**
     * @var const string $sql The SQL query to be executed.
     */
    private const UNSET = '__UNSET__';

    /**
     * Settings method to initialize the database connection, fetch method, and user table.
     * 
     * @param PDO $pdo The PDO instance for database connection.
     * @param int $fetchMethod The fetch method for PDO queries, default is PDO::FETCH_OBJ.
     * @param string $userTable The user table name, must start with "user_".
     * @return self Returns an instance of the class.
     */
    public static function Settings(
        PDO $pdo = self::UNSET,
        int $fetchMethod = self::UNSET,
        string $userTable = self::UNSET,
    ): self {
        if($pdo !== self::UNSET) self::setDatabase($pdo); // Set the database connection
        if($fetchMethod !== self::UNSET) self::setFetchMethod($fetchMethod); // Set the fetch method if provided
        if($userTable !== self::UNSET) self::$userTable = $userTable; // Set the user table if provided

        return new self();
    }

    /**
     * Set the database connection using a PDO instance.
     * 
     * @param PDO $pdo The PDO instance for database connection.
     * @return self Returns an instance of the class.
     * @throws Exception If the provided instance is not a PDO instance.
     */
    public static function setDatabase(PDO $pdo): bool|self {
        if(!$pdo instanceof PDO) {
            throw new Exception('First parameter must be an instance of PDO'); // Validate that the provided instance is a PDO instance
        }

        self::$pdo = $pdo;
        return new self();
    }

    /**
     * Get the PDO instance for database connection, optionally using a provided PDO.
     *
     * @param PDO|null $pdo Optional PDO instance to use for this call.
     * @return PDO Returns the PDO instance.
     * @throws Exception If the database connection is not set.
     */
    private static function getDatabase(?PDO $pdo = null): PDO {
        // If $pdo isn't null and is an instance of PDO, return it.
        // This means that the caller provided a specific PDO instance for this call.
        if(!is_null($pdo) && $pdo instanceof PDO) return $pdo;

        // $pdo is null, so we return the class's static PDO instance
        // if self::$pdo is set, not null and is an instance of PDO, return it
        if(isset(self::$pdo) && !is_null(self::$pdo) && self::$pdo instanceof PDO) return self::$pdo;

        // If we reach here, it means that the $pdo is not set or not an instance of PDO
        // and also the class's static PDO instance is not set or not an instance of PDO.
        // Therefore, we throw an exception indicating that the database connection is not set.
        throw new Exception('Database connection not set.');
    }

    /**
     * Set the fetch method for PDO queries.
     * 
     * @param int $fetchMethod The fetch method for PDO queries, default is PDO::FETCH_OBJ.
     * @return self Returns an instance of the class.
     */
    public static function setFetchMethod(int $fetchMethod): self {
        self::$fetchMethod = $fetchMethod;
        return new self();
    }

    /**
     * Get the fetch method for PDO queries.
     * 
     * @return int Returns the fetch method, defaulting to PDO::FETCH_OBJ if not set.
     */
    public static function getFetchMethod(): int {
        return self::$fetchMethod ?? self::DEFAULT_FETCH_METHOD; // Return the fetch method, defaulting to PDO::FETCH_OBJ if not set
    }

    /**
     * Set the user table name.
     * 
     * @param string $userTable The user table name, must start with "user_".
     * @return self Returns an instance of the class.
     * @throws Exception If the user table name is not a string, is empty, or does not start with "user_".
     */
    private static function setUserTable(string $userTable): bool|self {
        if(!is_string($userTable)) {
            throw new Exception('User table name must be a string.');
        }
        if(empty($userTable)) {
            throw new Exception('User table name cannot be empty.');
        }
        if(strpos($userTable, 'user_') !== 0) {
            throw new Exception('User table name must start with "user_" prefix.');
        }

        self::$userTable = $userTable;
        return new self();
    }

    /**
     * Get the user table name.
     * 
     * @return string Returns the user table name.
     * @throws Exception If the user table is not set.
     */
    public static function getUserTable(): string {
        if(!isset(self::$userTable)) {
            throw new Exception('User table not set.');
        }

        return self::$userTable;
    }

    /**
     * Set the user query to search for a user by user_id, Turkish Government ID, email, username, or phone number.
     * 
     * @param int|string $userQuery The user query can be user_id, Turkish Government ID, email, username, or phone number.
     * @return self Returns an instance of the class.
     * @throws Exception If the database connection is not set or if the user query is not valid.
     */
    public static function setUserQuery(int|string $userQuery): bool|self {
        if(!isset(self::$pdo)) {
            throw new Exception('Database connection not set.');
        }

        if(!is_int($userQuery) && !is_string($userQuery)) {
            throw new Exception('User query must be either an integer or a string.');
        }

        $SearchResult = SearchUserID::Search($userQuery, self::getDatabase());

        if($SearchResult === false) {
            throw new Exception('User not found.');
        }

        self::$userID = $SearchResult;

        return new self();
    }

    /**
     * Get the user ID found by the SearchUserID::Search() method.
     * 
     * @return int Returns the user ID.
     * @throws Exception If the user ID is not set, indicating that setUserQuery() was not called first.
     */
    public static function getUserID(): int {
        if(!isset(self::$userID)) {
            throw new Exception('User ID not set, call setUserQuery() first.');
        }

        return self::$userID;
    }

    /**
     * Set the last affected user_id after an insert, update, delete or fetch operation.
     * 
     * @param int $lastAffectedID The last affected user_id.
     * @return void
     */
    public static function setLastAffectedId(int $lastAffectedID): void {
        self::$lastAffectedID = $lastAffectedID;
    }

    /**
     * Get the last inserted or updated user_id (if available).
     * @return int|null Returns the last affected user_id or null if not available.
     */
    public static function getLastAffectedId(): ?int {
        return self::$lastAffectedID ?? null;
    }

    /**
     * Truncate the user table.
     * 
     * @return bool Returns true on success, false on failure.
     * @throws Exception If the database connection is not set or if the user table is not set.
     */
    public static function truncateTable(?PDO $pdo = null): bool {
        // Ensure PDO connection exists
        $pdo = self::getDatabase($pdo);

        return $pdo->exec('truncate table '.self::$userTable) !== false;
    }

    public static function truncateAllTables(?PDO $pdo = null): bool {
        // Ensure PDO connection exists
        $pdo = self::getDatabase($pdo);

        // Truncate all user tables
        $tables = [
            UserTables::UserAccounts->value,
            UserTables::UserEmails->value,
            UserTables::UserPhones->value,
            UserTables::UserUsernames->value,
            UserTables::UserProfiles->value,
            UserTables::UserWallets->value,
        ];

        foreach ($tables as $table) {
            if ($pdo->exec('truncate table ' . $table) === false) {
                return false; // If any truncate fails, return false
            }
        }

        return true; // All truncates succeeded
    }

    /**
     * Get the SQL statement to create the user table.
     * 
     * @return bool|string Returns the create table SQL or false if not found.
     * @throws Exception If the database connection is not set or if the user table is not set.
     */
    public static function getCreateTableSQL(?PDO $pdo = null): bool|string {
        // Use 'SHOW CREATE TABLE ' . self::$userTable to get the create SQL
        $pdo = self::getDatabase($pdo);

        if(!isset(self::$userTable)) {
            throw new Exception('User table not set.');
        }

        $stmt = $pdo->query('show create table ' . self::$userTable);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$result) {
            throw new Exception('Table not found: ' . self::$userTable);
        }

        return $result['Create Table'] ?? false; // Return the create table SQL or false if not found
    }
}