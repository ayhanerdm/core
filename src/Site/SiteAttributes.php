<?php
namespace ayhanerdm\Core\Site;

use PDO, Exception;

class SiteAttributes {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    public static string $tableName = 'site_attributes';
    public static int $site_id;

    public static function getAttribute(int $site_id, string $name, ?PDO $pdo = null): ?string {
        $db = self::getDatabase($pdo);

        $prep = $db->prepare('select value from ' . self::$tableName . ' where site_id = :site_id and name = :name');
                $prep->bindValue(':site_id', $site_id, PDO::PARAM_INT);
                $prep->bindValue(':name', $name, PDO::PARAM_STR);

        $prep->execute();

        return $prep->fetchColumn() ?: null;
    }

    public static function setAttribute(int $site_id, string $name, string|int|null $value = null, ?PDO $pdo = null): bool {
        $db = self::getDatabase($pdo);
        $prep = $db->prepare('select count(*) from ' . self::$tableName . ' where site_id = :site_id and name = :name');
        $prep->bindValue(':site_id', $site_id, PDO::PARAM_INT);
        $prep->bindValue(':name', $name, PDO::PARAM_STR);
        $prep->execute();
        $exists = $prep->fetchColumn() > 0;
        if($exists) {
            $update = $db->prepare('update ' . self::$tableName . ' set value = :value where site_id = :site_id and name = :name');
            $update->bindValue(':site_id', $site_id, PDO::PARAM_INT);
            $update->bindValue(':name', $name, PDO::PARAM_STR);
            $update->bindValue(':value', $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            return $update->execute();
        } else {
            $insert = $db->prepare('insert into ' . self::$tableName . ' (site_id, name, value) values (:site_id, :name, :value)');
            $insert->bindValue(':site_id', $site_id, PDO::PARAM_INT);
            $insert->bindValue(':name', $name, PDO::PARAM_STR);
            $insert->bindValue(':value', $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            return $insert->execute();
        }
    }
}