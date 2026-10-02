<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Enums\UserTables;
use ayhanerdm\Core\Tools\SearchUserID;
use PDO, Exception;

class Wallet {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = UserTables::UserWallets->value;

    /*
    CREATE TABLE `user_wallets` (
    `user_id` int NOT NULL AUTO_INCREMENT,
    `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
    `balance` float NOT NULL DEFAULT '0',
    `experience` float NOT NULL DEFAULT '0',
    `level` int NOT NULL DEFAULT '0',
    PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    */

    /**
     * Insert a new wallet for a user.
     */
    public static function Insert(int $user_id, string $currency = 'TRY', float $balance = 0, float $experience = 0, int $level = 0, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $sql = 'insert into '.self::$userTable.' (user_id, currency, balance, experience, level) values (:user_id, :currency, :balance, :experience, :level)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'currency' => $currency,
            'balance' => $balance,
            'experience' => $experience,
            'level' => $level
        ]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Fetch a wallet row by userQuery (user_id, email, username, etc.).
     */
    public static function Fetch(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null): false|object|array {
        $pdo = self::getDatabase($pdo);
        $user_uuid = SearchUserID::Search($userQuery, $pdo);
        if($user_uuid === false) return false;
        if($fetchMethod === null) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid limit 1');
        $prep->execute(['user_uuid' => $user_uuid]);
        if($prep->rowCount() == 0) return false;
        $result = $prep->fetch($fetchMethod);
        self::$user = $result;
        return $result;
    }

    /**
     * Update wallet fields by userQuery.
     */
    public static function Update(int|string $userQuery, ?string $currency = self::UNSET, ?float $balance = self::UNSET, ?float $experience = self::UNSET, ?int $level = self::UNSET, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_uuid = SearchUserID::Search($userQuery, $pdo);
        if($user_uuid === false) return false;
        $fields = [];
        $params = ['user_uuid' => $user_uuid];
        if($currency !== self::UNSET) {
            $fields[] = 'currency = :currency';
            $params['currency'] = $currency;
        }
        if($balance !== self::UNSET) {
            $fields[] = 'balance = :balance';
            $params['balance'] = $balance;
        }
        if($experience !== self::UNSET) {
            $fields[] = 'experience = :experience';
            $params['experience'] = $experience;
        }
        if($level !== self::UNSET) {
            $fields[] = 'level = :level';
            $params['level'] = $level;
        }
        if(empty($fields)) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where user_uuid = :user_uuid');
        $result = $prep->execute($params);
        if($result) self::setLastAffectedId($user_uuid);
        return $result;
    }

    /**
     * Delete a wallet row by userQuery.
     */
    public static function Delete(int|string $userQuery, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('delete from '.self::$userTable.' where user_id = :user_id');
        $result = $prep->execute(['user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Update currency for a user.
     */
    public static function updateCurrency(int|string $userQuery, string $currency, ?PDO $pdo = null): bool {
        return self::Update($userQuery, $currency, null, null, null, $pdo);
    }

    /**
     * Update balance for a user.
     */
    public static function updateBalance(int|string $userQuery, float $balance, ?PDO $pdo = null): bool {
        return self::Update($userQuery, null, $balance, null, null, $pdo);
    }

    /**
     * Add to balance for a user.
     */
    public static function addBalance(int|string $userQuery, float $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set balance = balance + :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Subtract from balance for a user.
     */
    public static function subtractBalance(int|string $userQuery, float $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set balance = balance - :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Update experience for a user.
     */
    public static function updateExperience(int|string $userQuery, float $experience, ?PDO $pdo = null): bool {
        return self::Update($userQuery, null, null, $experience, null, $pdo);
    }

    /**
     * Add to experience for a user.
     */
    public static function addExperience(int|string $userQuery, float $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set experience = experience + :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Subtract from experience for a user.
     */
    public static function subtractExperience(int|string $userQuery, float $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set experience = experience - :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Update level for a user.
     */
    public static function updateLevel(int|string $userQuery, int $level, ?PDO $pdo = null): bool {
        return self::Update($userQuery, null, null, null, $level, $pdo);
    }

    /**
     * Add to level for a user.
     */
    public static function addLevel(int|string $userQuery, int $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set level = level + :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    /**
     * Subtract from level for a user.
     */
    public static function subtractLevel(int|string $userQuery, int $amount, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set level = level - :amount where user_id = :user_id');
        $result = $prep->execute(['amount' => $amount, 'user_id' => $user_id]);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }
}