<?php
namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
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
        self::$options = array_merge($options, self::$options);

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
        self::$options = array_merge($options, self::$options);

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

        foreach($result as $columnName => $value) {
            if($value !== null && self::getOption('time_format') !== null) {
                if(str_ends_with($columnName, '_at') && isUnixTimestamp($value)) $result[$columnName] = date(self::getOption('time_format'), $value);
            }
        }

        if($result['data_type'] === 'json' && json_validate($result['value'])) {
            if(self::getOption('decode_json_value_as_array') === true) $result['value'] = json_decode($result['value'], true, 512, DEFAULT_JSON_FLAGS);
            if(self::getOption('decode_json_value_as_object') === true) $result['value'] = json_decode($result['value'], false, 512, DEFAULT_JSON_FLAGS);
        }

        // Mantık, options içinde return_type ile tüm satırı mı yoksa value değerini mi döndüreceğimizi belirleyelim.
        // Ama peki ya return_type yoksa? O zaman varsayılan olarak ne dönecek?
        return match(self::getOption('return_type') ?? null) {
            'return_value', 'value' => $result['value'] ?? null,
            'return_array', 'array' => $result,
            'return_object', 'object' => (object) $result,
            'json_string', 'json' => json_encode($result, DEFAULT_JSON_FLAGS, 512),
            default => $result['value'] ?? null
        };
    }
}