<?php
namespace ayhanerdm\Core\Tools;

use ayhanerdm\Core\Enums\UserTables;
use isBase64;
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
        if(isBase64($userQuery)) {
            $decodedUserQuery = base64_decode($userQuery, true);
        }

        $sql = 'SELECT * FROM '. self::$accountsTable->value .' WHERE '.
        'user_id = :uq1 or md5(user_id) = :uq2 or sha2(user_id, 256) = :uq3'.
        'or uuid = :uq4 or md5(uuid) = :uq5 or sha2(uuid, 256) = :uq6 or uuid = :uq7'.
        'or tg_id = :uq8 or md5(tg_id) = :uq9 '.
        'or email = :uq10 or md5(email) = :uq11 or sha2(email, 256) = :uq12 '.
        'or phone = :uq13 or md5(phone) = :uq14 or sha2(phone, 256) = :uq15'.
        'or username = :uq16 or md5(username) = :uq17 or sha2(username, 256) = :uq18'; 

        $params = [
            ':uq1'  => $userQuery,
            ':uq2'  => $userQuery,
            ':uq3'  => $userQuery,
            ':uq4'  => $userQuery,
            ':uq5'  => $userQuery,
            ':uq6'  => $userQuery,
            ':uq7'  => $decodedUserQuery ?? $userQuery,
            ':uq8'  => $userQuery,
            ':uq9'  => $userQuery,
            ':uq10' => $userQuery,
            ':uq11' => $userQuery,
            ':uq12' => $userQuery,
            ':uq13' => $userQuery,
            ':uq14' => $userQuery,
            ':uq15' => $userQuery,
            ':uq16' => $userQuery,
            ':uq17' => $userQuery,
            ':uq18' => $userQuery,
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
        // $sql = 'select * from '. self::$phonesTable->value .' where phone = :phone or md5(phone) = :phoneHash';
        $sql = 'select * from ' . self::$phonesTable->value . ' where concat(country_code, subscriber_number, phone_number) = :phone or '
               .'md5(concat(country_code, subscriber_number, phone_number)) = :phoneHash';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'phone' => $userQuery,
            'phoneHash' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }

    public static function userSocials(int|string $userQuery, PDO $pdo): bool|int {
        $sql = 'select * from ' . self::$socialsTable->value . ' where provider_id = :provider_id or md5(provider_id) = :provider_idHash or '
               .'provider_username = :provider_username or md5(provider_username) = :provider_usernameHash';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'provider_id' => $userQuery,
            'provider_idHash' => $userQuery,
            'provider_username' => $userQuery,
            'provider_usernameHash' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_id ?: false;
    }
}