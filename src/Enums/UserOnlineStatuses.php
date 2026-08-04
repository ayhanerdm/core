<?php
namespace ayhanerdm\Core\Enums {
    Enum UserOnlineStatuses: int {
        case OFFLINE = 0;
        case ONLINE = 1;
        case AWAY = 2;
        case BUSY = 3;
        case DO_NOT_DISTURB = 4;
        case INVISIBLE = 5;
        case UNKNOWN = 6;
        case UNSET = 7;

        public function getStatusString(): string
        {
            return match ($this) {
                self::OFFLINE => 'Offline',
                self::ONLINE => 'Online',
                self::AWAY => 'Away',
                self::BUSY => 'Busy',
                self::DO_NOT_DISTURB => 'Do Not Disturb',
                self::INVISIBLE => 'Invisible',
                self::UNKNOWN => 'Unknown Status',
                self::UNSET => 'Unset Status',
            };
        }

        public function getStatusInt(): int {
            return $this->value;
        }

        public function getStatusClass(): string {
            return match ($this) {
                self::OFFLINE => 'user-offline',
                self::ONLINE => 'user-online',
                self::AWAY => 'user-away',
                self::BUSY => 'user-busy',
                self::DO_NOT_DISTURB => 'user-do-not-disturb',
                self::INVISIBLE => 'user-invisible',
                self::UNKNOWN => 'user-status-unknown',
                self::UNSET => 'user-status-unknown',
            };
        }
    }
}