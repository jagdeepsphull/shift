<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Whether the signed agreement is on file for an account.
 *
 * A back-office fact, not something the account holder tells us: it is ticked
 * on the applicant and employer forms in `/sadmin` and nowhere else, so it
 * defaults to 0 and every account that exists on the day this runs - and every
 * new registration - starts as "not yet".
 *
 * TINYINT rather than the INT `u_status` uses, because unlike a status this is
 * only ever a yes or a no.
 */
class AddUserAgreementDone extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'u_agreement_done' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'u_status',
                'comment'    => '1 when the signed agreement is on file. Ticked in the back office only.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'u_agreement_done');
    }
}
