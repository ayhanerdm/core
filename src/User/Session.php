<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Tools\SearchUserID;
use ayhanerdm\Core\Enums\UserOnlineStatuses;
use PDO, Exception;

class Session {
    public static function checkSession(PDO $pdo) {
        // If the user session isn't set then return false.
        if(!isset($_SESSION['user_session'])) return false;

        // If the user session is null then return false.
        if(is_null($_SESSION['user_session'])) return false;

        // If the user session is empty then return false.
        if(empty($_SESSION['user_session'])) return false;

        // Get the user session row from the database.
        $prep = $pdo->prepare('select * from user_sessions where session_id = :session_id');
                $prep->bindValue(':session_id', session_id(), PDO::PARAM_STR);

        $prep->execute();
        $SessionFromDatabase = $prep->fetch(PDO::FETCH_OBJ);

        // If the user session row isn't found then return false.
        if($SessionFromDatabase === false) return false;

        // If the user session row's session_id isn't equal to the current session_id then return false.
        if($SessionFromDatabase->session_id !== session_id()) return false;

        return $SessionFromDatabase->user_id;
    }

    public static function redirectIfNotLoggedIn(PDO $pdo, string $redirectUrl = 'https://auth.ayhanerdm.dynu.net/'): void {
        // If the user session isn't set then redirect to the login page.
        if(!isset($_SESSION['user_session'])) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // If the user session is null then redirect to the login page.
        if(is_null($_SESSION['user_session'])) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // If the user session is empty then redirect to the login page.
        if(empty($_SESSION['user_session'])) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Also check if the session exists in the database.
        $prep = $pdo->prepare('select * from user_sessions where id = :id');
        $prep->bindValue(':id', $_SESSION['user_session'], PDO::PARAM_INT);
        $prep->execute();
        $SessionFromDatabase = $prep->fetch(PDO::FETCH_OBJ);
        // If the session is not found in the database, redirect to the login page.
        if($SessionFromDatabase === false) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public static function errorIfNotLoggedIn(PDO $pdo): void {
        $isLoggedIn = false;

        // If the user session isn't set then throw an exception.
        if(!isset($_SESSION['user_session'])) $isLoggedIn = false;

        // If the user session is null then throw an exception.
        if(is_null($_SESSION['user_session'])) $isLoggedIn = false;

        // If the user session is empty then throw an exception.
        if(empty($_SESSION['user_session'])) $isLoggedIn = false;

        // Also check if the session exists in the database.
        $prep = $pdo->prepare('select * from user_sessions where id = :id');
        $prep->bindValue(':id', $_SESSION['user_session'], PDO::PARAM_INT);
        $prep->execute();

        $SessionFromDatabase = $prep->fetch(PDO::FETCH_OBJ);
        
        // If the session is not found in the database, throw an exception.
        if($SessionFromDatabase === false) $isLoggedIn = false;
        else $isLoggedIn = true;

        if(!$isLoggedIn) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => _('You need to be logged in to perform this action.')
            ]);
            exit;
        }
    }

    public static function createSession(int|string $userQuery, PDO $pdo): bool {
        $userID = SearchUserID::Search(userQuery: $userQuery, pdo: $pdo);

        if($userID === false) {
            throw new Exception('User not found with the provided query: ' . $userQuery);
            return false;
        }

        // id, user_id, session_id, ip_address, location, started_at
        $prep = $pdo->prepare('insert into user_sessions (user_id, session_id, ip_address, location) values (:user_id, :session_id, :ip_address, :location)');
        
        $NewSession = $prep->execute([
            'user_id' => $userID,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'location' => isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'Unknown')
        ]);

        if($NewSession === false) {
            throw new Exception('Failed to create a new session in database for user ID: ' . $userID);
            return false;
        }

        $_SESSION['user_session'] = $pdo->lastInsertId();

        // Update "online_status" and "last_online" in "user_accounts" table
        $updatePrep = $pdo->prepare('update user_accounts set online_status = :online_status, last_online = :time where user_id = :user_id');
        $updatePrep->bindValue(':user_id', $userID, PDO::PARAM_INT);
        $updatePrep->bindValue(':online_status', UserOnlineStatuses::ONLINE->value, PDO::PARAM_INT);
        $updatePrep->bindValue(':time', time(), PDO::PARAM_INT);
        $updateResult = $updatePrep->execute();

        if($updateResult === false) {
            throw new Exception('Failed to update online status for user ID: ' . $userID);
            return false;
        }

        if(isset($_SESSION['user_session'])) return $_SESSION['user_session'];

        return false;
    }

    // get session
    public static function getSession(PDO $pdo): ?object {
        // If the user session isn't set then return null.
        if(!isset($_SESSION['user_session'])) return null;

        // If the user session is null then return null.
        if(is_null($_SESSION['user_session'])) return null;

        // If the user session is empty then return null.
        if(empty($_SESSION['user_session'])) return null;

        // Get the user session row from the database.
        $prep = $pdo->prepare('select * from user_sessions where id = :id');
        $prep->bindValue(':id', $_SESSION['user_session'], PDO::PARAM_INT);
        $prep->execute();

        return $prep->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public static function deleteSession(PDO $pdo): bool {
        // If the user session isn't set then return false.
        if(!isset($_SESSION['user_session'])) return false;

        // If the user session is null then return false.
        if(is_null($_SESSION['user_session'])) return false;

        // If the user session is empty then return false.
        if(empty($_SESSION['user_session'])) return false;

        // Update "online_status" and "last_online" in "user_accounts" table
        $updatePrep = $pdo->prepare('update user_accounts set online_status = :online_status, last_online = :now where user_id = (select user_id from user_sessions where id = :id)');
                      $updatePrep->bindValue(':id', $_SESSION['user_session'], PDO::PARAM_INT);
                      $updatePrep->bindValue(':online_status', UserOnlineStatuses::OFFLINE->value, PDO::PARAM_INT);
                      $updatePrep->bindvalue(':now', time(), PDO::PARAM_INT);
        $updateResult = $updatePrep->execute();

        if($updateResult === false) {
            throw new Exception('Failed to update online status for user session ID: ' . $_SESSION['user_session']);
            return false;
        }

        // delete the user session from the database.
        $prep = $pdo->prepare('delete from user_sessions where id = :id');
        $prep->bindValue(':id', $_SESSION['user_session'], PDO::PARAM_INT);
        $DeletedSession = $prep->execute();

        if($DeletedSession === false) {
            throw new Exception('Failed to delete the session from the database for session ID: ' . $_SESSION['user_session']);
            return false;
        }

        unset($_SESSION['user_session']);

        return true;
    }

    public static function truncateSessionsTable(PDO $pdo): bool {
        // Truncate the user_sessions table.
        $prep = $pdo->prepare('truncate table user_sessions');
        $Truncated = $prep->execute();

        if($Truncated === false) {
            throw new Exception('Failed to truncate the user_sessions table.');
            return false;
        }

        unset($_SESSION['user_session']);

        return true;
    }
}