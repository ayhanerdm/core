<?php

namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Enums\ReturnTypes;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Tools\Crypto;
use \ayhanerdm\Core\Exception\CustomException;
use \ayhanerdm\Core\Traits\ConnectsDatabaseBeta;
use \PDO;
use \ReflectionMethod;
use \ReflectionNamedType;
use \ReflectionUnionType;
use \ReflectionType;

class UserSocialsCredentials
{
    use ConnectsDatabaseBeta;

    public function __construct(?array $options = null)
    {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserSocialsCredentials->value);
    }

    public static function setCredential(
        string|int $user_social_id,
        string $name,
        string $value,
        ?string $value_bindex = null
    ): int|false {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        if($name === '') {
            throw new CustomException('Credential adı boş bırakılamaz.', null, 'invalid_credential_name');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        $sql = 'SELECT id FROM ' . UserTables::UserSocials->value . '
                WHERE id = :id AND user_uuid = :user_uuid AND deleted_at IS NULL
                LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute(['id' => $user_social_id, 'user_uuid' => $user_uuid]);

        if($prep->fetch(PDO::FETCH_ASSOC) === false) {
            throw new CustomException(
                'Belirtilen user_social_id mevcut kullanıcıya ait değil veya silinmiş.',
                null,
                'invalid_user_social'
            );
        }

        $crypto_class = self::getCryptoClass();
        $app_secret = self::getOption('app_secret');

        if(!is_string($app_secret) || $app_secret === '') {
            throw new CustomException('Credential şifreleme işlemi için app_secret gereklidir.', null, 'missing_app_secret');
        }

        $encrypted_value = $crypto_class::encrypt($value, $app_secret);

        if(!is_string($encrypted_value)) {
            throw new CustomException('Credential değeri şifrelenemedi.', null, 'credential_encryption_failed');
        }

        $timestamp = (string) time();

        $sql = 'SELECT id FROM ' . self::getOption('table_name') . '
                WHERE user_social_id = :user_social_id AND name = :name LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute(['user_social_id' => $user_social_id, 'name' => $name]);
        $existing = $prep->fetch(PDO::FETCH_ASSOC);

        if($existing !== false) {
            $sql = 'UPDATE ' . self::getOption('table_name') . '
                    SET user_uuid = :user_uuid, value = :value, value_bindex = :value_bindex, updated_at = :updated_at
                    WHERE id = :id';

            self::$fetch_details['sql'] = $sql;

            $prep = $pdo->prepare($sql);
            $prep->execute([
                'user_uuid' => $user_uuid,
                'value' => $encrypted_value,
                'value_bindex' => $value_bindex,
                'updated_at' => $timestamp,
                'id' => $existing['id'],
            ]);

            return (int) $existing['id'];
        }

        $sql = 'INSERT INTO ' . self::getOption('table_name') . '
                (user_uuid, user_social_id, name, value, value_bindex, created_at, updated_at)
                VALUES (:user_uuid, :user_social_id, :name, :value, :value_bindex, :created_at, :updated_at)';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'user_uuid' => $user_uuid,
            'user_social_id' => $user_social_id,
            'name' => $name,
            'value' => $encrypted_value,
            'value_bindex' => $value_bindex,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function set(
        string|int $user_social_id,
        string $name,
        string $value,
        ?string $value_bindex = null
    ): int|false {
        return self::setCredential($user_social_id, $name, $value, $value_bindex);
    }

    public static function getCredential(string|int $user_social_id, ?string $name = null): mixed
    {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        $conditions = [
            'c.user_social_id = :user_social_id',
            'c.user_uuid = :user_uuid',
            's.user_uuid = :user_uuid_social',
            's.deleted_at IS NULL',
        ];

        $parameters = [
            'user_social_id' => $user_social_id,
            'user_uuid' => $user_uuid,
            'user_uuid_social' => $user_uuid,
        ];

        if($name !== null) {
            $conditions[] = 'c.name = :name';
            $parameters['name'] = $name;
        }

        $sql = 'SELECT c.* FROM ' . self::getOption('table_name') . ' c
                INNER JOIN ' . UserTables::UserSocials->value . ' s ON s.id = c.user_social_id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY c.id DESC';

        if($name !== null) $sql .= ' LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute($parameters);

        if($name !== null) {
            $result = $prep->fetch(PDO::FETCH_ASSOC);
            if($result === false) return false;
            return self::decryptResult($result);
        }

        $results = $prep->fetchAll(PDO::FETCH_ASSOC);
        if(count($results) === 0) return false;

        foreach($results as &$result) $result = self::decryptResult($result);
        unset($result);

        return self::formatResult($results);
    }

    public static function get(string|int $user_social_id, ?string $name = null): mixed
    {
        return self::getCredential($user_social_id, $name);
    }

    public static function deleteCredential(string|int $user_social_id, string $name): bool
    {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        $sql = 'DELETE c FROM ' . self::getOption('table_name') . ' c
                INNER JOIN ' . UserTables::UserSocials->value . ' s ON s.id = c.user_social_id
                WHERE c.user_social_id = :user_social_id
                  AND c.name = :name
                  AND c.user_uuid = :user_uuid
                  AND s.user_uuid = :user_uuid_social';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'user_social_id' => $user_social_id,
            'name' => $name,
            'user_uuid' => $user_uuid,
            'user_uuid_social' => $user_uuid,
        ]);

        return $prep->rowCount() > 0;
    }

    public static function delete(string|int $user_social_id, string $name): bool
    {
        return self::deleteCredential($user_social_id, $name);
    }

    private static function decryptResult(array $result): array
    {
        $crypto_class = self::getCryptoClass();
        $app_secret = self::getOption('app_secret');

        if(!is_string($app_secret) || $app_secret === '') {
            throw new CustomException('Credential şifre çözme işlemi için app_secret gereklidir.', null, 'missing_app_secret');
        }

        $decrypted = $crypto_class::decrypt($result['value'], $app_secret);

        if(!is_string($decrypted)) {
            throw new CustomException('Credential değeri çözülemedi.', null, 'credential_decryption_failed');
        }

        $result['value'] = $decrypted;
        return $result;
    }

    private static function getCryptoClass(): string
    {
        $custom_crypto = self::getOption('crypto_class');

        if(
            $custom_crypto !== null &&
            is_string($custom_crypto) &&
            self::isCompatibleCryptoClass($custom_crypto)
        ) {
            return $custom_crypto;
        }

        $default_crypto = Crypto::class;

        if(self::isCompatibleCryptoClass($default_crypto)) return $default_crypto;

        throw new CustomException('Kullanılabilir bir crypto sınıfı bulunamadı.', null, 'invalid_crypto_class');
    }

    private static function isCompatibleCryptoClass(string $crypto_class): bool
    {
        if(!class_exists($crypto_class)) return false;

        foreach(['encrypt', 'decrypt'] as $method_name) {
            if(!method_exists($crypto_class, $method_name)) return false;

            try {
                $method = new ReflectionMethod($crypto_class, $method_name);
            } catch(\ReflectionException) {
                return false;
            }

            if(!$method->isPublic() || !$method->isStatic()) return false;

            $parameters = $method->getParameters();
            if(count($parameters) !== 2) return false;

            foreach($parameters as $parameter) {
                $type = $parameter->getType();
                if($type !== null && !self::typeAcceptsString($type)) return false;
            }
        }

        $encrypt = new ReflectionMethod($crypto_class, 'encrypt');
        if(!self::typeIsString($encrypt->getReturnType())) return false;

        $decrypt = new ReflectionMethod($crypto_class, 'decrypt');
        if(!self::typeIsStringOrBool($decrypt->getReturnType())) return false;

        return true;
    }

    private static function typeAcceptsString(ReflectionType $type): bool
    {
        if($type instanceof ReflectionNamedType) return $type->getName() === 'string';

        if($type instanceof ReflectionUnionType) {
            foreach($type->getTypes() as $union_type) {
                if($union_type instanceof ReflectionNamedType && $union_type->getName() === 'string') return true;
            }
        }

        return false;
    }

    private static function typeIsString(?ReflectionType $type): bool
    {
        return $type instanceof ReflectionNamedType && $type->getName() === 'string';
    }

    private static function typeIsStringOrBool(?ReflectionType $type): bool
    {
        if(!$type instanceof ReflectionUnionType) return false;

        $types = [];
        foreach($type->getTypes() as $union_type) {
            if(!$union_type instanceof ReflectionNamedType) return false;
            $types[] = $union_type->getName();
        }

        sort($types);

        return $types === ['bool', 'string'];
    }

    private static function formatResult(mixed $result): mixed
    {
        $return_type = self::getOption('return_type');
        if($return_type instanceof ReturnTypes) $return_type = $return_type->value;

        return match($return_type) {
            ReturnTypes::VALUE->value => is_array($result) && array_is_list($result) ? ($result[0] ?? false) : $result,
            ReturnTypes::OBJECT->value => is_array($result) && array_is_list($result)
                ? array_map(static fn(array $item) => (object) $item, $result)
                : (object) $result,
            ReturnTypes::JSON->value => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => $result,
        };
    }
}
