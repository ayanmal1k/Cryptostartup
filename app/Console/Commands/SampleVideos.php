<?php

namespace App\Console\Commands;

use App\Models\Video;
use Database\Seeders\SampleVideoSeeder;
use Illuminate\Console\Command;

/**
 * Manage the demo clips that populate the feed.
 *
 *   php artisan sample:videos            list what is currently seeded
 *   php artisan sample:videos --purge    remove the seeded rows (and optionally the files)
 *
 * Exists so demo content is never a one-way door: the client will want it gone before real
 * users arrive, and hunting rows by hand is exactly how demo data survives into production.
 */
class SampleVideos extends Command
{
    protected $signature = 'sample:videos {--purge : Delete the seeded sample videos} {--files : Also delete the media files from disk}';

    protected $description = 'List or purge the sample videos that populate the feed';

    public function handle(): int
    {
        // tags is a JSON column, so match the marker inside the encoded array.
        $videos = Video::where('tags', 'like', '%' . SampleVideoSeeder::SAMPLE_TAG . '%')->get();

        if ($videos->isEmpty()) {
            $this->info('No sample videos are currently seeded.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Path', 'User'],
            $videos->map(fn ($v) => [$v->id, $v->title, $v->video_path, $v->user_id])->all()
        );

        if (!$this->option('purge')) {
            $this->line('');
            $this->comment('Run with --purge to remove these (add --files to delete the media too).');
            return self::SUCCESS;
        }

        $deletedFiles = 0;
        foreach ($videos as $video) {
            if ($this->option('files')) {
                foreach ([$video->video_path, $video->thumbnail_path] as $path) {
                    if ($path && is_file(public_path('storage/' . $path))) {
                        @unlink(public_path('storage/' . $path));
                        $deletedFiles++;
                    }
                }
            }
            $video->delete();
        }

        $this->info("Purged {$videos->count()} sample videos" . ($this->option('files') ? " and {$deletedFiles} files." : '.'));

        return self::SUCCESS;
    }
}
