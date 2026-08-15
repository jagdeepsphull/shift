<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Which e-mails a user has been opted out of, for the Manage Email screen.
 *
 * A comma separated list of `emailTypes` codes (Config\AppSettings), the same
 * shape as `p_skills`. It holds what is BLOCKED, not what is allowed, and that
 * is deliberate on three counts: every existing account keeps receiving
 * everything on the day this runs (blank means nothing blocked); a brand new
 * registration starts the same way with no code writing a default; and an
 * eighth e-mail type added to the config next year is on for everybody at
 * once, instead of silently off because nobody's stored allow-list mentions
 * it.
 */
class AddUserEmailBlocked extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'u_email_blocked' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'default'    => '',
                'after'      => 'u_email',
                'comment'    => 'Comma separated AppSettings::$emailTypes codes this user must NOT receive. Blank = everything.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'u_email_blocked');
    }
}
