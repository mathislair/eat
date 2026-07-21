<?php

namespace App\Enums;

/**
 * An attendee's Tinder-style call on a suggested restaurant during the reveal.
 * A place everyone accepts is where the group ends up eating.
 */
enum SwipeDecision: string
{
    case Accept = 'accept'; // 💚 swipe right — works for me
    case Reject = 'reject'; // ✖️ swipe left — not this one
}
