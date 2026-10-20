<?php

namespace Database\Seeders;

use App\Model\User;
use App\Models\CustomRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the challenges browse page with a few demo requests — some delivered (so the video
 * wall has something on it) and some still open for funding (so the progress bars are real).
 *
 * Delivered rows reuse the sample clips already shipped under public/storage/videos, so this
 * adds no new media. Rows are tagged via `release_notes` so they can be purged in one command:
 *   php artisan sample:challenges --purge
 */
class SampleChallengeSeeder extends Seeder
{
    public const SAMPLE_TAG = '[sample-content]';

    /** title, slug of an existing sample clip (null = open/undelivered), goal, raised */
    public const CHALLENGES = [
        ['Dye my hair pink for a week',      'dance-practice',    250, 250],
        ['Cook a 3-course meal blindfolded', 'cooking-live',      180, 180],
        ['24 hours in the studio',           'studio-session',    400, 400],
        ['Learn a dance in one day',         'workout-challenge', 150, 150],
        ['Shave my head on stream',          null,               1000, 640],
        ['Fly somewhere random for 48h',     null,                800, 210],
        ['Run a marathon in costume',        null,                500, 95],
    ];

    public function run(): void
    {
        $users = User::orderBy('id')->pluck('id')->all();
        if (count($users) < 2) {
            $this->command?->warn('SampleChallengeSeeder: needs at least 2 users; nothing seeded.');
            return;
        }

        $created = 0;
        $skipped = 0;
        $i = 0;

        foreach (self::CHALLENGES as [$title, $clip, $goal, $raised]) {
            if (CustomRequest::where('title', $title)->exists()) {
                $skipped++;
                $i++;
                continue;
            }

            $creatorId = $users[$i % count($users)];
            // Requester must differ from creator, or the request makes no sense.
            $requesterId = $users[($i + 1) % count($users)];

            $delivered = $clip !== null && is_file(public_path('storage/videos/' . $clip . '.mp4'));

            $cr = new CustomRequest();
            $cr->creator_id = $creatorId;
            $cr->requester_id = $requesterId;
            $cr->type = 'marketplace';
            $cr->title = $title;
            $cr->description = 'A community-funded challenge. ' . self::SAMPLE_TAG;
            $cr->goal_amount = $goal;
            $cr->current_amount = $raised;
            $cr->price = 0;
            $cr->upfront_payment = 0;
            $cr->payment_received = false;
            $cr->is_marketplace = true;
            $cr->requires_voting = false;
            $cr->release_notes = self::SAMPLE_TAG;
            $cr->status = $delivered
                ? CustomRequest::STATUS_COMPLETED
                : ($raised > 0 ? CustomRequest::STATUS_ACCEPTED : CustomRequest::STATUS_PENDING);

            if ($delivered) {
                $cr->delivery_video_path = 'videos/' . $clip . '.mp4';
                if (is_file(public_path('storage/thumbnails/' . $clip . '.jpg'))) {
                    $cr->delivery_thumbnail_path = 'thumbnails/' . $clip . '.jpg';
                }
                $cr->delivered_at = now()->subDays($i + 1);
            }

            $cr->save();
            $created++;
            $i++;
        }

        $this->command?->info("SampleChallengeSeeder: {$created} created, {$skipped} already present.");
    }
}
