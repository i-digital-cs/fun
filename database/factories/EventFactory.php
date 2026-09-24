<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\UserSex;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(18, 0);

        return [
            'created_by' => User::factory(),
            'category_id' => Category::factory(),
            'city_id' => City::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'venue' => fake()->company().' Playground',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHours(2),
            'max_participants' => 10,
            'sex' => UserSex::Mix,
            'requirements' => 'Bring water',
            'expected_cost' => 'Pitch booking shared',
            'cost_per_person' => 80,
            'status' => EventStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Published,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Event $event) {
            if ($event->members()->where('user_id', $event->created_by)->exists()) {
                return;
            }

            $event->members()->create([
                'user_id' => $event->created_by,
                'role' => \App\Enums\EventMemberRole::Owner,
            ]);
        });
    }
}
