<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\UserSex;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\EventMessage;
use App\Models\User;
use App\Support\MapsUrlParser;
use App\Support\UserPublicId;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EgyptManualEventsSeeder extends Seeder
{
    public const OWNER_EMAIL = 'egypt.catalog@funnity.com';

    private const MANUAL_DIR = 'events/manual';

    public function run(bool $fresh = false): void
    {
        $catalog = require __DIR__.'/data/egypt_manual_events.php';
        $this->command?->info('Egypt catalog events: '.count($catalog));

        $categories = Category::query()->get()->keyBy('slug');
        $cities = City::query()->get()->keyBy('slug');

        if ($categories->isEmpty() || $cities->isEmpty()) {
            $this->command?->error('Run CitySeeder + CategorySeeder first.');

            return;
        }

        $owner = $this->ensureOwner();
        $pool = $this->ensureActivePool(40, $owner->id);

        if ($fresh) {
            $removed = Event::query()->where('created_by', $owner->id)->pluck('id');
            if ($removed->isNotEmpty()) {
                EventMessage::query()->whereIn('event_id', $removed)->delete();
                EventJoinRequest::query()->whereIn('event_id', $removed)->delete();
                EventMember::query()->whereIn('event_id', $removed)->delete();
                Event::query()->whereIn('id', $removed)->delete();
                $this->command?->warn('Removed previous catalog events: '.$removed->count());
            }
        }

        $uploaded = $this->syncImagesToR2();
        $this->command?->info("Images synced to R2: {$uploaded}");

        $maps = app(MapsUrlParser::class);
        $created = 0;

        foreach ($catalog as $index => $row) {
            $category = $categories->get($row['category']);
            $city = $cities->get($row['city']);
            if (! $category || ! $city) {
                $this->command?->warn("Skip [{$row['title']}] — missing category/city");

                continue;
            }

            // Discover orders by starts_at ASC (soonest first) — pack catalog near-term.
            $starts = now()
                ->addMinutes(30)
                ->addHours($index * 4)
                ->seconds(0);
            $preferredHour = max(9, min(22, (int) $row['hour']));
            if ((int) $starts->format('H') < 9 || (int) $starts->format('H') > 22) {
                $starts->setTime($preferredHour, [0, 15, 30, 45][$index % 4]);
            }
            $ends = (clone $starts)->addHours((int) $row['duration_hours']);

            $imagePath = null;
            $imageDisk = null;
            if (filled($row['image'] ?? null)) {
                $rel = self::MANUAL_DIR.'/'.$row['image'];
                if (Storage::disk('r2')->exists($rel) || $this->localImageExists($row['image'])) {
                    if (! Storage::disk('r2')->exists($rel)) {
                        $this->uploadOne($row['image']);
                    }
                    $imagePath = $rel;
                    $imageDisk = 'r2';
                }
            }

            $memberTarget = min(
                max(4, (int) round($row['max'] * 0.55)),
                max(3, (int) $row['max'] - 2)
            );

            $event = new Event([
                'category_id' => $category->id,
                'city_id' => $city->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'venue' => $row['venue'],
                'latitude' => $row['lat'],
                'longitude' => $row['lng'],
                'maps_url' => $maps->openStreetMapUrl((float) $row['lat'], (float) $row['lng']),
                'starts_at' => $starts,
                'ends_at' => $ends,
                'max_participants' => (int) $row['max'],
                'sex' => UserSex::Mix,
                'requirements' => $row['requirements'] ?? null,
                'expected_cost' => $row['cost'] ?? null,
                'cost_per_person' => null,
                'image_path' => $imagePath,
                'image_disk' => $imageDisk,
            ]);
            $event->forceFill([
                'created_by' => $owner->id,
                'status' => EventStatus::Published,
            ])->save();

            // Owner + random active members
            $event->members()->create([
                'user_id' => $owner->id,
                'role' => EventMemberRole::Owner,
            ]);

            $others = $pool->where('id', '!=', $owner->id)->shuffle()->take($memberTarget - 1);
            foreach ($others as $i => $user) {
                $event->members()->create([
                    'user_id' => $user->id,
                    'role' => $i === 0 ? EventMemberRole::Admin : EventMemberRole::Member,
                ]);
            }

            $memberIds = $event->members()->pluck('user_id')->all();

            // Pending join requests from users not already members
            $applicants = $pool->whereNotIn('id', $memberIds)->shuffle()->take(random_int(1, 3));
            foreach ($applicants as $applicant) {
                EventJoinRequest::query()->create([
                    'event_id' => $event->id,
                    'user_id' => $applicant->id,
                    'status' => JoinRequestStatus::Pending,
                    'message' => collect([
                        'مهتم أشارك، إزاي نلتقي؟',
                        'عايز أحجز مكان معايا لو لسه فيه.',
                        'الجو باين حلو — أقدر أنضم؟',
                        'Hi! Can I join this one?',
                    ])->random(),
                ]);
            }

            // Chat messages from members
            $chatters = $event->members()->with('user')->get()->shuffle();
            $bodies = [
                'مية مية، أنا جاهز في الميعاد 🙌',
                'هصل قبلها بربع ساعة عشان المواصلات.',
                'حد محتاج توصيلة من الطريق الدائري؟',
                'لو الجو حر نجيب مياه زيادة.',
                'تم، شوفت اللوكيشن على الخريطة.',
                'أي حد جديد مرحّب بيه 👋',
                'نأكد الحضور هنا لو سمحتوا.',
                'Looks great — see you there!',
            ];
            $count = random_int(2, 5);
            for ($m = 0; $m < $count; $m++) {
                $who = $chatters[$m % $chatters->count()];
                EventMessage::query()->create([
                    'event_id' => $event->id,
                    'user_id' => $who->user_id,
                    'body' => $bodies[($index + $m) % count($bodies)],
                    'is_system' => false,
                    'created_at' => now()->subHours($count - $m),
                    'updated_at' => now()->subHours($count - $m),
                ]);
            }

            $created++;
            $this->command?->line(($index + 1).'/'.count($catalog).' · '.$row['title']);
        }

        $this->command?->info("Done. Created {$created} published Egypt catalog events (owner: ".self::OWNER_EMAIL.').');
    }

    private function ensureOwner(): User
    {
        $user = User::query()->where('email', self::OWNER_EMAIL)->first();
        if ($user) {
            if ($user->status !== AccountStatus::Active) {
                $user->forceFill([
                    'status' => AccountStatus::Active,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            return $user;
        }

        $user = new User([
            'name' => 'Funnity Egypt Catalog',
            'email' => self::OWNER_EMAIL,
            'password' => Hash::make('password'),
            'locale' => 'ar',
            'sex' => UserSex::Mix,
            'city_id' => City::query()->where('slug', 'cairo')->value('id'),
            'distance_km' => 40,
        ]);
        $user->forceFill([
            'public_id' => UserPublicId::generate(),
            'status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function ensureActivePool(int $need, int $ownerId)
    {
        $pool = User::query()
            ->where('status', AccountStatus::Active)
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->limit(120)
            ->get();

        $extra = max(0, $need - $pool->count());
        for ($i = 0; $i < $extra; $i++) {
            $n = $pool->count() + $i + 1;
            $u = new User([
                'name' => 'عضو نشط '.$n,
                'email' => "egypt.member{$n}@funnity.test",
                'password' => Hash::make('password'),
                'locale' => $i % 2 === 0 ? 'ar' : 'en',
                'sex' => [UserSex::Male, UserSex::Female, UserSex::Mix][$i % 3],
                'city_id' => City::query()->inRandomOrder()->value('id'),
                'distance_km' => 25,
            ]);
            $u->forceFill([
                'public_id' => UserPublicId::generate(),
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ])->save();
            $pool->push($u);
        }

        return $pool->values();
    }

    private function syncImagesToR2(): int
    {
        $dir = storage_path('app/public/media/'.self::MANUAL_DIR);
        if (! is_dir($dir)) {
            $this->command?->warn("Manual images folder missing: {$dir}");

            return 0;
        }

        $count = 0;
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (! str_ends_with(Str::lower($file), '.png') && ! str_ends_with(Str::lower($file), '.jpg') && ! str_ends_with(Str::lower($file), '.webp')) {
                continue;
            }
            if ($this->uploadOne($file)) {
                $count++;
            }
        }

        return $count;
    }

    private function uploadOne(string $filename): bool
    {
        $local = storage_path('app/public/media/'.self::MANUAL_DIR.'/'.$filename);
        if (! is_file($local)) {
            return false;
        }

        $key = self::MANUAL_DIR.'/'.$filename;
        if (Storage::disk('r2')->exists($key)) {
            return true;
        }

        Storage::disk('r2')->put($key, file_get_contents($local), 'public');

        return true;
    }

    private function localImageExists(string $filename): bool
    {
        return is_file(storage_path('app/public/media/'.self::MANUAL_DIR.'/'.$filename));
    }
}
