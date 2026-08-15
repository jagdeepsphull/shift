<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Additional Details master.
 *
 * A back-office list of the same shape as Services (`store_service`) and Shift
 * For (`shift_for`): an id, a name, and an active flag the admin toggles.
 * Nothing reads it yet - it is the list itself that was asked for, and whatever
 * screen comes to use it will reference `ad_id` the way `post_job.p_services`
 * references `st_id`.
 *
 * `ad_status` defaults to 1, following `shift_for` rather than
 * `store_service`: the add form posts a name and nothing else, so a column with
 * no default leaves a newly added row Deactive and needing a second click. It
 * also makes the insert safe on a server running in strict mode, which one
 * without a default is not.
 */
class AddAdditionalDetailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ad_id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'ad_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ad_status' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
        ]);
        $this->forge->addKey('ad_id', true);
        $this->forge->createTable('additional_details');
    }

    public function down()
    {
        $this->forge->dropTable('additional_details');
    }
}
