<?php

namespace App\Enums;

use App\Support\EventSummary;

/**
 * How an attendee feels about a single option on their ballot.
 *
 * Only non-neutral opinions are ever stored: a "neutral" preference is the
 * absence of a row, which keeps the ballot tables holding just the choices a
 * voter actually cared about. An "avoid" is a hard veto — a single one takes
 * the option off the table for the whole group, no matter how many wants it
 * collected (see {@see EventSummary}).
 */
enum VotePreference: string
{
    case Want = 'want';   // 🟢 "je veux"
    case Avoid = 'avoid'; // 🔴 "surtout pas" — a veto
}
