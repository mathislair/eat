<?php

namespace App\Enums;

enum Meal: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    /**
     * Human-friendly label for display.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
