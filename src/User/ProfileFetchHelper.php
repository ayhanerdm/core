<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Tools\{ SearchUserID, Domain };
use ayhanerdm\Core\Enums\{ Domains, UserTables };
use PDO, Exception, stdClass;

class ProfileFetchHelper {
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    private static string $userTable = 'user_profiles';
    private static PDO $insertedPDO;
    private static int $insertedUserID = 0;

    public int $user_id = 0;
    public ?string $title = null;
    public ?string $first_name = null;
    public ?string $middle_name = null;
    public ?string $last_name = null;
    public ?string $display_name = null;
    public null|object|array $safe_display_name = null; // Contains 'public' and 'private' keys
    public ?string $email = null;
    public ?string $username = null;
    public ?string $handle = null; // Username or md5(user_id) if username is not set
    public ?string $profile_url = null;
    public ?string $avatar_url = null;
    public ?string $cover_url = null;
    public ?string $gravatar_url = null; // Gravatar URL based on email
    public ?string $birthdate = null;
    public ?int $birthdate_timestamp = null;
    public ?string $sex = null;
    public ?string $gender = null;
    public ?string $pronouns = null;
    public ?string $short_biography = null;
    public ?string $long_biography = null;

    /**
     * Fetch a profile row by userQuery (user_id, email, username, etc.).
     */
    public function __construct(int|string $userQuery, ?int $fetchMethod = null, ?PDO $pdo = null) {
        self::$insertedPDO = $pdo = self::getDatabase($pdo);
        $user_id = SearchUserID::Search($userQuery, $pdo);
        if($user_id === false) return false; else self::$insertedUserID = $user_id;
        if(is_null($fetchMethod)) $fetchMethod = self::$fetchMethod;
        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_id = :user_id limit 1');
        $prep->execute(['user_id' => $user_id]);
        if($prep->rowCount() == 0) throw new \Exception('Profile not found.');
        $result = $prep->fetch($fetchMethod);
        $result = self::displayHelper($result, $pdo);
        $this->populateFromFetchResult($result);
    }

    public function getAvatarImage(bool $base64 = false): ?string {
        $prep = self::$insertedPDO->prepare('select * from '.self::$userTable.' where user_id = :user_id limit 1');
        $prep->execute(['user_id' => self::$insertedUserID]);
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
        $prep = self::$insertedPDO->prepare('select * from '.self::$userTable.' where user_id = :user_id limit 1');
        $prep->execute(['user_id' => self::$insertedUserID]);
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
        $email = Emails::getDefaultEmail($profile->user_id ?? $profile['user_id'], null, $db);
        $username = Usernames::getDefaultUsername($profile->user_id ?? $profile['user_id'], null, $db);

        if(!is_object($profile) && !is_array($profile)) {
            throw new Exception('First argument of ' . __METHOD__ . ' must be an object or an array.');
        }

        $profile = self::displayNameHelper($profile);
    
        if(is_object($profile)) {
            
            $profile->email = $email;
            $profile->username = $username;

            $profile->handle = !empty($username) ? $username : md5($profile->user_id);

            $profile->profile_url = 'https://'. Domain::getDomain(). '/' .$profile->handle;
            $profile->avatar_url = 'https://'. Domain::getDomain().'/api/user/'. $profile->handle . '/avatar';
            $profile->cover_url = 'https://'. Domain::getDomain().'/api/user/'. $profile->handle . '/cover';
            $profile->gravatar_url = 'https://www.gravatar.com/avatar/'. md5(strtolower(trim($email))) . '?d=identicon';
        }

        if(is_array($profile)) {
            // If fetch method is FETCH_ASSOC, add display_name key
            $profile['display_name'] = trim(implode(' ', array_filter([$profile['first_name'], $profile['middle_name'], $profile['last_name']])));

            $profile['safe_public_display_name'] = null;
            if(!empty($profile['first_name']) || !empty($profile['middle_name']) || !empty($profile['last_name'])) {
                $profile['safe_public_display_name'] = trim(implode(' ', array_filter([
                    $profile['first_name'] ?? null,
                    $profile['middle_name'] ?? null,
                    $profile['last_name'] ?? null
                ])));
            } elseif(!empty($username)) {
                $profile['safe_public_display_name'] = $username;
            } elseif(!empty($email)) {
                $profile['safe_public_display_name'] = $email;
            } elseif(!empty($profile['user_id'])) {
                $profile['safe_public_display_name'] = md5($profile['user_id']);
            }

            $profile['email'] = $email;
            $profile['username'] = $username;

            $profile['handle'] = !empty($username) ? $username : md5($profile['user_id']);

            $profile['profile_url'] = 'https://'. Domain::getDomain(). '/' .$profile['handle'];
            $profile['avatar_url'] = 'https://api.'. Domain::getDomain().'/user/'. $profile['handle'] . '/avatar';
            $profile['cover_url'] = 'https://api.'. Domain::getDomain().'/user/'. $profile['handle'] . '/cover';
            $profile['gravatar_url'] = 'https://www.gravatar.com/avatar/'. md5(strtolower(trim($email)));
        }

        return $profile;
    }

    public static function displayNameHelper(object|array $profile): object|array {
        if(is_object($profile)) {
            // display_name is a combination of first_name, middle_name, and last_name
            $names = array_filter([
                $profile->first_name ?? null,
                $profile->middle_name ?? null,
                $profile->last_name ?? null
            ], fn($v) => !empty($v) );
            $profile->display_name = empty($names) ? null : trim(implode(' ', $names));

            // safe_public_display_name is a string that works if any of the names are empty and for public display
            $profile->safe_display_name = new stdClass();
            if(!empty($profile->first_name) || !empty($profile->middle_name) || !empty($profile->last_name)) {
                $profile->safe_display_name->public = trim(implode(' ', array_filter([
                    $profile->first_name ?? null,
                    $profile->middle_name ?? null,
                    $profile->last_name ?? null
                ])));
            } elseif(!empty($username)) {
                $profile->safe_display_name->public = $username;
            } elseif(!empty($profile->user_id)) {
                $profile->safe_display_name->public = md5($profile->user_id);
            }

            // safe_private_display_name is a string that works if any of the names are empty and for private display
            // It is used to show user themselves in private contexts
            $profile->safe_display_name->private = new stdClass();
            if(!empty($profile->first_name) || !empty($profile->middle_name) || !empty($profile->last_name)) {
                $profile->safe_display_name->private = trim(implode(' ', array_filter([
                    $profile->first_name ?? null,
                    $profile->middle_name ?? null,
                    $profile->last_name ?? null
                ])));
            } elseif(!empty($username)) {
                $profile->safe_display_name->private = $username;
            } elseif(!empty($email)) {
                $profile->safe_display_name->private = $email;
            } elseif(!empty($profile->user_id)) {
                $profile->safe_display_name->private = md5($profile->user_id);
            }
        }

        if(is_array($profile)) {
            // display_name is a combination of first_name, middle_name, and last_name
            $names = array_filter([
            $profile['first_name'] ?? null,
            $profile['middle_name'] ?? null,
            $profile['last_name'] ?? null
            ], fn($v) => !empty($v));
            $profile['display_name'] = empty($names) ? null : trim(implode(' ', $names));

            // safe_display_name is an array with 'public' and 'private' keys
            $profile['safe_display_name'] = [];

            if(!empty($profile['first_name']) || !empty($profile['middle_name']) || !empty($profile['last_name'])) {
            $profile['safe_display_name']['public'] = trim(implode(' ', array_filter([
                $profile['first_name'] ?? null,
                $profile['middle_name'] ?? null,
                $profile['last_name'] ?? null
            ])));
            } elseif(!empty($profile['username'])) {
            $profile['safe_display_name']['public'] = $profile['username'];
            } elseif(!empty($profile['user_id'])) {
            $profile['safe_display_name']['public'] = md5($profile['user_id']);
            }

            if(!empty($profile['first_name']) || !empty($profile['middle_name']) || !empty($profile['last_name'])) {
            $profile['safe_display_name']['private'] = trim(implode(' ', array_filter([
                $profile['first_name'] ?? null,
                $profile['middle_name'] ?? null,
                $profile['last_name'] ?? null
            ])));
            } elseif(!empty($profile['username'])) {
            $profile['safe_display_name']['private'] = $profile['username'];
            } elseif(!empty($profile['email'])) {
            $profile['safe_display_name']['private'] = $profile['email'];
            } elseif(!empty($profile['user_id'])) {
            $profile['safe_display_name']['private'] = md5($profile['user_id']);
            }
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