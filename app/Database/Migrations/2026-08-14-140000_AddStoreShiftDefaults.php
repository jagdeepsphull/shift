<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * What a store normally offers, so a new shift starts from it.
 *
 * The same three lists a shift carries - `p_skills`, `p_services` and
 * `p_additional_details` - held against the location instead. Choosing the
 * store on the shift form copies these across so the admin is correcting a
 * filled-in form rather than ticking the same boxes for every shift at the
 * same pharmacy.
 *
 * They are only ever a starting point. The shift keeps its own copy the moment
 * it is saved, so editing it never reaches back here, and editing these never
 * reaches back into shifts already posted.
 *
 * Blank on every existing store, which simply means a shift at that store
 * starts empty exactly as it does today.
 */
class AddStoreShiftDefaults extends Migration
{
    public function up()
    {
        $this->forge->addColumn('store', [
            's_skills' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_website',
                'comment'    => 'Default software_skills.ss_id list for shifts here, same shape as post_job.p_skills',
            ],
            's_services' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_skills',
                'comment'    => 'Default store_service.st_id list for shifts here, same shape as post_job.p_services',
            ],
            's_additional_details' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_services',
                'comment'    => 'Default additional_details.ad_id list for shifts here, same shape as post_job.p_additional_details',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('store', ['s_skills', 's_services', 's_additional_details']);
    }
}
