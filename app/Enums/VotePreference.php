<?php

namespace App\Enums;

/**
 * How strongly an attendee feels about a single option on their ballot.
 *
 * Only non-neutral opinions are ever stored: a "neutral" preference is the
 * absence of a row, which keeps the ballot tables holding just the choices a
 * voter actually cared about. Each stored preference carries a weight so the
 * closed-event summary can turn a pile of ballots into one group decision.
 */
enum VotePreference: string
{
    case Want = 'want';   // 🟢 "je veux" — pulls the option up
    case Avoid = 'avoid'; // 🔴 "surtout pas" — pushes the option down

    /**
     * Net contribution to an option's score. Wants and avoids cancel out, so a
     * dish everyone loves floats to the top and one person's veto is one
     * person's love undone — not a hard ban.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Want => 1,
            self::Avoid => -1,
        };
    }
}
