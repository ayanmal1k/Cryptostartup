<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Let streams.status hold 'deleted'.
 *
 * The column is enum('pending','live','ended'), but StreamsController writes
 * Stream::DELETED_STATUS ('deleted') when a creator removes a stream -- a value the
 * column cannot hold, so the write failed. Widening the enum is the smaller change:
 * the code already treats 'deleted' as a real state and filters on it.
 *
 * Raw SQL because Doctrine's schema tooling does not model MySQL enums, so a
 * Blueprint change here would drop the type rather than extend it.
 */
class AddDeletedToStreamsStatusEnum extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('streams') || !Schema::hasColumn('streams', 'status')) {
            return;
        }

        if ($this->statusEnumHas('deleted')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `streams` MODIFY `status` "
            . "ENUM('pending','live','ended','deleted') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down()
    {
        if (!Schema::hasTable('streams') || !Schema::hasColumn('streams', 'status')) {
            return;
        }

        // Nothing can be left pointing at a value the narrower type cannot hold.
        DB::table('streams')->where('status', 'deleted')->update(['status' => 'ended']);

        DB::statement(
            "ALTER TABLE `streams` MODIFY `status` "
            . "ENUM('pending','live','ended') NOT NULL DEFAULT 'pending'"
        );
    }

    private function statusEnumHas(string $value): bool
    {
        try {
            $col = DB::select("SHOW COLUMNS FROM `streams` LIKE 'status'");

            return !empty($col) && strpos($col[0]->Type, "'" . $value . "'") !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
