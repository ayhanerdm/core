<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Enums\{ UserTables, Domains };
use ayhanerdm\Core\Tools\SearchUserID;
use PDO, Exception;

class Usernames {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = UserTables::UserUsernames->value;

    /* user_usernames table structure:
     CREATE TABLE `user_usernames` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_default` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
     */

    /**
     * Insert a new username for a user.
     */
    public static function Insert(int $user_id, string $username, bool $is_default = false, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        if($is_default) {
            $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id')->execute(['user_id' => $user_id]);
        }
        $sql = 'insert into '.self::$userTable.' (user_id, username, is_default) values (:user_id, :username, :is_default)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'username' => $username,
            'is_default' => $is_default ? 1 : 0
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        return $result;
    }

    /**
     * Check if a username exists. Returns user_id if exists, false otherwise.
     */
    public static function Exists(string $username, ?PDO $pdo = null): int|false {
        $pdo = self::getDatabase($pdo);
        $prep = $pdo->prepare('select user_id from '.self::$userTable.' where username = :username limit 1');
        $prep->execute(['username' => $username]);
        $row = $prep->fetch(PDO::FETCH_OBJ);
        return $row ? (int)$row->user_id : false;
    }

    /**
     * Fetch a single username row by id (primary key) or username string or userQuery.
     */
    public static function Fetch(int|string $query, ?int $fetchMethod = null, ?PDO $pdo = null): false|object|array {
        $pdo = self::getDatabase($pdo);
        $where = is_int($query) ? 'id = :query' : 'username = :query';
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where '.$where.' limit 1');
        $prep->execute(['query' => $query]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Fetch all usernames for a user (userQuery can be id, email, username, etc.).
     */
    public static function fetchAll(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null): array {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return [];
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id');
        $prep->execute(['user_id' => $user_id]);
        return $prep->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetch the default username row for a user (userQuery).
     */
    public static function fetchDefault(int|string $userQuery, ?PDO $pdo = null): false|object {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id and is_default = 1 limit 1');
        $prep->execute(['user_id' => $user_id]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetchObject();
    }

    /**
     * Get the default username as a string for a user (userQuery).
     */
    public static function getDefaultUsername(int|string $userQuery, ?PDO $pdo = null): ?string {
        $row = self::fetchDefault($userQuery, $pdo);
        return $row && isset($row->username) ? $row->username : null;
    }

    /**
     * Set a username as default for its user, unset others.
     */
    public static function setDefaultUsername(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $usernameRow = self::Fetch($id, null, $pdo);
        if(!$usernameRow) return false;
        $user_id = $usernameRow->user_id;
        $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id')->execute(['user_id' => $user_id]);
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 1 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Unset a username as default. If it was default, set another as default (lowest id for that user).
     */
    public static function unsetDefaultUsername(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $usernameRow = self::Fetch($id, null, $pdo);
        if(!$usernameRow) return false;
        $user_id = $usernameRow->user_id;
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 0 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        $defaultExists = $pdo->prepare('select 1 from '.self::$userTable.' where user_id = :user_id and is_default = 1');
        $defaultExists->execute(['user_id' => $user_id]);
        if($defaultExists->fetchColumn() === false) {
            $next = $pdo->prepare('select id, username from '.self::$userTable.' where user_id = :user_id and id != :id order by id asc limit 1');
            $next->execute(['user_id' => $user_id, 'id' => $id]);
            $nextRow = $next->fetch(PDO::FETCH_OBJ);
            if($nextRow) {
                self::setDefaultUsername((int)$nextRow->id, $pdo);
            }
        }
        return $result;
    }

    /**
     * Update a username row by id (primary key).
     */
    public static function Update(
        int $id,
        ?string $username = self::UNSET,
        ?bool $is_default = self::UNSET,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $fields = [];
        $params = ['id' => $id];
        if($username !== self::UNSET) {
            $fields[] = 'username = :username';
            $params['username'] = $username;
        }
        if($is_default !== self::UNSET) {
            $fields[] = 'is_default = :is_default';
            $params['is_default'] = $is_default ? 1 : 0;
        }
        if(empty($fields)) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where id = :id');
        $result = $prep->execute($params);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Delete a username row by id (primary key).
     */
    public static function Delete(int $id, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $usernameRow = self::Fetch($id, null, $pdo);
        if(!$usernameRow) return false;
        $isDefault = $usernameRow->is_default;
        $user_id = $usernameRow->user_id;
        $prep = $pdo->prepare('delete from '.self::$userTable.' where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        if($result && $isDefault) {
            $next = $pdo->prepare('select id from '.self::$userTable.' where user_id = :user_id order by id asc limit 1');
            $next->execute(['user_id' => $user_id]);
            $nextId = $next->fetchColumn();
            if($nextId) {
                self::setDefaultUsername((int)$nextId, $pdo);
            }
        }
        return $result;
    }
}