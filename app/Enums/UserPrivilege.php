<?php

namespace App\Enums;

enum UserPrivilege: int
{
    case Blocked = 0;
    case User1   = 1;
    case User2   = 2;
    case User3   = 3;
    case Admin   = 4;
    case Root    = 5;

    public function label(): string
    {
        return match ($this) {
            self::Blocked => 'Zablokowany',
            self::User1   => 'Pracownik',
            self::User2   => 'Pracownik+',
            self::User3   => 'Pracownik++',
            self::Admin   => 'Administrator',
            self::Root    => 'Superadmin',
        };
    }

    public function isAtLeast(self $level): bool
    {
        return $this->value >= $level->value;
    }

    public function isAdmin(): bool
    {
        return $this->isAtLeast(self::Admin);
    }

    public function canAddProducts(): bool
    {
        return $this->isAtLeast(self::User2);
    }

    public function canChangeTax(): bool
    {
        return $this->isAtLeast(self::User3);
    }
}