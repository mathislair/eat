<?php

namespace App\Enums;

enum Style: string implements Attribute
{
    case Spicy = 'spicy';
    case Mild = 'mild';
    case Sweet = 'sweet';
    case Savory = 'savory';
    case Sour = 'sour';

    public function type(): AttributeType
    {
        return AttributeType::Style;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
