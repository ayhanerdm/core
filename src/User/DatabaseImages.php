<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Tools\SearchUserID;

class DatabaseImages {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    public static string $userTable = 'user_images';

    /*
    CREATE TABLE `user_images` (
        `id` int NOT NULL AUTO_INCREMENT,
        `user_id` int NOT NULL,
        `file_data` longblob NOT NULL,
        `file_size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
        `mime_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
        `image_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avatar',
        `is_default` tinyint(1) NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    */

    /**
     * Insert a new image for a user.
     */
    public static function Insert(int $user_id, string $file_data, string $file_size, string $mime_type, string $image_type = 'avatar', bool $is_default = true, ?\PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        if($is_default) {
            $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id')->execute(['user_id' => $user_id]);
        }
        $sql = 'insert into '.self::$userTable.' (user_id, file_data, file_size, mime_type, image_type, is_default) values (:user_id, :file_data, :file_size, :mime_type, :image_type, :is_default)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'file_data' => $file_data,
            'file_size' => $file_size,
            'mime_type' => $mime_type,
            'image_type' => $image_type,
            'is_default' => $is_default ? 1 : 0
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        return $result;
    }

    /**
     * Fetch a single image row by id (primary key).
     */
    public static function Fetch(int $id, ?int $fetchMethod = null, ?\PDO $pdo = null): false|object {
        $pdo = self::getDatabase($pdo);
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where id = :id limit 1');
        $prep->execute(['id' => $id]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Fetch all images for a user (userQuery can be id, email, username, etc.).
     * file_data is unset for each row to avoid browser crashes.
     */
    public static function fetchAll(int|string $userQuery, ?int $fetchMethod = null, ?\PDO $pdo = null): array {
        $pdo = self::getDatabase($pdo);
        $userId = SearchUserID::Search($userQuery, $pdo);
        if($userId === false) return [];
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id');
        $prep->execute(['user_id' => $userId]);
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $rows = $prep->fetchAll($fetchMethod);
        foreach($rows as $row) {
            unset($row->file_data);
        }
        return $rows;
    }

    /**
     * Update an image row by id (primary key).
     */
    public static function Update(
        int $id,
        ?string $fileData = self::UNSET,
        ?string $fileSize = self::UNSET,
        ?string $mimeType = self::UNSET,
        ?string $imageType = self::UNSET,
        ?bool $isDefault = self::UNSET,
        ?\PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $fields = [];
        $params = ['id' => $id];
        if($fileData !== self::UNSET) {
            $fields[] = 'file_data = :file_data';
            $params['file_data'] = $fileData;
        }
        if($fileSize !== self::UNSET) {
            $fields[] = 'file_size = :file_size';
            $params['file_size'] = $fileSize;
        }
        if($mimeType !== self::UNSET) {
            $fields[] = 'mime_type = :mime_type';
            $params['mime_type'] = $mimeType;
        }
        if($imageType !== self::UNSET) {
            $fields[] = 'image_type = :image_type';
            $params['image_type'] = $imageType;
        }
        if($isDefault !== self::UNSET) {
            $fields[] = 'is_default = :is_default';
            $params['is_default'] = $isDefault ? 1 : 0;
        }
        if(empty($fields)) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where id = :id');
        $result = $prep->execute($params);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Delete an image row by id (primary key).
     */
    public static function Delete(int $id, ?\PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $imageRow = self::Fetch($id, null, $pdo);
        if(!$imageRow) return false;
        $prep = $pdo->prepare('delete from '.self::$userTable.' where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Fetch the default avatar image row for a user (userQuery).
     */
    public static function fetchDefaultAvatar(int|string $userQuery, ?int $fetchMethod = null, ?\PDO $pdo = null): false|object {
        $pdo = self::getDatabase($pdo);
        $userId = SearchUserID::Search($userQuery, $pdo);
        if($userId === false) return false;
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id and image_type = :image_type and is_default = 1 limit 1');
        $prep->execute(['user_id' => $userId, 'image_type' => 'avatar']);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Fetch the default cover image row for a user (userQuery).
     */
    public static function fetchDefaultCover(int|string $userQuery, ?int $fetchMethod = null, ?\PDO $pdo = null): false|object {
        $pdo = self::getDatabase($pdo);
        $userId = SearchUserID::Search($userQuery, $pdo);
        if($userId === false) return false;
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id and image_type = :image_type and is_default = 1 limit 1');
        $prep->execute(['user_id' => $userId, 'image_type' => 'cover']);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }

    /**
     * Get the default avatar image as an object for a user (userQuery).
     */
    public static function getDefaultAvatar(int|string $userQuery, ?int $fetchMethod = null, ?\PDO $pdo = null): false|object {
        return self::fetchDefaultAvatar($userQuery, $fetchMethod, $pdo);
    }

    /**
     * Get the default cover image as an object for a user (userQuery).
     */
    public static function getDefaultCover(int|string $userQuery, ?int $fetchMethod = null, ?\PDO $pdo = null): false|object {
        return self::fetchDefaultCover($userQuery, $fetchMethod, $pdo);
    }

    /**
     * Set an image as default for its user and image type, unset others.
     */
    public static function setDefault(int $id, ?\PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $imageRow = self::Fetch($id, null, $pdo);
        if(!$imageRow) return false;
        $userId = $imageRow->user_id;
        $imageType = $imageRow->image_type;
        $pdo->prepare('update '.self::$userTable.' set is_default = 0 where user_id = :user_id and image_type = :image_type')->execute(['user_id' => $userId, 'image_type' => $imageType]);
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 1 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        return $result;
    }

    /**
     * Unset an image as default. If it was default, set another as default (lowest id for that user and image type).
     */
    public static function unsetDefault(int $id, ?\PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $imageRow = self::Fetch($id, null, $pdo);
        if(!$imageRow) return false;
        $userId = $imageRow->user_id;
        $imageType = $imageRow->image_type;
        $prep = $pdo->prepare('update '.self::$userTable.' set is_default = 0 where id = :id');
        $result = $prep->execute(['id' => $id]);
        if($result) self::setLastAffectedId($id);
        $defaultExists = $pdo->prepare('select 1 from '.self::$userTable.' where user_id = :user_id and image_type = :image_type and is_default = 1');
        $defaultExists->execute(['user_id' => $userId, 'image_type' => $imageType]);
        if($defaultExists->fetchColumn() === false) {
            $next = $pdo->prepare('select id from '.self::$userTable.' where user_id = :user_id and image_type = :image_type and id != :id order by id asc limit 1');
            $next->execute(['user_id' => $userId, 'image_type' => $imageType, 'id' => $id]);
            $nextId = $next->fetchColumn();
            if($nextId) {
                self::setDefault((int)$nextId, $pdo);
            }
        }
        return $result;
    }
}