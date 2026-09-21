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
    private static UserTables $socialsTable = UserTables::UserSocials;

    public static string $foundTable;
    public static ?bool $needsLogin = null;

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

        // Try to see if $_SESSION['user_id'] is set if the current user is trying to get their own data
        // Show session content which should be user_id if session is set, return false otherwise.
        // This means, a username cannot be "me" or "ben" ("ben" is me in Turkish), sorry Ben.
        if($userQuery === 'me' || $userQuery === 'ben') {
            if(isset($_SESSION['user_id'])) return $_SESSION['user_id'];
            else {
                self::$needsLogin = true;
                return false;
            }
        }

        foreach (['userAccounts', 'userEmails', 'userUsernames', 'userPhones', 'userSocials'] as $method) {
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
        'user_id = :userQuery or md5(user_id) = :userQuery '. // User ID
        'or tg_id = :userQuery or md5(tg_id) = :userQuery '; // Turkish Government ID
        'or email = :userQuery or md5(email) = :userQuery '. // Email
        'or phone = :userQuery or md5(phone) = :userQuery '. // Phone
        'or username = :userQuery or md5(username) = :userQuery'; // Username

        $params = [
            ':userQuery' => $userQuery,
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
        $sql = 'select * from '. self::$usernamesTable->value .' where username = :username or md5(username) = :username';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'username' => $userQuery,
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
        // $sql = 'select * from '. self::$phonesTable->value .' where phone = :phone or md5(phone) = :phoneHash';
        $sql = 'select * from ' . self::$phonesTable->value . ' where concat(country_code, subscriber_number, phone_number) = :phone or '
               .'md5(concat(country_code, subscriber_number, phone_number)) = :phone';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'phone' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }

    public static function userSocials(int|string $userQuery, PDO $pdo): bool|int {
        $sql = 'select * from ' . self::$socialsTable->value . ' where provider_id = :provider_id or md5(provider_id) = :provider_id or '
               .'provider_username = :provider_id or md5(provider_username) = :provider_id';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'provider_id' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }
}