<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The address that is copied on booking e-mails.
 *
 * Kept in `settings` rather than in the code so the agency address can be
 * changed from the admin panel. `AppSettings::$agencyCopyEmail` is the fallback
 * used when the column is empty.
 */
class AddAgencyCopyEmailSetting extends Migration
{
    public function up()
    {
        $this->forge->addColumn('settings', [
            's_agency_copy_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 's_email',
                'comment'    => 'Copied on booking e-mails. Blank switches the copy off.',
            ],
        ]);

        $this->db->table('settings')
            ->where('s_id', 1)
            ->update(['s_agency_copy_email' => 'team@pickashift.ca']);
    }

    public function down()
    {
        $this->forge->dropColumn('settings', 's_agency_copy_email');
    }
}
