<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\{ 
    Tools\SearchUserID,
    Tools\TurkishIdentityValidator,
    Enums\UserTables,
    Enums\UserOnlineStatuses,
    Enums\Domains,
};
use PDO, SensitiveParameter;
use stdClass;

class Account {
    // Use the ConnectsDatabase trait to handle database connections
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    // Set the table name here.
    private static string $userTable = UserTables::UserAccounts->value;

    /**
     * Insert a new user account into the database.
     *
     * @param int|null $user_id The user ID, can be null if not provided.
     * @param int|null $tg_id The Turkish Identity Number, must be 11 digits, can be null if not provided.
     * @param string|null $password The password for the user, can be null if not provided.
     * @return bool Returns true on success, false on failure.
     * @throws \Exception If the database connection is not set or if the input types are invalid.
     */
    public static function Insert(
        ?int $user_id = null,
        #[\SensitiveParameter] ?int $tg_id = null, // Must be 11 digits
        #[\SensitiveParameter] ?string $email = null,
        #[\SensitiveParameter] ?string $phone = null,
        ?string $username = null,
        #[\SensitiveParameter] ?string $password = null,
        ?int $registered_at = null,
        ?UserOnlineStatuses $online_status = null,
        ?string $last_online = null,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        // Ensure input types are valid
        if(!is_null($user_id) && !is_int($user_id)) throw new \Exception('user_id must be int or null.');
        if(!is_null($tg_id) && !is_int($tg_id)) throw new \Exception('tg_id must be int or null.');
        if(!is_null($password) && !is_string($password)) throw new \Exception('password must be string or null.');
        // Validate Turkish Identity Number format if provided
        if(!is_null($tg_id) && !TurkishIdentityValidator::isValidFormat((string)$tg_id)) {
            throw new \Exception('tg_id must be 11 digits and valid.');
        }
        // Check for duplicate Turkish Identity Number
        if(!is_null($tg_id) && SearchUserID::Search($tg_id, $pdo) !== false) {
            throw new \Exception('Turkish Identity Number <b>'.$tg_id.'</b> already exists.');
            return false;
        }
        // Check for duplicate email
        if(!is_null($email) && SearchUserID::Search($email, $pdo) !== false) {
            throw new \Exception('Email <b>'.$email.'</b> already exists.');
            return false;
        }
        // Check for duplicate phone
        if(!is_null($phone) && SearchUserID::Search($phone, $pdo) !== false) {
            throw new \Exception('Phone number <b>'.$phone.'</b> already exists.');
            return false;
        }
        // Check for duplicate username
        if(!is_null($username) && SearchUserID::Search($username, $pdo) !== false) {
            throw new \Exception('Username <b>'.$username.'</b> already exists.');
            return false;
        }
        // Hash password if provided
        $hashedPassword = $password ? password_hash($password, PASSWORD_BCRYPT, ['cost'=>13]) : null;
        // Set registration date to current timestamp if not provided
        $registered_at = $registered_at ?? time();
        // Set default online status if not provided
        $online_status = $online_status?->getStatusInt() ?? UserOnlineStatuses::OFFLINE->getStatusInt();
        // Set last_online to null if not provided
        $last_online = $last_online ?? null;
        // Prepare and execute insert statement
        $sql = 'insert into '. self::$userTable .' (user_id, tg_id, email, phone, username, password, registered_at, online_status, last_online) '.
               'values (:user_id, :tg_id, :email, :phone, :username, :password, :registered_at, :online_status, :last_online)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'tg_id' => $tg_id,
            'email' => $email,
            'phone' => $phone,
            'username' => $username,
            'password' => $hashedPassword,
            'registered_at' => $registered_at,
            'online_status' => $online_status,
            'last_online' => $last_online,
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        return $result;
    }

    /**
     * Fetch an account row by userQuery (user_id, email, username, etc.).
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

        if($result === false) return false;

        self::$user = $result;

        return $result;
    }

    public static function Update(
        int $user_id,
        #[\SensitiveParameter] $tg_id = self::UNSET,
        ?string $email = self::UNSET,
        ?string $phone = self::UNSET,
        ?string $username = self::UNSET,
        #[\SensitiveParameter] $password = self::UNSET,
        $last_online = self::UNSET,
        \ayhanerdm\Enums\UserOnlineStatuses|int|null $online_status = self::UNSET,
        $registered_at = self::UNSET,
        $updated_at = self::UNSET,
        $deleted_at = self::UNSET,

        ?PDO $pdo = null
    ): bool {
        // Set the user table name from enum or selected table
        self::$userTable = self::$userTable ?? UserTables::UserAccounts->value;
        // Resolve the PDO connection
        $pdo = self::getDatabase($pdo);
        // Validate user_id type
        if(!is_int($user_id)) throw new \Exception('user_id must be int.');
        // Prepare fields and params for dynamic update
        $fields = [];
        $params = ['user_id' => $user_id];
        // Only update tg_id if explicitly provided (including null)
        if($tg_id !== self::UNSET) {
            $fields[] = 'tg_id = :tg_id';
            $params['tg_id'] = $tg_id;
        }
        // Only update email if explicitly provided (including null)
        if($email !== self::UNSET) {
            $fields[] = 'email = :email';
            $params['email'] = $email;
        }
        // Only update phone if explicitly provided (including null)
        if($phone !== self::UNSET) {
            $fields[] = 'phone = :phone';
            $params['phone'] = $phone;
        }
        // Only update username if explicitly provided (including null)
        if($username !== self::UNSET) {
            $fields[] = 'username = :username';
            $params['username'] = $username;
        }
        // Only update password if explicitly provided (including null)
        if($password !== self::UNSET) {
            $fields[] = 'password = :password';
            // Hash password if not null, otherwise set to null
            $params['password'] = $password !== null ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]) : null;
        }

        // Only update last_online if explicitly provided
        if($last_online !== self::UNSET) {
            $fields[] = 'last_online = :last_online';
            $params['last_online'] = $last_online;
        }
        
        // Only update online_status if explicitly provided
        if($online_status !== self::UNSET) {
            $fields[] = 'online_status = :online_status';
            // If online_status is an enum, get its int value, else use as is
            if($online_status instanceof \ayhanerdm\Enums\UserOnlineStatuses) {
                $params['online_status'] = $online_status->getStatusInt();
            } else {
                $params['online_status'] = $online_status;
            }
        }

        // Only update registered_at if explicitly provided
        if($registered_at !== self::UNSET) {
            $fields[] = 'registered_at = :registered_at';
            $params['registered_at'] = $registered_at;
        }
        
        // Only update updated_at if explicitly provided
        if($updated_at !== self::UNSET) {
            $fields[] = 'updated_at = :updated_at';
            $params['updated_at'] = $updated_at;
        } else {
            // If updated_at is not provided, set it to current timestamp
            $fields[] = 'updated_at = :updated_at';
            $params['updated_at'] = time();
        }

        // Only update deleted_at if explicitly provided
        if($deleted_at !== self::UNSET) {
            $fields[] = 'deleted_at = :deleted_at';
            $params['deleted_at'] = $deleted_at;
        }
        
        // If no fields to update, return false
        if(empty($fields)) return false;
        // Build SQL update statement dynamically
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where user_id = :user_id');
        $result = $prep->execute($params);
        if($result) self::setLastAffectedId($user_id);
        return $result;
    }

    public static function Delete(int|string $userQuery, ?PDO $pdo = null): bool {
        // Resolve the PDO connection
        $pdo = self::getDatabase($pdo);

        $userId = SearchUserID::Search($userQuery, $pdo);

        if($userId === false) return false;

        $prep = $pdo->prepare('delete from '. self::$userTable .' where user_id = :user_id');
        $result = $prep->execute(['user_id' => $userId]);

        if($result) self::setLastAffectedId($userId);
        
        return $result;
    }

    public static function verifyPassword(string $password): bool {
        return password_verify($password, self::$user->password);
    }

    public static function addTrigger(?PDO $pdo = null) {
        $dropFirstTrigger = 'DROP TRIGGER IF EXISTS `BeforeUserAccountsInsert`;';
        $beforeTrigger = 'CREATE TRIGGER `BeforeUserAccountsInsert` BEFORE INSERT ON `user_accounts`
FOR EACH ROW
BEGIN
    -- Eğer eklenecek kayıttaki username NULL ise, rastgele bir kullanıcı adı ata
    -- Bu atama doğrudan NEW.username e yapıldığı için tabloya kaydedilecektir.
    IF NEW.username IS NULL THEN
        SET NEW.username = substring(MD5(RAND()),1,8);
    END IF;
END;';
        $dropSecondTrigger = 'DROP TRIGGER IF EXISTS `AddUserRelatedTables`;';
        $afterTrigger = 'CREATE TRIGGER `AddUserRelatedTables` AFTER INSERT ON `user_accounts`
FOR EACH ROW
BEGIN
    -- 1. user_profiles tablosuna yeni bir profil kaydı ekle
    INSERT INTO user_profiles (user_id)
    VALUES (NEW.user_id);
    
    -- 2. user_wallets tablosuna yeni bir cüzdan kaydı ekle
    INSERT INTO user_wallets (user_id)
    VALUES (NEW.user_id);
    
    -- 3. user_usernames tablosuna yeni bir kullanıcı adı kaydı ekle
    -- NEW.username artık asla NULL olmayacak (BeforeUserAccountsInsert triggerı tarafından doldurulmuş olabilir)
    INSERT INTO user_usernames (user_id, username, is_default)
    VALUES (NEW.user_id, NEW.username, 1);
    
    -- 4. user_emails tablosuna yeni bir kullanıcı kaydı ekle
    INSERT INTO user_emails (user_id, email, is_default)
    VALUES (NEW.user_id, NEW.email, 1);
    
    -- 5. user_phones tablosuna yeni bir kullanıcı kaydı ekle
    INSERT INTO user_phones (user_id, phone, is_default)
    VALUES (NEW.user_id, NEW.phone, 1);
END;';

        // Resolve the PDO connection
        $pdo = self::getDatabase($pdo);
        // Drop the first trigger if it exists
        $dropFirst = $pdo->exec($dropFirstTrigger);
        $createFirstTrigger = $pdo->exec($beforeTrigger);
        $dropSecond = $pdo->exec($dropSecondTrigger);
        $createSecondTrigger = $pdo->exec($afterTrigger);
        if($dropFirst === false || $createFirstTrigger === false || $dropSecond === false || $createSecondTrigger === false) {
            throw new \Exception('Failed to create triggers.');
        }
    }
}