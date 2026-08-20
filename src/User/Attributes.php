<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Enums\{ UserTables, Domains };
use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\User\{ ProfileFetchHelper, Emails, Usernames };
use PDO, Exception, stdClass;

class Attributes {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = UserTables::UserAttributes->value;

    public static function Insert(
        int $user_id,
        ?string $type,
        ?string $name,
        ?string $value = null,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $sql = 'insert into '.self::$userTable.' (user_id, type, name, value) values (:user_id, :type, :name, :value)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'type' => $type,
            'name' => $name,
            'value' => $value
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        return $result;
    }

    public static function Fetch(
        int|string $userQuery,
        string $type,
        string $name,
        ?int $fetchMethod = null,
        ?PDO $pdo = null
    ) {
        $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);

        if($user_id === false) return false;

        $sql = 'select * from '.self::$userTable.' where user_id = :user_id and type = :type and name = :name';
        $prep = $pdo->prepare($sql);
                $prep->execute([
                        'user_id' => $user_id,
                        'type' => $type,
                        'name' => $name
                    ]);

        $result = $prep->fetch();
        return $result !== false ? $result : false;
    }
}