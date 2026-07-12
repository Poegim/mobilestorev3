<?php

namespace App\Enums;

enum PaymentMethod: int
{
    case Cash     = 1;
    case Card     = 2;
    case Transfer = 3;
    case Allegro  = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash     => 'Gotówka',
            self::Card     => 'Karta',
            self::Transfer => 'Przelew',
            self::Allegro  => 'Allegro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash     => 'banknotes',
            self::Card     => 'credit-card',
            self::Transfer => 'building-library',
            self::Allegro  => 'shopping-bag',
        };
    }

    /**
     * Tailwind text color class for the payment icon.
     * Explicit strings so Tailwind detects them at build time.
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Cash     => 'text-emerald-500',
            self::Card     => 'text-blue-500',
            self::Transfer => 'text-violet-500',
            self::Allegro  => 'text-orange-500',
        };
    }

    /**
     * Flux badge color for the payment method pill.
     */
    public function color(): string
    {
        return match ($this) {
            self::Cash     => 'green',
            self::Card     => 'blue',
            self::Transfer => 'purple',
            self::Allegro  => 'orange',
        };
    }

    public function bgClass(): string
    {
        return match ($this) {
            self::Cash     => 'bg-emerald-100 dark:bg-emerald-900/40',
            self::Card     => 'bg-blue-100 dark:bg-blue-900/40',
            self::Transfer => 'bg-violet-100 dark:bg-violet-900/40',
            self::Allegro  => 'bg-orange-100 dark:bg-orange-900/40',
        };
    }
}