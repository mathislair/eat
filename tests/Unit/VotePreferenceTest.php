<?php

namespace Tests\Unit;

use App\Enums\VotePreference;
use PHPUnit\Framework\TestCase;

class VotePreferenceTest extends TestCase
{
    public function test_it_has_only_the_two_non_neutral_states(): void
    {
        $this->assertSame(['want', 'avoid'], array_column(VotePreference::cases(), 'value'));
    }

    public function test_wants_and_avoids_have_opposite_weights(): void
    {
        $this->assertSame(1, VotePreference::Want->weight());
        $this->assertSame(-1, VotePreference::Avoid->weight());
    }
}
