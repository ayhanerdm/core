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
        ?string $provider_email = null,
        array|string|null $meta_data = null,
        string $visibility = 'private',
        bool $is_default = false
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

        if($provider_username !== null && $provider_username === '') $provider_username = null;
        if($provider_email !== null && $provider_email === '') $provider_email = null;

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

        $crypto_class = self::getCryptoClass();
        $app_secret = self::getOption('app_secret');

        if(!is_string($app_secret) || $app_secret === '') {
            throw new CustomException('Social verilerinin şifrelenmesi için app_secret gereklidir.', null, 'missing_app_secret');
        }

        $encrypted_username = $provider_username === null
            ? null
            : $crypto_class::encrypt($provider_username, $app_secret);

        $encrypted_email = $provider_email === null
            ? null
            : $crypto_class::encrypt($provider_email, $app_secret);

        $encrypted_meta_data = $meta_data === null
            ? null
            : $crypto_class::encrypt($meta_data, $app_secret);

        if(($provider_username !== null && !is_string($encrypted_username))
            || ($provider_email !== null && !is_string($encrypted_email))
            || ($meta_data !== null && !is_string($encrypted_meta_data))) {
            throw new CustomException('Social verileri şifrelenemedi.', null, 'social_encryption_failed');
        }

        $username_bindex = $provider_username === null ? null : self::blindIndex($provider_username, $app_secret);
        $email_bindex = $provider_email === null ? null : self::blindIndex($provider_email, $app_secret);

        $transaction_started = false;

        try {
            if(!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $transaction_started = true;
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
                $owner_sql = 'SELECT user_uuid FROM ' . self::getOption('table_name') . ' WHERE id = :id LIMIT 1';
                $owner_prep = $pdo->prepare($owner_sql);
                $owner_prep->execute(['id' => $existing['id']]);
                $existing_owner = $owner_prep->fetchColumn();

                if($existing_owner !== $user_uuid) {
                    throw new CustomException(
                        'Bu sosyal hesap başka bir kullanıcıya bağlı.',
                        null,
                        'social_account_already_linked'
                    );
                }

                if($is_default) {
                    $default_sql = 'UPDATE ' . self::getOption('table_name') . '
                                     SET is_default = 0
                                     WHERE user_uuid = :user_uuid
                                       AND provider = :provider
                                       AND is_default = 1';

                    self::$fetch_details['sql'] = $default_sql;

                    $default_prep = $pdo->prepare($default_sql);
                    $default_prep->execute([
                        'user_uuid' => $user_uuid,
                        'provider' => $provider,
                    ]);
                }

                $sql = 'UPDATE ' . self::getOption('table_name') . '
                        SET provider_username = :provider_username,
                            provider_username_bindex = :provider_username_bindex,
                            provider_email = :provider_email,
                            provider_email_bindex = :provider_email_bindex,
                            meta_data = :meta_data,
                            visibility = :visibility,
                            is_default = :is_default,
                            updated_at = :updated_at,
                            deleted_at = NULL
                        WHERE id = :id';

                self::$fetch_details['sql'] = $sql;

                $prep = $pdo->prepare($sql);
                $prep->execute([
                    'provider_username' => $encrypted_username,
                    'provider_username_bindex' => $username_bindex,
                    'provider_email' => $encrypted_email,
                    'provider_email_bindex' => $email_bindex,
                    'meta_data' => $encrypted_meta_data,
                    'visibility' => $visibility,
                    'is_default' => $is_default ? 1 : 0,
                    'updated_at' => $timestamp,
                    'id' => $existing['id'],
                ]);

                if($transaction_started) $pdo->commit();

                return (int) $existing['id'];
            }

            if($is_default) {
                $default_sql = 'UPDATE ' . self::getOption('table_name') . '
                                 SET is_default = 0
                                 WHERE user_uuid = :user_uuid
                                   AND provider = :provider
                                   AND is_default = 1';

                self::$fetch_details['sql'] = $default_sql;

                $default_prep = $pdo->prepare($default_sql);
                $default_prep->execute([
                    'user_uuid' => $user_uuid,
                    'provider' => $provider,
                ]);
            }

            $sql = 'INSERT INTO ' . self::getOption('table_name') . '
                    (user_uuid, provider, provider_user_id, provider_username, provider_username_bindex, provider_email, provider_email_bindex, meta_data, visibility, is_default, created_at, updated_at, deleted_at)
                    VALUES (:user_uuid, :provider, :provider_user_id, :provider_username, :provider_username_bindex, :provider_email, :provider_email_bindex, :meta_data, :visibility, :is_default, :created_at, :updated_at, NULL)';

            self::$fetch_details['sql'] = $sql;

            $prep = $pdo->prepare($sql);
            $prep->execute([
                'user_uuid' => $user_uuid,
                'provider' => $provider,
                'provider_user_id' => $provider_user_id,
                'provider_username' => $encrypted_username,
                'provider_username_bindex' => $username_bindex,
                'provider_email' => $encrypted_email,
                'provider_email_bindex' => $email_bindex,
                'meta_data' => $encrypted_meta_data,
                'visibility' => $visibility,
                'is_default' => $is_default ? 1 : 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $social_id = (int) $pdo->lastInsertId();

            if($transaction_started) $pdo->commit();

            return $social_id;
        } catch(\Throwable $exception) {
            if($transaction_started && $pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function set(
        string $provider,
        string $provider_user_id,
        ?string $provider_username = null,
        ?string $provider_email = null,
        array|string|null $meta_data = null,
        string $visibility = 'private',
        bool $is_default = false
    ): int|false {
        return self::setSocial($provider, $provider_user_id, $provider_username, $provider_email, $meta_data, $visibility, $is_default);
    }

    public static function getSocial(
        string|int|null $social_id = null,
        ?string $provider = null,
        ?string $provider_user_id = null,
        ?string $provider_username = null,
        ?string $provider_email = null,
        bool $include_deleted = false
    ): mixed {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $conditions = [];
        $parameters = [];

        if($social_id !== null) {
            $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
            if($user_uuid === false) return false;

            $conditions[] = 'id = :id';
            $conditions[] = 'user_uuid = :user_uuid';
            $parameters['id'] = $social_id;
            $parameters['user_uuid'] = $user_uuid;
        } else {
            $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
            if($user_uuid === false) return false;

            $conditions[] = 'user_uuid = :user_uuid';
            $parameters['user_uuid'] = $user_uuid;

            $conditions[] = 'is_default = 1';

            if($provider !== null) {
                $conditions[] = 'provider = :provider';
                $parameters['provider'] = $provider;
            }

            if($provider_user_id !== null) {
                $conditions[] = 'provider_user_id = :provider_user_id';
                $parameters['provider_user_id'] = $provider_user_id;
            }

            if($provider_username !== null) {
                $conditions[] = 'provider_username_bindex = :provider_username_bindex';
                $parameters['provider_username_bindex'] = self::blindIndex($provider_username, self::getAppSecret());
            }

            if($provider_email !== null) {
                $conditions[] = 'provider_email_bindex = :provider_email_bindex';
                $parameters['provider_email_bindex'] = self::blindIndex($provider_email, self::getAppSecret());
            }
        }

        if(!$include_deleted) $conditions[] = 'deleted_at IS NULL';

        $sql = 'SELECT * FROM ' . self::getOption('table_name') . '
                WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id DESC';

        $sql .= ' LIMIT 1';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute($parameters);

        $result = $social_id !== null
            ? $prep->fetch(PDO::FETCH_ASSOC)
            : $prep->fetchAll(PDO::FETCH_ASSOC);

        if($result === false || $result === []) return false;

        if($social_id !== null) {
            $result = self::decryptResult($result);
        } else {
            foreach($result as &$row) $row = self::decryptResult($row);
            unset($row);
        }

        return self::formatResult($result);
    }

    public static function getSocials(
        ?string $provider = null,
        bool $is_default = false,
        bool $include_deleted = false
    ): mixed {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        $conditions = ['user_uuid = :user_uuid'];
        $parameters = ['user_uuid' => $user_uuid];

        if($provider !== null) {
            $conditions[] = 'provider = :provider';
            $parameters['provider'] = $provider;
        }

        if($is_default) $conditions[] = 'is_default = 1';
        if(!$include_deleted) $conditions[] = 'deleted_at IS NULL';

        $sql = 'SELECT * FROM ' . self::getOption('table_name') . '
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY id DESC';

        self::$fetch_details['sql'] = $sql;

        $prep = $pdo->prepare($sql);
        $prep->execute($parameters);

        $result = $prep->fetchAll(PDO::FETCH_ASSOC);

        if($result === []) return false;

        foreach($result as &$row) $row = self::decryptResult($row);
        unset($row);

        return self::formatResult($result);
    }

    public static function get(
        string|int|null $social_id = null,
        ?string $provider = null,
        ?string $provider_user_id = null,
        ?string $provider_username = null,
        ?string $provider_email = null,
        bool $include_deleted = false
    ): mixed {
        return self::getSocial($social_id, $provider, $provider_user_id, $provider_username, $provider_email, $include_deleted);
    }

    public static function deleteSocial(string|int $social_id, bool $hard_delete = false): bool
    {
        $pdo = self::getOption('database_connection');

        if(!$pdo instanceof PDO) {
            throw new CustomException('Geçerli bir database_connection verilmedi.', null, 'invalid_database_connection');
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), $pdo);
        if($user_uuid === false) return false;

        if($hard_delete) {
            $sql = 'DELETE FROM ' . self::getOption('table_name') . '
                    WHERE id = :id AND user_uuid = :user_uuid';

            self::$fetch_details['sql'] = $sql;
            $prep = $pdo->prepare($sql);
            $prep->execute([
                'id' => $social_id,
                'user_uuid' => $user_uuid,
            ]);

            return $prep->rowCount() > 0;
        }

        $sql = 'UPDATE ' . self::getOption('table_name') . '
                SET deleted_at = :deleted_at, updated_at = :updated_at
                WHERE id = :id AND user_uuid = :user_uuid AND deleted_at IS NULL';

        self::$fetch_details['sql'] = $sql;

        $timestamp = (string) time();
        $prep = $pdo->prepare($sql);
        $prep->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $social_id,
            'user_uuid' => $user_uuid,
        ]);

        return $prep->rowCount() > 0;
    }

    public static function delete(string|int $social_id, bool $hard_delete = false): bool
    {
        return self::deleteSocial($social_id, $hard_delete);
    }

    private static function getAppSecret(): string
    {
        $app_secret = self::getOption('app_secret');

        if(!is_string($app_secret) || $app_secret === '') {
            throw new CustomException('Social verileri için app_secret gereklidir.', null, 'missing_app_secret');
        }

        return $app_secret;
    }

    private static function blindIndex(string $value, string $secret): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($value), 'UTF-8'), $secret);
    }

    private static function decryptResult(array $result): array
    {
        $crypto_class = self::getCryptoClass();
        $app_secret = self::getAppSecret();

        foreach(['provider_username', 'provider_email', 'meta_data'] as $field) {
            if($result[$field] === null) continue;

            $decrypted = $crypto_class::decrypt($result[$field], $app_secret);

            if($decrypted === false) {
                throw new CustomException($field . ' çözülemedi.', null, 'social_decryption_failed');
            }

            if($field === 'meta_data') {
                $decoded = json_decode($decrypted, true);

                if(json_last_error() !== JSON_ERROR_NONE) {
                    throw new CustomException('meta_data çözüldü ancak geçerli JSON değil.', null, 'invalid_social_metadata');
                }

                $result[$field] = $decoded;
            } else {
                $result[$field] = $decrypted;
            }
        }

        return $result;
    }

    private static function getCryptoClass(): string
    {
        $custom_crypto = self::getOption('crypto_class');

        if($custom_crypto !== null) {
            if(!is_string($custom_crypto) || !self::isCompatibleCryptoClass($custom_crypto)) {
                throw new CustomException('Verilen crypto_class beklenen API ile uyumlu değil.', null, 'invalid_crypto_class');
            }

            return $custom_crypto;
        }

        return Crypto::class;
    }

    private static function isCompatibleCryptoClass(string $crypto_class): bool
    {
        if(!class_exists($crypto_class)) return false;

        foreach(['encrypt', 'decrypt'] as $method_name) {
            if(!method_exists($crypto_class, $method_name)) return false;

            try { $method = new ReflectionMethod($crypto_class, $method_name); }
            catch(\ReflectionException) { return false; }

            if(!$method->isPublic() || !$method->isStatic()) return false;
            $parameters = $method->getParameters();
            if(count($parameters) !== 2) return false;

            foreach($parameters as $parameter) {
                $type = $parameter->getType();
                if($type !== null && !self::typeAcceptsString($type)) return false;
            }
        }

        if(!self::typeIsString((new ReflectionMethod($crypto_class, 'encrypt'))->getReturnType())) return false;
        if(!self::typeIsStringOrBool((new ReflectionMethod($crypto_class, 'decrypt'))->getReturnType())) return false;

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
