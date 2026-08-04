<?php
namespace ayhanerdm\Core\Enums {
    Enum UserTypes {
        case Admin;
        case Moderator;
        case Translator;
        case Developer;
        case Support;
        case User;

        public function getUserType(): string
        {
            return match ($this) {
                self::Admin => 'Administrator',
                self::Moderator => 'Moderator',
                self::Translator => 'Translator',
                self::Developer => 'Developer',
                self::Support => 'Support',
                self::User => 'User',
            };
        }
    }
}