<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Which Additional Details a shift offers.
 *
 * The same shape as `p_services` next to it - a comma separated list of ids
 * from the master, ticked on the shift form - pointing at
 * `additional_details.ad_id`.
 *
 * Wider than `p_services` (VARCHAR(100)) on purpose: that column holds roughly
 * a dozen two-digit ids before it silently truncates the tail, and this master
 * is new with no ceiling on how long it grows.
 *
 * Not to be confused with `p_jobinfo`, the free-text "Additional details" box
 * further down the same form, which stays exactly as it was.
 */
class AddJobAdditionalDetails extends Migration
{
    public function up()
    {
        $this->forge->addColumn('post_job', [
            'p_additional_details' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'p_services',
                'comment'    => 'Comma separated additional_details.ad_id list, same shape as p_services',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('post_job', 'p_additional_details');
    }
}
