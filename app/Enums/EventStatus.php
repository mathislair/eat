<?php

namespace App\Enums;

enum EventStatus: string
{
    case Voting = 'voting';
    case Closed = 'closed';

    /**
     * Human-friendly label for display.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
