<?php
namespace ayhanerdm\Core\User;

use ayhanerdm\Core\Tools\SearchUserID;

class DirectoryImages {
    public static string $imageType = 'avatar';
    public static string $imageDir;
    public static string $imagePath;

    public static function setImageDir(string $imageDir) {
        $realPath = realpath($imageDir);

        if($realPath === false) {
            throw new \InvalidArgumentException("Invalid image directory: $imageDir");
            return false;
        }

        self::$imageDir = $realPath;
    }

    public static function getAvatar(string $userQuery, ?int $size = null, ?\PDO $pdo = null) {
        return self::getImage($userQuery, 'avatar', $size, $pdo);
    }

    public static function getCover(string $userQuery, ?int $size = null, ?\PDO $pdo = null) {
        return self::getImage($userQuery, 'cover', $size, $pdo);
    }

    public static function getImage(string $userQuery, string $imageType = 'avatar', ?int $size = null, ?\PDO $pdo = null) {
        
        // Determine the user ID
        if($pdo === null) $user_id = $userQuery;
        else $user_id = SearchUserID::Search($userQuery, $pdo);

        // Return false if user ID is false
        if($user_id === false) return false;

        // Use the user_id as is if it's already an MD5 hash, otherwise hash it
        $hashedUserID = (preg_match('/^[a-f0-9]{32}$/i', $user_id)) ? $user_id : md5($user_id);

        // Set the image path based on the hashed user ID and image type
        $userDir = self::$imageDir . '/' . $hashedUserID;
        $imageFile = $imageType . '.png';

        if(!is_null($size)) {
            // $size cannot be less than 200
            if($size < 200) {
                throw new \InvalidArgumentException("Size must be at least 200 pixels.");
                return false;
            }

            $sizedImageFile = $imageType . '_' . $size . '.png';

            if(file_exists($userDir . '/' . $sizedImageFile)) {
                $imageFile = $sizedImageFile;
            }
        }

        self::$imagePath = $userDir . '/' . $imageFile;

        // Return false if the image file does not exist
        if(!file_exists(self::$imagePath)) return false;

        // Check if the image file is readable
        if(!is_readable(self::$imagePath)) return false;

        // Open the image file for reading
        $open = fopen(self::$imagePath, 'rb');

        // Return false if the file could not be opened
        if(!$open) return false;

        // Read the image data from the file
        $imageData = fread($open, filesize(self::$imagePath));

        // Close the file handle
        fclose($open);

        // Output the image data
        return $imageData;
    }

    public static function getDefaultImage(string $imageType = 'avatar') {
        // Set the default image path based on the image type
        $defaultImagePath = self::$imageDir . '/default/' . $imageType . '.png';

        // Return false if the default image file does not exist
        if(!file_exists($defaultImagePath)) return false;

        // Check if the default image file is readable
        if(!is_readable($defaultImagePath)) return false;

        // Open the default image file for reading
        $open = fopen($defaultImagePath, 'rb');

        // Return false if the file could not be opened
        if(!$open) return false;

        // Read the default image data from the file
        $imageData = fread($open, filesize($defaultImagePath));

        // Close the file handle
        fclose($open);

        // Output the default image data
        return $imageData;
    }
}