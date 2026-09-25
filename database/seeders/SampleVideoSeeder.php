<?php

namespace Database\Seeders;

use App\Model\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

/**
 * Seeds the reels/home feed with demo clips so the app doesn't present an empty feed.
 *
 * The media files themselves are NOT created here — they ship as real files under
 * public/storage/videos and public/storage/thumbnails. This seeder only creates the rows
 * that point at them, and skips any clip whose file is missing so it can never seed a
 * broken player.
 *
 * Everything it creates is tagged SAMPLE_TAG, so it is safe to re-run and trivial to purge:
 *   php artisan db:seed --class=SampleVideoSeeder     (idempotent)
 *   php artisan sample:videos --purge                 (removes rows + files)
 */
class SampleVideoSeeder extends Seeder
{
    /** Marker stored in `tags` so demo rows are always identifiable for cleanup. */
    public const SAMPLE_TAG = 'sample-content';

    /** slug => [title, description, views] */
    public const CLIPS = [
        'morning-routine'    => ['Morning Routine', 'How I start every day.', 1240],
        'studio-session'     => ['Studio Session', 'Late night in the studio.', 860],
        'behind-the-scenes'  => ['Behind The Scenes', 'What you do not usually see.', 2130],
        'creator-qa'         => ['Creator Q&A', 'Answering your questions.', 640],
        'workout-challenge'  => ['Workout Challenge', 'Day 4 of the challenge.', 1780],
        'cooking-live'       => ['Cooking Live', 'Making dinner from scratch.', 970],
        'travel-diary'       => ['Travel Diary', 'First day in a new city.', 1520],
        'late-night-talk'    => ['Late Night Talk', 'Just talking, no script.', 430],
        'first-unboxing'     => ['First Unboxing', 'Opening it for the first time.', 2450],
        'dance-practice'     => ['Dance Practice', 'Still learning this one.', 1310],
    ];

    public function run(): void
    {
        $users = User::orderBy('id')->pluck('id')->all();
        if (empty($users)) {
            $this->command?->warn('SampleVideoSeeder: no users to attribute videos to; nothing seeded.');
            return;
        }

        $created = 0;
        $skipped = 0;
        $missing = 0;
        $i = 0;

        foreach (self::CLIPS as $slug => [$title, $description, $views]) {
            $videoPath = 'videos/' . $slug . '.mp4';
            $thumbPath = 'thumbnails/' . $slug . '.jpg';

            // Never seed a row whose media isn't actually on disk.
            if (!is_file(public_path('storage/' . $videoPath))) {
                $missing++;
                continue;
            }

            if (Video::where('video_path', $videoPath)->exists()) {
                $skipped++;
                $i++;
                continue;
            }

            // Assigned directly rather than via create(): tags/duration/views_count are absent
            // from the model's $fillable, so mass assignment silently drops them. `tags` also
            // carries a json_valid() CHECK constraint, hence the encoded array.
            $video = new Video();
            $video->user_id = $users[$i % count($users)];
            $video->title = $title;
            $video->description = $description;
            $video->tags = json_encode([self::SAMPLE_TAG]);
            $video->video_path = $videoPath;
            $video->thumbnail_path = is_file(public_path('storage/' . $thumbPath)) ? $thumbPath : null;
            $video->duration = 8;
            $video->is_public = true;
            $video->is_private = false;
            // Feed::getAllVideos() accepts 'published' or 'ready'; VideoController@reels only
            // accepts 'published', so use that to satisfy both.
            $video->status = 'published';
            $video->views_count = $views;
            $video->save();

            $created++;
            $i++;
        }

        $this->command?->info("SampleVideoSeeder: {$created} created, {$skipped} already present, {$missing} skipped (media file missing).");
    }
}
