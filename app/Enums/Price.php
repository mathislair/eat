<?php

namespace App\Enums;

enum Price: string implements Attribute
{
    case Cheap = '€';
    case Moderate = '€€';
    case Expensive = '€€€';

    public function type(): AttributeType
    {
        return AttributeType::Price;
    }

    /**
     * Human-friendly label — the symbol itself.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Ordinal weight (1 = cheapest), useful for sorting and range filters.
     */
    public function level(): int
    {
        return match ($this) {
            self::Cheap => 1,
            self::Moderate => 2,
            self::Expensive => 3,
        };
    }
}
