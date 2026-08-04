<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gives `post_job` a real DATE column.
 *
 * The shift date has always been stored in `p_dates` as a `varchar(200)`
 * holding text like `01-08-2026`, which cannot be sorted or filtered
 * chronologically. This adds `p_date_start` and backfills it from the existing
 * text. Both columns are written from now on; `p_dates` stays as the source of
 * truth for display until every screen has moved across.
 */
class AddShiftDateColumn extends Migration
{
    public function up()
    {
        $this->forge->addColumn('post_job', [
            'p_date_start' => [
                'type'    => 'DATE',
                'null'    => true,
                'after'   => 'p_dates',
                'comment' => 'Parsed from p_dates (dd-mm-yyyy). Sortable shift date.',
            ],
        ]);

        // Backfill. Rows whose text does not parse are left NULL rather than
        // guessed at - they are reported by the accompanying spark command.
        $this->db->query(
            "UPDATE post_job
                SET p_date_start = STR_TO_DATE(p_dates, '%d-%m-%Y')
              WHERE p_dates IS NOT NULL
                AND p_dates <> ''
                AND STR_TO_DATE(p_dates, '%d-%m-%Y') IS NOT NULL"
        );

        $this->db->query('CREATE INDEX idx_post_job_date_start ON post_job (p_date_start)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_post_job_date_start ON post_job');
        $this->forge->dropColumn('post_job', 'p_date_start');
    }
}
