<?php
namespace ayhanerdm\Core\Tools;

use ayhanerdm\Core\Enums\UserTables;
use \PDO;

class SearchUserID
{
    private static PDO $pdo;

    private static UserTables $accountsTable = UserTables::UserAccounts;
    private static UserTables $emailsTable = UserTables::UserEmails;
    private static UserTables $usernamesTable = UserTables::UserUsernames;
    private static UserTables $phonesTable = UserTables::UserPhones;

    public static string $foundTable;

    /**
     * Search for a user ID in multiple tables based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for user IDs,
     * email addresses, usernames, or phone numbers both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function Search(null|int|string $userQuery, PDO $pdo): bool|int
    {
        if(is_null($userQuery) || empty($userQuery)) return false; // No query provided

        foreach (['userAccounts', 'userEmails', 'userUsernames', 'userPhones'] as $method) {
            if (($result = self::$method($userQuery, $pdo)) !== false) return $result;
        }

        return false;
    }

    /**
     * Search for a user ID in the user accounts table based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for user IDs
     * or tg_ids both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function userAccounts(int|string $userQuery, PDO $pdo): bool|int
    {
        $sql = 'select * from '. self::$accountsTable->value .' where '.
        'user_id = :query_user_id or md5(user_id) = :query_md5_user_id '. // User ID
        'or tg_id = :query_tg_id or md5(tg_id) = :query_md5_tg_id '. // Turkish Government ID
        'or email = :query_email or md5(email) = :query_md5_email '. // Email
        'or phone = :query_phone or md5(phone) = :query_md5_phone '. // Phone
        'or username = :query_username or md5(username) = :query_md5_username'; // Username

        $params = [
            ':query_user_id'      => $userQuery,
            ':query_md5_user_id'  => $userQuery,
            ':query_tg_id'        => $userQuery,
            ':query_md5_tg_id'    => $userQuery,
            ':query_email'        => $userQuery,
            ':query_md5_email'    => $userQuery,
            ':query_phone'        => $userQuery,
            ':query_md5_phone'    => $userQuery,
            ':query_username'     => $userQuery,
            ':query_md5_username' => $userQuery,
        ];

        $prep = $pdo->prepare($sql);
        $prep->execute($params);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$accountsTable->value;

        return $prep->fetch(\PDO::FETCH_OBJ)?->user_id ?: false;
    }

    /**
     * Search for a user ID in the user emails table based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for email addresses
     * both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function userEmails(int|string $userQuery, PDO $pdo): bool|int
    {
        $sql = 'select * from '. self::$emailsTable->value .' where email = :email or md5(email) = :emailHash';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'email' => $userQuery,
            'emailHash' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$emailsTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }

    /**
     * Search for a user ID in the user usernames table based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for usernames
     * both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function userUsernames(int|string $userQuery, PDO $pdo): bool|int
    {
        $sql = 'select * from '. self::$usernamesTable->value .' where username = :username or md5(username) = :usernameHash';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'username' => $userQuery,
            'usernameHash' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$usernamesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }

    /**
     * Search for a user ID in the user phones table based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for phone numbers
     * both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function userPhones(int|string $userQuery, PDO $pdo): bool|int
    {
        $sql = 'select * from '. self::$phonesTable->value .' where phone = :phone or md5(phone) = :phoneHash';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'phone' => $userQuery,
            'phoneHash' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }
}