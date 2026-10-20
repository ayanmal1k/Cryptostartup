<?php

namespace App\Console\Commands;

use App\Models\CustomRequest;
use Database\Seeders\SampleChallengeSeeder;
use Illuminate\Console\Command;

/**
 * List or purge the demo challenges that populate the browse page, so this content is never
 * a one-way door into production.
 */
class SampleChallenges extends Command
{
    protected $signature = 'sample:challenges {--purge : Delete the seeded sample challenges}';

    protected $description = 'List or purge the sample challenges on the custom-requests browse page';

    public function handle(): int
    {
        $rows = CustomRequest::where('release_notes', SampleChallengeSeeder::SAMPLE_TAG)->get();

        if ($rows->isEmpty()) {
            $this->info('No sample challenges are currently seeded.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Status', 'Raised/Goal', 'Video'],
            $rows->map(fn ($r) => [
                $r->id,
                $r->title,
                $r->status,
                $r->current_amount . '/' . $r->goal_amount,
                $r->delivery_video_path ?: '—',
            ])->all()
        );

        if (!$this->option('purge')) {
            $this->line('');
            $this->comment('Run with --purge to remove these. Media is shared with the sample videos, so it is left in place.');
            return self::SUCCESS;
        }

        $count = $rows->count();
        // forceDelete because CustomRequest soft-deletes; a soft-deleted demo row would linger.
        foreach ($rows as $row) {
            $row->forceDelete();
        }

        $this->info("Purged {$count} sample challenges.");

        return self::SUCCESS;
    }
}
