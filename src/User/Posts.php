<?php
namespace ayhanerdm\Core\User;

use \ayhanerdm\Core\Enums\UserTables;
use PDO, Exception;

class Posts {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    public static string $userTable = UserTables::UserPosts->value;

    public static function Insert(
        int $user_id,
        string $post,
        ?PDO $pdo = null,
    ): bool {
        $pdo = self::getDatabase($pdo);

        $stmt = $pdo->prepare('insert into ' . self::$userTable . ' set user_id = :user_id, post = :post, posted_at = :posted_at');
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':post', $post, PDO::PARAM_STR);
        $stmt->bindValue(':posted_at', time(), PDO::PARAM_INT);

        $result = $stmt->execute();
        self::$lastAffectedID = $pdo->lastInsertId();
        return $result;
    }

    public static function Fetch(
        ?int $user_id = null,
        ?int $post_id = null,
        ?int $fetchMethod = null,
        ?PDO $pdo = null,
    ): mixed {
        $pdo = self::getDatabase($pdo);
        $where = [];
        $params = [];

        if($user_id !== null) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $user_id;
        }
        if($post_id !== null) {
            $where[] = 'post_id = :post_id';
            $params[':post_id'] = $post_id;
        }

        $sql = 'select * from ' . self::$userTable;
        if($where) {
            $sql .= ' where ' . implode(' and ', $where);
        }

        $stmt = $pdo->prepare($sql);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        if($fetchMethod === null) $fetchMethod = self::$fetchMethod;
        
        return $stmt->fetch($fetchMethod);
    }

    public static function FetchAll(
        ?int $user_id = null,
        ?int $fetchMethod = null,
        ?PDO $pdo = null,
    ): array {
        $pdo = self::getDatabase($pdo);
        $where = [];
        $params = [];

        if($user_id !== null) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $user_id;
        }

        $sql = 'select * from ' . self::$userTable;
        if($where) {
            $sql .= ' where ' . implode(' and ', $where);
        }

        $stmt = $pdo->prepare($sql);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        if($fetchMethod === null) $fetchMethod = self::$fetchMethod;

        return $stmt->fetchAll($fetchMethod);
    }

    public static function Update(
        ?string $user_id = self::UNSET,
        ?int $post = self::UNSET,
        ?int $posted_at = self::UNSET,
        ?PDO $pdo = null,
    ) {
        $pdo = self::getDatabase($pdo);
        $set = [];
        $params = [];

        if ($user_id !== self::UNSET) {
            $set[] = 'user_id = :user_id';
            $params[':user_id'] = $user_id;
        }
        if ($post !== self::UNSET) {
            $set[] = 'post = :post';
            $params[':post'] = $post;
        }
        if ($posted_at !== self::UNSET) {
            $set[] = 'posted_at = :posted_at';
            $params[':posted_at'] = $posted_at;
        }

        if (empty($set)) {
            throw new Exception('No fields to update');
        }

        $sql = 'update ' . self::$userTable . ' set ' . implode(', ', $set) . ' where post_id = :post_id';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        return $stmt->execute();
    }

    public static function Delete(
        int $post_id,
        ?PDO $pdo = null,
    ): bool {
        $pdo = self::getDatabase($pdo);
        $stmt = $pdo->prepare('delete from ' . self::$userTable . ' where post_id = :post_id');
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}