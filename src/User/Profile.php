<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Enums\{ UserTables, Domains };
use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\User\{ ProfileFetchHelper, Emails, Usernames };
use PDO, Exception, stdClass;

class Profile {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = UserTables::UserProfiles->value;

    /**
     * Insert a new user profile into the database.
     *
     * @param int|null $user_id The user ID, can be null if not provided.
     * @param string|null $title The title of the user, can be null if not provided.
     * @param string|null $first_name The first name of the user, can be null if not provided.
     * @param string|null $middle_name The middle name of the user, can be null if not provided.
     * @param string|null $last_name The last name of the user, can be null if not provided.
     * @param string|null $birthdate The birthdate timestamp of the user, can be null if not provided.
     * @param string|null $sex
     * @param string|null $gender
     * @param string|null $pronouns
     * @param string|null $short_biography A short biography of the user, can be null if not provided.
     * @param string|null $long_biography A long biography of the user, can be null if not provided.
     * @return bool Returns true on success, false on failure.
     * @throws Exception If the database connection is not set.
     */
    public static function Insert(
        ?int $user_id = null,
        ?string $title = null,
        ?string $first_name = null,
        ?string $middle_name = null,
        ?string $last_name = null,
        ?string $birthdate = null,
        ?string $sex = null,
        ?string $gender = null,
        ?string $pronouns = null,
        ?string $short_biography = null,
        ?string $long_biography = null,
        ?string $avatar_image = null,
        ?string $cover_image = null,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $sql = 'insert into '.self::$userTable.' (user_id, title, first_name, middle_name, last_name, birthdate, sex, gender, pronouns, short_biography, long_biography, avatar_image, cover_image) values (:user_id, :title, :first_name, :middle_name, :last_name, :birthdate, :sex, :gender, :pronouns, :short_biography, :long_biography, :avatar_image, :cover_image)';
        $prep = $pdo->prepare($sql);
        $result = $prep->execute([
            'user_id' => $user_id,
            'title' => $title,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'birthdate' => $birthdate,
            'sex' => $sex,
            'gender' => $gender,
            'pronouns' => $pronouns,
            'short_biography' => $short_biography,
            'long_biography' => $long_biography,
            'avatar_image' => $avatar_image,
            'cover_image' => $cover_image
        ]);
        if($result) self::setLastAffectedId($pdo->lastInsertId());
        return $result;
    }

    public static function Fetch(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null) {
        return self::$user = new ProfileFetchHelper($userQuery, $fetchMethod, $pdo);
    }

    /**
     * Update a user profile. Only updates columns if argument is specified (even as null).
     */
    public static function Update(
        int $user_id,
        $title = self::UNSET,
        $first_name = self::UNSET,
        $middle_name = self::UNSET,
        $last_name = self::UNSET,
        $birthdate = self::UNSET,
        $sex = self::UNSET,
        $gender = self::UNSET,
        $pronouns = self::UNSET,
        $short_biography = self::UNSET,
        $long_biography = self::UNSET,
        $avatar_image = self::UNSET,
        $cover_image = self::UNSET,
        ?PDO $pdo = null
    ): bool {
        $pdo = self::getDatabase($pdo);
        $fields = [];
        $params = ['user_id' => $user_id];
        if($title !== self::UNSET) {
            $fields[] = 'title = :title';
            $params['title'] = $title;
        }
        if($first_name !== self::UNSET) {
            $fields[] = 'first_name = :first_name';
            $params['first_name'] = $first_name;
        }
        if($middle_name !== self::UNSET) {
            $fields[] = 'middle_name = :middle_name';
            $params['middle_name'] = $middle_name;
        }
        if($last_name !== self::UNSET) {
            $fields[] = 'last_name = :last_name';
            $params['last_name'] = $last_name;
        }
        if($birthdate !== self::UNSET) {
            $fields[] = 'birthdate = :birthdate';
            $params['birthdate'] = $birthdate;
        }
        if($sex !== self::UNSET) {
            $fields[] = 'sex = :sex';
            $params['sex'] = $sex;
        }
        if($gender !== self::UNSET) {
            $fields[] = 'gender = :gender';
            $params['gender'] = $gender;
        }
        if($pronouns !== self::UNSET) {
            $fields[] = 'pronouns = :pronouns';
            $params['pronouns'] = $pronouns;
        }
        if($short_biography !== self::UNSET) {
            $fields[] = 'short_biography = :short_biography';
            $params['short_biography'] = $short_biography;
        }
        if($long_biography !== self::UNSET) {
            $fields[] = 'long_biography = :long_biography';
            $params['long_biography'] = $long_biography;
        }
        if($avatar_image !== self::UNSET) {
            $fields[] = 'avatar_image = :avatar_image';
            $params['avatar_image'] = $avatar_image;
        }
        if($cover_image !== self::UNSET) {
            $fields[] = 'cover_image = :cover_image';
            $params['cover_image'] = $cover_image;
        }
        
        if(empty($fields)) return false;
        $prep = $pdo->prepare('update '.self::$userTable.' set '.implode(', ', $fields).' where user_id = :user_id');
        $result = $prep->execute($params);

        if($result) self::setLastAffectedId($user_id);

        
        return $result;
    }

    /**
     * Delete a user profile by user_id.
     */
    public static function Delete(int|string $userQuery, ?PDO $pdo = null): bool {
        $pdo = self::getDatabase($pdo);
        $userID = SearchUserID::Search($userQuery, $pdo);
        if($userID === false) {
            throw new Exception('User not found with the provided query: ' . $userQuery);
            return false;
        }
        $prep = $pdo->prepare('delete from '.self::$userTable.' where user_id = :user_id');
        $result = $prep->execute(['user_id' => $userID]);
        if($result) self::setLastAffectedId($userID);
        return $result;
    }

    /**
     * Truncate the user_profiles table.
     */
    public static function truncateTable(?PDO $pdo = null): bool {
        self::$userTable = self::$userTable ?? UserTables::UserProfiles->value;
        $pdo = self::getDatabase($pdo);
        $prep = $pdo->prepare('truncate table '.self::$userTable);
        return $prep->execute();
    }
}
