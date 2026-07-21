<?php

namespace Tests\Unit;

use App\Enums\VotePreference;
use PHPUnit\Framework\TestCase;

class VotePreferenceTest extends TestCase
{
    public function test_it_has_only_the_two_non_neutral_states(): void
    {
        // Neutral is never stored — it's simply the absence of a row.
        $this->assertSame(['want', 'avoid'], array_column(VotePreference::cases(), 'value'));
    }
}
