<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The one-click opt-out behind the Unsubscribe link on every e-mail.
 *
 * Two columns, and both are deliberate.
 *
 * `u_unsubscribed_at` is a nullable datetime rather than a flag because the
 * admin screen this feeds asks "who opted out, and when" - a tinyint answers
 * only half of that, and the date is what tells an administrator whether a
 * complaint and an opt-out are the same event. NULL means subscribed, so every
 * account on the day this runs keeps receiving exactly what it received the day
 * before, the same way `u_email_blocked` was introduced.
 *
 * This is separate from `u_email_blocked` and does not replace it. That column
 * is the administrator's per-type switchboard, set on somebody's behalf from
 * Manage Email; this one is the recipient's own decision about all of it, made
 * from their inbox without signing in. Folding the second into the first would
 * lose which of the two happened - and re-subscribing would have to guess which
 * types the administrator had meant to leave off.
 *
 * `u_unsub_token` is the unguessable half of the link. It is per user and
 * stable, so a link in a year-old e-mail still works, and it is not derived
 * from `u_id` or the address: a token that can be computed from a sequential id
 * is a URL anybody can walk to unsubscribe every user on the site. Existing
 * rows are filled in here; anything still blank is filled in lazily by
 * `unsubscribeToken()` the first time that user is sent something.
 */
class AddUserUnsubscribed extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'u_unsubscribed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'u_email_blocked',
                'comment' => 'When this user opted out of all optional e-mail. NULL = still subscribed.',
            ],
            'u_unsub_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
                'default'    => '',
                'after'      => 'u_unsubscribed_at',
                'comment'    => 'Secret in this user\'s unsubscribe link. Blank until first needed.',
            ],
        ]);

        // Not unique: rows sit at '' until their first e-mail, and a unique key
        // would refuse the second one. The lookup is by token on a public URL,
        // so it wants an index either way.
        $this->db->query('CREATE INDEX idx_users_unsub_token ON ' . $this->db->prefixTable('users') . ' (u_unsub_token)');

        // Fill in what exists now, so the first send after this deploy is a
        // plain read. random_bytes is the same source unsubscribeToken() uses.
        foreach ($this->db->table('users')->select('u_id')->get()->getResultArray() as $row) {
            $this->db->table('users')
                ->where('u_id', (int) $row['u_id'])
                ->update(['u_unsub_token' => bin2hex(random_bytes(16))]);
        }
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_users_unsub_token ON ' . $this->db->prefixTable('users'));

        $this->forge->dropColumn('users', ['u_unsubscribed_at', 'u_unsub_token']);
    }
}
