<?php
namespace ayhanerdm\Core\User;
use ayhanerdm\Core\User\DirectoryImages;
use ayhanerdm\Core\User\DatabaseImages;

class Images {
    public static int $mode = self::DIR_MODE; // Default mode is directory

    const DIR_MODE = 1;
    const DB_MODE = 2;

    public static function setMode(int $mode) {
        if(!in_array($mode, ($modes = [self::DIR_MODE, self::DB_MODE]))) {
            throw new \InvalidArgumentException("Invalid file mode: $mode, choose either " . implode(' or ', $modes));
        }

        self::$mode = $mode;
    }

    public static function getImagesInstance() {
        if(self::$mode === self::DIR_MODE) {
            return new DirectoryImages();
        } elseif(self::$mode === self::DB_MODE) {
            return new DatabaseImages();
        } else {
            throw new \LogicException("Invalid mode set: " . self::$mode);
        }
    }
}