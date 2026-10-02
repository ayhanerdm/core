<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\{ 
    Tools\SearchUserID,
    Enums\UserTables,
};
use PDO, SensitiveParameter;


class Emails {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = UserTables::UserEmails->value;

    /*
        CREATE TABLE `user_emails` (
        `id` int NOT NULL,
        `user_id` int NOT NULL,
        `email` varchar(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `is_default` tinyint(1) NOT NULL DEFAULT '0',
        `is_verified` tinyint(1) NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    */

    /**
     * Insert a new email for a user.
     */
    public static function Insert(
        int $user_id,
        #[SensitiveParameter] string $email,
        bool $is_default = false,
        bool $is_verified = false,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        if($is_default) {
            // Unset all other defaults for this user before inserting
            $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id')->execute(['user_id' => $user_id]);
        }
        $sql = 'insert into '.self::$userTable.' (user_id, email, is_default, is_verified) values (:user_id, :email, :is_default, :is_verified)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'email' => $email,
            'is_default' => $is_default ? 1 : 0,
            'is_verified' => $is_verified ? 1 : 0
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        // If inserted as default, update user_accounts.email
        if($result && $is_default) {
            $pdo->prepare('update user_accounts set email = :email where user_id = :user_id')->execute(['email' => $email, 'user_id' => $user_id]);
        }
        return $result;
    }

    /**
     * Check if an email exists. Returns user_id if exists, false otherwise.
     */
    public static function Exists(string $email, ?PDO $pdo = null): int|false {
        $pdo = self::getDatabase($pdo);
        $prep = $pdo->prepare('select user_id from '.self::$userTable.' where email = :email limit 1');
        $prep->execute(['email' => $email]);
        $row = $prep->fetch(PDO::FETCH_OBJ);
        return $row ? (int)$row->user_id : false;
    }

    /**
     * Fetch a single email row by id (primary key) or email string or userQuery.
     */
    public static function Fetch(int|string $query, ?int $fetchMethod = null, ?PDO $pdo = null): false|object|array {
        $pdo = self::getDatabase($pdo);
        $where = is_int($query) ? 'id = :query' : 'email = :query';
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where '.$where.' limit 1');
        $prep->execute(['query' => $query]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Fetch all emails for a user (userQuery can be id, email, username, etc.).
     */
    public static function fetchAll(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null): array {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return [];
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id');
        $prep->execute(['user_id' => $user_id]);
        return $prep->fetchAll($fetchMethod);
    }

    /**
     * Fetch the default email row for a user (userQuery).
     */
    public static function fetchDefault(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null): false|object {
        $pdo = self::getDatabase($pdo);
        $user_uuid = SearchUserID::Search($userQuery, $pdo);
        if($user_uuid === false) return false;
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid and is_default = 1 limit 1');
        $prep->execute(['user_uuid' => $user_uuid]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Get the default email address as a string for a user (userQuery).
     */
    public static function getDefaultEmail(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null): ?string {
        $row = self::fetchDefault($userQuery, $fetchMethod, $pdo);
        return $row && isset($row->email) ? $row->email : null;
    }

    /**
     * Set an email as default for its user, unset others.
     */
    public static function setDefaultEmail(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        // Get the email row
        $emailRow = self::Fetch($id, $pdo);
        if(!$emailRow) return false;
        $user_id = $emailRow->user_id;
        // Unset all other defaults for this user
        $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id')->execute(['user_id' => $user_id]);
        // Set this email as default
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 1 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) {
            self::setLastAffectedId($id);
            // Update user_accounts.email to the new default email
            $updateAccount = $pdo->prepare('update user_accounts set email = :email where user_id = :user_id');
            $updateAccount->execute(['email' => $emailRow->email, 'user_id' => $user_id]);
        }
        return $result;
    }

    /**
     * Unset an email as default. If it was default, set another as default (lowest id for that user).
     */
    public static function unsetDefaultEmail(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $emailRow = self::Fetch($id, $pdo);
        if(!$emailRow) return false;
        $user_id = $emailRow->user_id;
        // Unset this email as default
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 0 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        // If this was the default, set another as default (lowest id)
        $defaultExists = $pdo->prepare('select 1 from '.self::$userTable.' where user_id = :user_id and is_default = 1');
        $defaultExists->execute(['user_id' => $user_id]);
        if($defaultExists->fetchColumn() === false) {
            $next = $pdo->prepare('select id, email from '.self::$userTable.' where user_id = :user_id and id != :id order by id asc limit 1');
            $next->execute(['user_id' => $user_id, 'id' => $id]);
            $nextRow = $next->fetch(PDO::FETCH_OBJ);
            if($nextRow) {
                self::setDefaultEmail((int)$nextRow->id, $pdo);
                // user_accounts.email will be updated by setDefaultEmail
            } else {
                // No emails left, clear user_accounts.email
                $pdo->prepare('update user_accounts set email = NULL where user_id = :user_id')->execute(['user_id' => $user_id]);
            }
        }
        return $result;
    }

    /**
     * Returns true if the email is verified.
     */
    public static function isVerified(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $prep = $pdo->prepare('select is_verified from '.self::$userTable.' where id = :id');
        $prep->execute(['id' => $id]);
        $row = $prep->fetch(PDO::FETCH_OBJ);
        return $row && $row->is_verified == 1;
    }

    /**
     * Set the is_verified flag for an email.
     */
    public static function setVerified(int $id, bool $verified = true, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $prep = $pdo->prepare('update '.self::$userTable.' set is_verified = :verified where id = :id');
        $result = $prep->execute(['verified' => $verified ? 1 : 0, 'id' => $id]);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Count the number of emails for a user (userQuery).
     */
    public static function countUserEmails(int|string $userQuery, ?PDO $pdo = null): int {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return 0;
        $prep = $pdo->prepare('select count(*) from '.self::$userTable.' where user_id = :user_id');
        $prep->execute(['user_id' => $user_id]);
        return (int)$prep->fetchColumn();
    }

    /**
     * Update an email row by id (primary key).
     */
    public static function Update(
        int $id,
        ?string $email = self::UNSET,
        ?bool $is_default = self::UNSET,
        ?bool $is_verified = self::UNSET,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $fields = [];
        $params = ['id' => $id];
        if($email !== self::UNSET) {
            $fields[] = 'email = :email';
            $params['email'] = $email;
        }
        if($is_default !== self::UNSET) {
            $fields[] = 'is_default = :is_default';
            $params['is_default'] = $is_default ? 1 : 0;
        }
        if($is_verified !== self::UNSET) {
            $fields[] = 'is_verified = :is_verified';
            $params['is_verified'] = $is_verified ? 1 : 0;
        }
        if(empty($fields)) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where id = :id');
        $result = $prep->execute($params);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Delete an email row by id (primary key).
     */
    public static function Delete(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);

        // Check if the email to be deleted is default and get user_id
        $emailRow = self::Fetch($id, $pdo);
        if(!$emailRow) return false;
        $isDefault = $emailRow->is_default;
        $user_id = $emailRow->user_id;

        // Delete the email
        $prep = $pdo->prepare('delete from '.self::$userTable.' where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        
        // If deleted email was default, set another as default (lowest id for that user)
        if($result && $isDefault) {
            $next = $pdo->prepare('select id from '.self::$userTable.' where user_id = :user_id order by id asc limit 1');
            $next->execute(['user_id' => $user_id]);
            $nextId = $next->fetchColumn();
            if($nextId) {
                self::setDefaultEmail((int)$nextId, $pdo);
            } else {
                // No emails left, clear user_accounts.email
                $pdo->prepare('update user_accounts set email = NULL where user_id = :user_id')->execute(['user_id' => $user_id]);
            }
        }
        return $result;
    }
}