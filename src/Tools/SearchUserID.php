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
    public static ?string $uuid = null;
    public static ?bool $needsLogin = null;
    public static ?bool $isBase64 = false;

    /**
     * Search for a user ID in multiple tables based on the provided query.
     * $userQuery can be an integer or a string, and it will be used to search for user IDs,
     * email addresses, usernames, or phone numbers both in plain text and hashed (md5).
     *
     * @param int|string $userQuery The query to search for.
     * @param PDO $pdo The PDO instance to use for database operations.
     * @return bool|int The user ID if found, false otherwise.
     */
    public static function Search(int|string $userQuery, PDO $pdo): bool|string {
        if(empty($userQuery)) return false; // No query provided

        if(self::$isBase64 === true && isBase64($userQuery)) $userQuery = base64_decode($userQuery, true);

        // Try to see if $_SESSION['user_id'] is set if the current user is trying to get their own data
        // Show session content which should be user_uuid if session is set, return false otherwise.
        // This means, a username cannot be "me" or "ben" ("ben" is me in Turkish), sorry Ben.
        if($userQuery === 'me' || $userQuery === 'ben') {
            if(isset($_SESSION['user_uuid'])) return base64_decode($_SESSION['user_uuid']);
            elseif(isset($_SESSION['user_id'])) return $_SESSION['user_id'];
            else {
                self::$needsLogin = true;
                return false;
            }
        }

        foreach (['userAccounts', 'userEmails', 'userUsernames', 'userPhones', 'userSocials'] as $method) {
            if(($result = self::$method($userQuery, $pdo)) !== false) return $result;
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
    public static function userAccounts(int|string $userQuery, PDO $pdo): bool|string {
        $sql = 'select * from '. self::$accountsTable->value .' where '.
        'user_id = :user_id or '.
        'user_uuid = :user_uuid or md5(user_uuid) = :user_uuid_md5 or sha2(user_uuid, 256) = :user_uuid_sha256 '.
        'or tg_id_bindex = :tg_id_bindex or email_bindex = :email_bindex or phone_bindex = :phone_bindex '.
        'or username_bindex = :username_bindex';

        $secretKey = hex2bin($_ENV['APP_SECRET']); 
        $userQueryHash = hash_hmac('sha256', $userQuery, $secretKey);

        $params = [
            'user_id' => $userQuery,
            'user_uuid' => $userQuery, 'user_uuid_md5' => $userQuery, 'user_uuid_sha256' => $userQuery,
            'tg_id_bindex' => $userQueryHash, 'email_bindex' => $userQueryHash, 'phone_bindex' => $userQueryHash,
            'username_bindex' => $userQueryHash,
        ];

        $prep = $pdo->prepare($sql);
        $prep->execute($params);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$accountsTable->value;

        return $prep->fetch(\PDO::FETCH_OBJ)?->user_uuid ?: false;
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
    public static function userEmails(int|string $userQuery, PDO $pdo): bool|string {
        $sql = 'select * from '. self::$emailsTable->value .' where email = :email or md5(email) = :email_md5 or sha2(email, 256) = :email_sha256';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'email' => $userQuery,
            'email_md5' => $userQuery,
            'email_sha256' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$emailsTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_uuid ?: false;
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
    public static function userUsernames(int|string $userQuery, PDO $pdo): bool|string {
        $sql = 'select * from '. self::$usernamesTable->value .' where username = :username or md5(username) = :username_md5 or sha2(username, 256) = :username_sha256';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'username' => $userQuery,
            'username_md5' => $userQuery,
            'username_sha256' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$usernamesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_uuid ?: false;
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
    public static function userPhones(int|string $userQuery, PDO $pdo): bool|string {
        // $sql = 'select * from '. self::$phonesTable->value .' where phone = :phone or md5(phone) = :phoneHash';
        $sql = 'select * from ' . self::$phonesTable->value . ' where concat(country_code, subscriber_number, phone_number) = :phone or '
               .'md5(concat(country_code, subscriber_number, phone_number)) = :phone_md5 or sha2(concat(country_code, subscriber_number, phone_number), 256) = :phone_sha256';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'phone' => $userQuery,
            'phone_md5' => $userQuery,
            'phone_sha256' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$phonesTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_uuid ?: false;
    }

    public static function userSocials(int|string $userQuery, PDO $pdo): bool|string {
        $sql = 'select * from ' . self::$socialsTable->value . ' where provider_user_id = :provider_user_id or md5(provider_user_id) = :provider_user_id_md5 or sha2(provider_user_id, 256) = :provider_user_id_sha256 or '
               .'provider_username = :provider_username or md5(provider_username) = :provider_username_md5 or sha2(provider_username, 256) = :provider_username_sha256';

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'provider_user_id' => $userQuery, 'provider_user_id_md5' => $userQuery, 'provider_user_id_sha256' => $userQuery,
            'provider_username' => $userQuery, 'provider_username_md5' => $userQuery, 'provider_username_sha256' => $userQuery,
        ]);

        if($prep->rowCount() == 0) return false;
        if($prep->rowCount() != 0) self::$foundTable = self::$socialsTable->value;

        return $prep->fetch(PDO::FETCH_OBJ)?->user_uuid ?: false;
    }
}