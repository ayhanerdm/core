<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Tools\{ SearchUserID, Domain };
use ayhanerdm\Core\Enums\{ Domains, UserTables };
use PDO, Exception, stdClass;

class ProfileFetchHelper {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static PDO $insertedPDO;

    private static string $userTable = 'user_profiles';
    private static ?string $inserted_user_uuid = null;

    public int $user_id = 0;
    public ?string $user_uuid = null;
    public ?string $title = null;
    public ?string $first_name = null;
    public ?string $middle_name = null;
    public ?string $last_name = null;
    public ?string $display_name = null;
    public ?string $email = null;
    public ?string $username = null;
    public ?string $handle = null; // Username or md5(user_id) if username is not set
    public ?string $profile_url = null;
    public ?string $avatar_url = null;
    public ?string $cover_url = null;
    public ?string $gravatar_url = null; // Gravatar URL based on email
    public ?string $sex = null;
    public ?string $gender = null;
    public ?string $pronouns = null;
    public ?string $short_biography = null;
    public ?string $long_biography = null;
    public ?string $born_at = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    /**
     * Fetch a profile row by userQuery (user_id, email, username, etc.).
     */
    public function __construct(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null) {
        self::$insertedPDO = $pdo = self::getDatabase($pdo);
        $user_uuid = SearchUserID::Search($userQuery, $pdo);
        if($user_uuid === false) return false; else self::$inserted_user_uuid = $user_uuid;
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid limit 1');
        $prep->execute(['user_uuid' => $user_uuid]);
        if($prep->rowCount() == 0) throw new \Exception('Profile not found.');
        $result = $prep->fetch($fetchMethod);
        $result = self::displayHelper($result, $pdo);
        $this->populateFromFetchResult($result);
    }

    public function getAvatarImage(bool $base64 = false): ?string {
        $prep = self::$insertedPDO->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid limit 1');
        $prep->execute(['user_uuid' => self::$inserted_user_uuid]);
        if($prep->rowCount() == 0) return null;
        $result = $prep->fetch(PDO::FETCH_OBJ);

        if($base64 && !empty($result->avatar_image)) {
            $mimeType = 'image/png'; // Adjust if you store mime type
            $base64Image = base64_encode($result->avatar_image);
            return "data:$mimeType;base64,$base64Image";
        }
        
        return $result->avatar_image ?? null;
    }

    public function getCoverImage(bool $base64 = false): ?string {
        $prep = self::$insertedPDO->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid limit 1');
        $prep->execute(['user_uuid' => self::$inserted_user_uuid]);
        if($prep->rowCount() == 0) return null;
        $result = $prep->fetch(PDO::FETCH_OBJ);

        if($base64 && !empty($result->cover_image)) {
            $mimeType = 'image/png'; // Adjust if you store mime type
            $base64Image = base64_encode($result->cover_image);
            return "data:$mimeType;base64,$base64Image";
        }

        return $result->cover_image ?? null;
    }

    private static function displayHelper(object|array $profile, ?PDO $db = null): object|array {
        $db = self::getDatabase($db);

        // Get default email
        $email = Emails::getDefaultEmail($profile->user_uuid ?? $profile['user_uuid'], null, $db);
        $username = Usernames::getDefaultUsername($profile->user_uuid ?? $profile['user_uuid'], null, $db);

        if(!is_object($profile) && !is_array($profile)) {
            throw new Exception('First argument of ' . __METHOD__ . ' must be an object or an array.');
        }
    
        if(is_object($profile)) {
            
            $profile->email = $email;
            $profile->username = $username;

            $profile->handle = !empty($username) ? $username : hash('sha256', $profile->user_uuid);

            $profile->profile_url = 'https://'. Domain::getDomain(). '/' .$profile->handle;
            $profile->avatar_url = 'https://'. Domain::getDomain().'/api/users/'. $profile->handle . '/avatar';
            $profile->cover_url = 'https://'. Domain::getDomain().'/api/users/'. $profile->handle . '/cover';
            $profile->gravatar_url = 'https://www.gravatar.com/avatar/'. md5(strtolower(trim($email)));
        }

        if(is_array($profile)) {


            $profile['email'] = $email;
            $profile['username'] = $username;

            $profile['handle'] = !empty($username) ? $username : hash('sha256', $profile['user_uuid']);

            $profile['profile_url'] = 'https://'. Domain::getDomain(). '/' .$profile['handle'];
            $profile['avatar_url'] = 'https://api.'. Domain::getDomain().'/users/'. $profile['handle'] . '/avatar';
            $profile['cover_url'] = 'https://api.'. Domain::getDomain().'/users/'. $profile['handle'] . '/cover';
            $profile['gravatar_url'] = 'https://www.gravatar.com/avatar/'. md5(strtolower(trim($email)));
        }

        return $profile;
    }

    /**
     * Populate this instance with fetch results, excluding avatar_image and cover_image.
     */
    public function populateFromFetchResult(object|array $result) {
        if(is_object($result)) {
            foreach(get_object_vars($result) as $key => $value) {
                if($key !== 'avatar_image' && $key !== 'cover_image') {
                    $this->$key = $value;
                }
            }
        } elseif(is_array($result)) {
            foreach($result as $key => $value) {
                if($key !== 'avatar_image' && $key !== 'cover_image') {
                    $this->$key = $value;
                }
            }
        }

        return $result;
    }
}