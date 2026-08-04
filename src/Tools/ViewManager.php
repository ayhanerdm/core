<?php
namespace ayhanerdm\Core\Tools;

class ViewManager {
    public static ?string $viewPath = null;

    public static function setViewPath(string $path): bool {

        if(empty($path) || is_null($path)) {
            throw new \InvalidArgumentException('View path cannot be empty or null.');
            return false;
        }

        // Check if the path exists and is a directory
        if(!is_dir($path)) {
            throw new \InvalidArgumentException('The specified view path does not exist or is not a directory: '. $path);
            return false;
        }

        $path = realpath($path);
        
        if(substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        if($path === false) {
            throw new \RuntimeException('Failed to resolve the real path for: ' .$path);
            return false;
        }

        self::$viewPath = $path;

        return true;
    }

    public static function getViewPath(): false|string {
        if (is_null(self::$viewPath) || empty(self::$viewPath)) {
            throw new \RuntimeException('View path has not been set or empty, please set the view path using setViewPath() method.');
            return false;
        }

        return self::$viewPath;
    }
}