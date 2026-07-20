<?php

namespace App\Enums;

enum Diet: string implements Attribute
{
    case Vegan = 'vegan';
    case Vegetarian = 'vegetarian';
    case Halal = 'halal';
    case Kosher = 'kosher';
    case GlutenFree = 'gluten_free';

    public function type(): AttributeType
    {
        return AttributeType::Diet;
    }

    public function label(): string
    {
        return match ($this) {
            self::Vegan => 'Vegan',
            self::Vegetarian => 'Vegetarian',
            self::Halal => 'Halal',
            self::Kosher => 'Kosher',
            self::GlutenFree => 'Gluten-free',
        };
    }
}
