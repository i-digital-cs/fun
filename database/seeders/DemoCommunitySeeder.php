<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\PlatformRole;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Support\UserPublicId;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCommunitySeeder extends Seeder
{
    private const MEMBER_COUNT = 100;

    private const PAST_EVENTS = 400;

    private const UPCOMING_EVENTS = 100;

    /** @var list<string> */
    private array $firstNames = [
        'Ahmed', 'Omar', 'Youssef', 'Hassan', 'Karim', 'Mahmoud', 'Tarek', 'Mostafa', 'Ali', 'Ibrahim',
        'Sara', 'Nour', 'Mariam', 'Hana', 'Layla', 'Yasmin', 'Dina', 'Farah', 'Rania', 'Salma',
        'Khaled', 'Amr', 'Hossam', 'Wael', 'Sherif', 'Nader', 'Bassem', 'Adel', 'Fady', 'Ramy',
        'Mona', 'Heba', 'Aya', 'Nada', 'Reem', 'Lina', 'Mai', 'Jana', 'Passant', 'Menna',
    ];

    /** @var list<string> */
    private array $lastNames = [
        'Hassan', 'Ali', 'Ibrahim', 'Mahmoud', 'Youssef', 'Farouk', 'Saleh', 'Nabil', 'Samir', 'Fathy',
        'Kamal', 'Rashad', 'Mansour', 'Soliman', 'Gaber', 'Hegazy', 'Zaki', 'Ashraf', 'Tawfik', 'Saad',
    ];

    /** @var array<string, list<string>> */
    private array $titlesByCategory = [
        'football' => [
            '5-a-side evening kickabout', 'Friendly football at the pitch', 'Weekend football meetup',
            'Morning football drills', 'Neighborhood football match', 'Indoor football session',
        ],
        'playstation' => [
            'FIFA tournament night', 'PlayStation party at the lounge', 'Co-op gaming evening',
            'Local FIFA cup', 'Console night with friends', 'Split-screen challenge',
        ],
        'basketball' => [
            'Pickup basketball game', 'Evening hoops session', 'Street basketball meetup',
            '3v3 basketball challenge', 'Weekend basketball run', 'Court practice + scrimmage',
        ],
        'running' => [
            'Sunrise group run', 'Evening jog around the lake', 'Weekend long run club',
            '5K neighborhood run', 'Casual recovery run', 'Park loop running meetup',
        ],
        'gaming' => [
            'PC gaming night', 'LAN party meetup', 'Competitive gaming session',
            'Casual online co-op', 'Esports watch + play', 'Strategy game evening',
        ],
        'board-games' => [
            'Board game café night', 'Catan + strategy night', 'Family board game evening',
            'New game learn & play', 'Card games meetup', 'Puzzle & board games night',
        ],
        'cinema' => [
            'Cinema night downtown', 'Indie film screening meetup', 'Blockbuster movie outing',
            'Arabic film night', 'Open-air cinema hangout', 'Post-movie coffee chat',
        ],
        'social' => [
            'Coffee & conversation', 'Newcomers welcome meetup', 'Evening social hangout',
            'Book club discussion', 'Friends of friends mixer', 'Neighborhood potluck',
        ],
        'outdoor' => [
            'Park picnic meetup', 'Sunset outdoor hangout', 'Bike ride around town',
            'Photography walk', 'Outdoor yoga in the park', 'Weekend hiking day trip',
        ],
        'tennis' => [
            'Tennis doubles practice', 'Friendly tennis match', 'Morning tennis drills',
            'Weekend tennis meetup', 'Beginner tennis session', 'Courtside rally night',
        ],
    ];

    /** @var list<string> */
    private array $venues = [
        'Community Sports Club', 'City Park Courts', 'Neighborhood Playground', 'Downtown Arena',
        'University Pitch', 'Club House Lounge', 'Riverside Promenade', 'Mall Food Court Meetup Point',
        'Local Café Terrace', 'Youth Center Hall', 'Municipal Stadium Side Pitch', 'Desert Road Courts',
        'School Gym (evening)', 'Rooftop Gathering Spot', 'Lakeside Path',
    ];

    /** @var array<string, true> */
    private array $ticketCodes = [];

    public function run(): void
    {
        $cities = City::query()->where('is_active', true)->get();
        $categories = Category::query()->where('is_active', true)->get();

        if ($cities->isEmpty() || $categories->isEmpty()) {
            $this->command?->error('Cities/categories missing — run CitySeeder and CategorySeeder first.');

            return;
        }

        $password = Hash::make('password');
        $now = now();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'locale' => 'en',
                'city_id' => $cities->firstWhere('slug', 'cairo')?->id ?? $cities->first()->id,
                'distance_km' => 50,
            ]
        );
        $admin->forceFill([
            'public_id' => $admin->public_id ?: UserPublicId::generate(),
            'status' => AccountStatus::Active,
            'platform_role' => PlatformRole::SuperAdmin,
            'email_verified_at' => $now,
        ])->save();

        $this->command?->info('Creating '.self::MEMBER_COUNT.' member accounts…');

        $users = collect([$admin]);
        $usedEmails = ['admin@example.com' => true];

        for ($i = 1; $i <= self::MEMBER_COUNT; $i++) {
            $first = fake()->randomElement($this->firstNames);
            $last = fake()->randomElement($this->lastNames);
            $name = $first.' '.$last;
            $email = Str::lower($first.'.'.$last.$i.'@example.com');
            while (isset($usedEmails[$email])) {
                $email = Str::lower($first.'.'.$last.$i.'.'.Str::random(3).'@example.com');
            }
            $usedEmails[$email] = true;

            $city = $cities->random();
            $locale = fake()->boolean(35) ? 'ar' : 'en';

            $user = User::query()->create([
                'public_id' => UserPublicId::generate(),
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'locale' => $locale,
                'city_id' => $city->id,
                'distance_km' => fake()->randomElement([10, 15, 25, 40, 50]),
            ]);
            $user->forceFill([
                'status' => AccountStatus::Active,
                'platform_role' => null,
                'email_verified_at' => $now,
            ])->save();

            $users->push($user);

            if ($i % 25 === 0) {
                $this->command?->info("  …{$i} users");
            }
        }

        $creators = $users->values();
        $totalEvents = self::PAST_EVENTS + self::UPCOMING_EVENTS;
        $this->command?->info("Creating {$totalEvents} events (".self::PAST_EVENTS.' past / '.self::UPCOMING_EVENTS.' upcoming)…');

        $memberRows = [];
        $eventBatch = 0;

        for ($n = 0; $n < $totalEvents; $n++) {
            $isPast = $n < self::PAST_EVENTS;
            $category = $categories->random();
            $city = $cities->random();
            $creator = $creators->random();
            $slug = $category->slug;
            $titlePool = $this->titlesByCategory[$slug] ?? ['Community meetup'];
            $title = fake()->randomElement($titlePool);

            if ($isPast) {
                $startsAt = $now->copy()->subDays(fake()->numberBetween(1, 180))->setTime(
                    fake()->numberBetween(8, 21),
                    fake()->randomElement([0, 15, 30, 45])
                );
                $status = EventStatus::Completed;
            } else {
                $startsAt = $now->copy()->addDays(fake()->numberBetween(0, 60))->setTime(
                    fake()->numberBetween(9, 21),
                    fake()->randomElement([0, 15, 30, 45])
                );
                if ($n < self::PAST_EVENTS + 8) {
                    $startsAt = $now->copy()->subMinutes(fake()->numberBetween(10, 90));
                }
                $status = EventStatus::Published;
            }

            $durationHours = fake()->randomElement([1, 2, 2, 3]);
            $endsAt = $startsAt->copy()->addHours($durationHours);
            $max = fake()->randomElement([6, 8, 10, 12, 16, 20]);
            $cost = fake()->boolean(40) ? null : fake()->randomElement([25, 40, 50, 80, 100, 120, 150]);

            $eventId = DB::table('events')->insertGetId([
                'created_by' => $creator->id,
                'category_id' => $category->id,
                'city_id' => $city->id,
                'title' => $title,
                'description' => $this->descriptionFor($title, $city->name_en, $category->name_en),
                'venue' => fake()->randomElement($this->venues).' · '.$city->name_en,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'max_participants' => $max,
                'requirements' => fake()->randomElement([
                    null,
                    'Bring water and comfortable shoes.',
                    'Please arrive 10 minutes early.',
                    'Friendly vibe — all skill levels welcome.',
                    'Confirm attendance in chat the day before.',
                ]),
                'expected_cost' => $cost === null ? 'Free' : 'Shared cost per person',
                'cost_per_person' => $cost,
                'image_path' => null,
                'image_disk' => null,
                'status' => $status->value,
                'created_at' => $startsAt->copy()->subDays(fake()->numberBetween(1, 14)),
                'updated_at' => $now,
            ]);

            $memberRows[] = $this->memberRow($eventId, $creator->id, EventMemberRole::Owner, $now);

            $extraCount = fake()->numberBetween(0, min(5, $max - 1));
            if ($extraCount > 0) {
                $extras = $creators->where('id', '!=', $creator->id)->random($extraCount);
                foreach ($extras as $member) {
                    $memberRows[] = $this->memberRow($eventId, $member->id, EventMemberRole::Member, $now);
                }
            }

            $eventBatch++;
            if (count($memberRows) >= 500) {
                DB::table('event_members')->insert($memberRows);
                $memberRows = [];
            }

            if ($eventBatch % 100 === 0) {
                $this->command?->info("  …{$eventBatch} events");
            }
        }

        if ($memberRows !== []) {
            DB::table('event_members')->insert($memberRows);
        }

        $this->command?->info('Demo data ready.');
        $this->command?->table(
            ['Metric', 'Count'],
            [
                ['Users (members)', (string) self::MEMBER_COUNT],
                ['Admin', '1 (admin@example.com / password)'],
                ['Past completed events', (string) self::PAST_EVENTS],
                ['Upcoming published events', (string) self::UPCOMING_EVENTS],
                ['Cities', (string) $cities->count()],
                ['Categories', (string) $categories->count()],
                ['Member password', 'password'],
            ]
        );
    }

    /**
     * @return array{event_id: int, user_id: int, role: string, ticket_code: string, created_at: \Illuminate\Support\Carbon, updated_at: \Illuminate\Support\Carbon}
     */
    private function memberRow(int $eventId, int $userId, EventMemberRole $role, $now): array
    {
        return [
            'event_id' => $eventId,
            'user_id' => $userId,
            'role' => $role->value,
            'ticket_code' => $this->uniqueTicketCode(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function uniqueTicketCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
            $code = preg_replace('/[01OI]/', 'X', $code) ?: $code;
        } while (isset($this->ticketCodes[$code]));

        $this->ticketCodes[$code] = true;

        return $code;
    }

    private function descriptionFor(string $title, string $city, string $category): string
    {
        return "Join us for \"{$title}\" in {$city}. "
            ."This {$category} meetup is open to friendly neighbors — bring good energy and meet new people. "
            .'Chat opens after you join so we can coordinate arrival and details.';
    }
}
