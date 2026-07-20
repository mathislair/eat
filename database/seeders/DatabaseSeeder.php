<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();

        $test = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // A couple of events hosted by the test user, each with a few guests.
        Event::factory(2)
            ->for($test, 'creator')
            ->create()
            ->each(function (Event $event) use ($test, $users) {
                $event->attendees()->attach($test);
                $event->attendees()->attach($users->random(3));
            });

        // An event the test user was invited to (created by someone else).
        Event::factory()
            ->create()
            ->attendees()
            ->attach($test);
    }
}
