<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Who the "your shift is live" e-mail goes to, chosen per shift.
 *
 * A comma separated list of the words `owner` and `manager` - the same shape as
 * `p_skills` beside it, but words rather than ids, because there is no master
 * table here and never will be: the two sides of a store are the two sides of a
 * store. Empty means neither was ticked, and the shift is still announced to
 * `AppSettings::$shiftEmailFallback`, which is on these e-mails whatever this
 * column says; a shift going live that nobody is told about is the one outcome
 * the form must not be able to produce by accident.
 *
 * The column has to be able to hold "chose nobody" distinctly from "was never
 * asked", and every row that already exists was never asked. Those are set to
 * `owner` below, which is exactly who they mailed before this column existed -
 * so deploying this changes nothing about a shift already on the site, and the
 * choice only ever applies to shifts saved through the form from now on.
 */
class AddShiftEmailRecipients extends Migration
{
    public function up()
    {
        $this->forge->addColumn('post_job', [
            'p_email_to' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'default'    => '',
                'after'      => 'p_approved',
                'comment'    => 'Comma separated: owner, manager. Empty = the fallback address.',
            ],
        ]);

        // Keep every existing shift mailing exactly who it mailed yesterday.
        $this->db->table('post_job')->where('p_email_to', '')->update(['p_email_to' => 'owner']);
    }

    public function down()
    {
        $this->forge->dropColumn('post_job', 'p_email_to');
    }
}
