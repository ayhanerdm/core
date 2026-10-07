<?php
namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Enums\DataTypes;
use \ayhanerdm\Core\Enums\ReturnTypes;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Tools\Crypto;
use \ayhanerdm\Core\Exception\CustomException;
use \PDO;

class Attributes {
    // Use the ConnectsDatabase trait to handle database connections
    use \ayhanerdm\Core\Traits\ConnectsDatabaseBeta;

    public function __construct(?array $options = null) {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserAttributes->value);
    }

    public static function setAttr(array $options) { return self::setAttribute($options); }
    public static function setAttribute(array $options) {
        self::$options = array_merge(self::$options, $options);

        // Mantık: Tabloda attribute yoksa ekle, varsa güncelle.
        // name ve value olmak zorunda, type isteğe bağlı.
        // Peki data_type'ı ne yapacağız? Otomatik tespit etsek?

        if(self::getOption('user_query') === null || self::getOption('user_query') === '') {
            throw CustomException(
                message: 'user_uuid cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'user_query_missing',
            );
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));

        if(!$user_uuid) return false;

        if(self::getOption('name') === null || self::getOption('name') === '') {
            throw CustomException(
                message: 'Attibute name cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'attibute_name_missing',
            );
        }

        if(self::getOption('type') === '') self::setOption('type', null);

        // value bir json mı?
        if(gettype(self::getOption('value')) === 'string' && json_validate(self::getOption('value'))) $data_type = 'json';
        elseif(gettype(self::getOption('value')) === 'boolean') {
            if(self::getOption('value') === true) self::setOption('value', 1); $data_type = 'boolean';
            if(self::getOption('value') === false) self::setOption('value', 0); $data_type = 'boolean';
        }
        else $data_type = gettype(self::getOption('value'));

        // Attribute zaten var mı, kontrol edelim.
        self::$fetch_details['sql'] = 'select * from ' . self::getOption('table_name') . ' where user_uuid = :user_uuid and type = :type and name = :name';
        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
                $prep->execute([
                    'user_uuid' => $user_uuid,
                    'type' => self::getOption('type'),
                    'name' => self::getOption('name'),
                ]);

        // Kayıt yok, insert yapalım.
        if($prep->rowCount() === 0) {
            self::$fetch_details['sql'] = 'insert into ' . self::getOption('table_name') . ' set user_uuid = :user_uuid, type = :type, data_type = :data_type, name = :name, value = :value, created_at = :created_at';
            $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
            return $prep->execute([
                        'user_uuid' => $user_uuid,
                        'type' => self::getOption('type'),
                        'data_type' => $data_type,
                        'name' => self::getOption('name'),
                        'value' => self::getOption('value') ?? null,
                        'created_at' => time(),
                    ]);
        }

        // Kayıt var, value değerini güncelleyelim.
        if($prep->rowCount() > 0) {
            self::$fetch_details['sql'] = 'update ' . self::getOption('table_name') . ' set value = :value, data_type = :data_type, updated_at = :updated_at where user_uuid = :user_uuid and type = :type and name = :name';
            $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
            return $prep->execute([
                        'user_uuid' => $user_uuid,
                        'type' => self::getOption('type'),
                        'data_type' => $data_type,
                        'name' => self::getOption('name'),
                        'value' => self::getOption('value') ?? null,
                        'updated_at' => time(),
                    ]);
        }
    }

    public static function getAttr(array $options) { return self::getAttribute($options); }
    public static function getAttribute(array $options) {
        self::$options = array_merge(self::$options, $options);

        // Mantık: Tabloda attribute var mı kotrol et, varsa döndür, yoksa false döndür.

        if(self::getOption('user_query') === null || self::getOption('user_query') === '') {
            throw CustomException(
                message: 'user_uuid cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'user_query_missing',
            );
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));

        if(!$user_uuid) return false;

        if(self::getOption('name') === null || self::getOption('name') === '') {
            throw CustomException(
                message: 'Attibute name cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'attibute_name_missing',
            );
        }

        if(self::getOption('type') === '') self::setOption('type', null);

        // Attribute var mı, kontrol edelim.
        self::$fetch_details['sql'] = 'select * from ' . self::getOption('table_name') . ' where user_uuid = :user_uuid and type = :type and name = :name';
        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
        $execute = $prep->execute([
                    'user_uuid' => $user_uuid,
                    'type' => self::getOption('type'),
                    'name' => self::getOption('name'),
                ]);

        // Kayıt yok, false döndür.
        if(!$execute) return false;

        $result = $prep->fetch(PDO::FETCH_ASSOC);

        if(!$result) return null;

        foreach($result as $columnName => $value) {
            if($value !== null && self::getOption('time_format') !== null) {
                if(str_ends_with($columnName, '_at') && isUnixTimestamp($value)) $result[$columnName] = date(self::getOption('time_format'), $value);
            }
        }

        $data_type = DataTypes::tryFrom($result['data_type']);
        $result['value'] = match($data_type) {
            DataTypes::Json, DataTypes::JsonString => self::decodeJson($result['value']),
            DataTypes::Boolean, DataTypes::Bool => filter_var($result['value'], FILTER_VALIDATE_BOOLEAN),
            DataTypes::Integer, DataTypes::Int => (int) $result['value'],
            default => $result['value'],
        };

        // Mantık, options içinde return_type ile tüm satırı mı yoksa value değerini mi döndüreceğimizi belirleyelim.
        // Ama peki ya return_type yoksa? O zaman varsayılan olarak ne dönecek?
        $return_type = ReturnTypes::tryFrom(self::getOption('return_type'));

        if(is_array($result['value']) && $return_type === ReturnTypes::OBJECT) $result['value'] = (object) $result['value'];

        return match($return_type) {
            ReturnTypes::VALUE => $result['value'] ?? null,
            ReturnTypes::ARRAY => $result,
            ReturnTypes::OBJECT => (object) $result,
            ReturnTypes::JSON => json_encode($result, DEFAULT_JSON_FLAGS, 512),
            default => $result['value'] ?? null
        };
    }

    public static function deleteAttr(?array $options) { self::deleteAttribute($options); }
    public static function deleteAttribute(?array $options) {
        self::$options = array_merge(self::$options, $options);

        // Mantık: Tabloda attribute var mı kotrol et, varsa döndür, yoksa false döndür.

        if(self::getOption('user_query') === null || self::getOption('user_query') === '') {
            throw CustomException(
                message: 'user_uuid cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'user_query_missing',
            );
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));

        if(!$user_uuid) return false;

        if(self::getOption('name') === null || self::getOption('name') === '') {
            throw CustomException(
                message: 'Attibute name cannot be unset, null or empty.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'attibute_name_missing',
            );
        }

        if(self::getOption('type') === '') self::setOption('type', null);

        // Attribute var mı, kontrol edelim.
        self::$fetch_details['sql'] = 'select * from ' . self::getOption('table_name') . ' where user_uuid = :user_uuid and type = :type and name = :name';
        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
        $execute = $prep->execute([
                    'user_uuid' => $user_uuid,
                    'type' => self::getOption('type'),
                    'name' => self::getOption('name'),
                ]);

        if(!$execute) return false;

        if(self::getOption('delete_mode') !== null) {
            switch(self::getOption('delete_mode')) {
                case 'soft_delete': return self::softDelete(); break;
                case 'hard_delete': return self::hardDelete(); break;
                default: return self::softDelete();
            }
        }
    }

    // Helper functions
    private static function decodeJson(mixed $value): mixed
    {
        if(
            !is_string($value) ||
            !json_validate($value) ||
            self::getOption('decode_json_value') === false ||
            self::getOption('decode_json_value') === null
        ) return $value;

        return json_decode($value, true, 512, DEFAULT_JSON_FLAGS);
    }

    private static function softDelete(): bool {
        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));
        if(!$user_uuid) return false;

        // Şu tarihte silindi, diye güncelle.
        self::$fetch_details['sql'] = 'update ' . self::getOption('table_name') . ' set deleted_at = :deleted_at where user_uuid = :user_uuid and type = :type and name = :name';
        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
        $execute = $prep->execute([
                    'user_uuid' => $user_uuid,
                    'type' => self::getOption('type'),
                    'name' => self::getOption('name'),
                    'deleted_at' => time(),
                ]);

        if(!$execute) return false;

        return true;
    }

    private static function hardDelete(): bool {
        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));
        if(!$user_uuid) return false;

        // Şu tarihte silindi, diye güncelle.
        self::$fetch_details['sql'] = 'delete from ' . self::getOption('table_name') . ' where user_uuid = :user_uuid and type = :type and name = :name';
        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
        $execute = $prep->execute([
                    'user_uuid' => $user_uuid,
                    'type' => self::getOption('type'),
                    'name' => self::getOption('name'),
                ]);

        if(!$execute) return false;

        return true;
    }
}