<?php

namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Enums\ReturnTypes;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Exception\CustomException;
use \ayhanerdm\Core\Traits\ConnectsDatabaseBeta;
use \PDO;

class UserSocials
{
    use ConnectsDatabaseBeta;

    public function __construct(?array $options = null)
    {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserSocials->value);
    }

    public static function setSocial(
        string $provider,
        string $provider_user_id,
        ?string $provider_username = null,
        array|string|null $meta_data = null,
        string $visibility = 'private'
    ): int|false {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        if($provider === '' || $provider_user_id === '') {
            throw new CustomException('provider ve provider_user_id boş bırakılamaz.', null, 'invalid_social_identity');
        }

        if($meta_data !== null && is_array($meta_data)) {
            $meta_data = json_encode($meta_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if($meta_data === false) {
                throw new CustomException('meta_data JSON olarak kodlanamadı.', null, 'invalid_social_metadata');
            }
        } elseif($meta_data !== null) {
            json_decode($meta_data);

            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new CustomException('meta_data geçerli bir JSON değil.', null, 'invalid_social_metadata');
            }
        }

        $sql = 'SELECT id FROM ' . self::getOption('table_name') . '
                WHERE provider = :provider
                  AND provider_user_id = :provider_user_id
                LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'provider' => $provider,
            'provider_user_id' => $provider_user_id,
        ]);

        $existing = $prep->fetch(PDO::FETCH_ASSOC);
        $timestamp = (string) time();

        if($existing !== false) {
            $sql = 'UPDATE ' . self::getOption('table_name') . '
                    SET user_uuid = :user_uuid,
                        provider_username = :provider_username,
                        meta_data = :meta_data,
                        visibility = :visibility,
                        updated_at = :updated_at,
                        deleted_at = NULL
                    WHERE id = :id';

            self::$fetch_details['sql'] = $sql;

            $prep = $pdo->prepare($sql);
            $prep->execute([
                'user_uuid' => $user_uuid,
                'provider_username' => $provider_username,
                'meta_data' => $meta_data,
                'visibility' => $visibility,
                'updated_at' => $timestamp,
                'id' => $existing['id'],
            ]);

            return (int) $existing['id'];
        }

        $sql = 'INSERT INTO ' . self::getOption('table_name') . '
                (user_uuid, provider, provider_user_id, provider_username, meta_data, visibility, created_at, updated_at, deleted_at)
                VALUES (:user_uuid, :provider, :provider_user_id, :provider_username, :meta_data, :visibility, :created_at, :updated_at, NULL)';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute([
            'user_uuid' => $user_uuid,
            'provider' => $provider,
            'provider_user_id' => $provider_user_id,
            'provider_username' => $provider_username,
            'meta_data' => $meta_data,
            'visibility' => $visibility,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function set(
        string $provider,
        string $provider_user_id,
        ?string $provider_username = null,
        array|string|null $meta_data = null,
        string $visibility = 'private'
    ): int|false {
        return self::setSocial($provider, $provider_user_id, $provider_username, $meta_data, $visibility);
    }

    public static function getSocial(
        string|int|null $social_id = null,
        ?string $provider = null,
        ?string $provider_user_id = null,
        ?string $provider_username = null,
        bool $include_deleted = false
    ): mixed {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $conditions = [];
        $parameters = [];

        if($social_id !== null) {
            $conditions[] = 'id = :id';
            $parameters['id'] = $social_id;
        } else {
            $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
            if($user_uuid === false) return false;

            $conditions[] = 'user_uuid = :user_uuid';
            $parameters['user_uuid'] = $user_uuid;

            if($provider !== null) {
                $conditions[] = 'provider = :provider';
                $parameters['provider'] = $provider;
            }

            if($provider_user_id !== null) {
                $conditions[] = 'provider_user_id = :provider_user_id';
                $parameters['provider_user_id'] = $provider_user_id;
            }

            if($provider_username !== null) {
                $conditions[] = 'provider_username = :provider_username';
                $parameters['provider_username'] = $provider_username;
            }
        }

        if(!$include_deleted) $conditions[] = 'deleted_at IS NULL';

        $sql = 'SELECT * FROM ' . self::getOption('table_name') . '
                WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id DESC';

        if($social_id !== null) $sql .= ' LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute($parameters);

        $result = $social_id !== null
            ? $prep->fetch(PDO::FETCH_ASSOC)
            : $prep->fetchAll(PDO::FETCH_ASSOC);

        if($result === false || $result === []) return false;

        return self::formatResult($result);
    }

    public static function get(
        string|int|null $social_id = null,
        ?string $provider = null,
        ?string $provider_user_id = null,
        ?string $provider_username = null,
        bool $include_deleted = false
    ): mixed {
        return self::getSocial($social_id, $provider, $provider_user_id, $provider_username, $include_deleted);
    }

    public static function deleteSocial(string|int $social_id, bool $hard_delete = false): bool
    {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        if($hard_delete) {
            $sql = 'DELETE FROM ' . self::getOption('table_name') . ' WHERE id = :id';
            self::$fetch_details['sql'] = $sql;
            $prep = $pdo->prepare($sql);
            $prep->execute(['id' => $social_id]);
            return $prep->rowCount() > 0;
        }

        $sql = 'UPDATE ' . self::getOption('table_name') . '
                SET deleted_at = :deleted_at, updated_at = :updated_at
                WHERE id = :id AND deleted_at IS NULL';

        self::$fetch_details['sql'] = $sql;

        $timestamp = (string) time();
        $prep = $pdo->prepare($sql);
        $prep->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $social_id,
        ]);

        return $prep->rowCount() > 0;
    }

    public static function delete(string|int $social_id, bool $hard_delete = false): bool
    {
        return self::deleteSocial($social_id, $hard_delete);
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
