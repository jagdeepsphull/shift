<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Give the accounts created from the back office a login id.
 *
 * `users.u_userid` is the column the login screen looks up, and public
 * registration fills it from the e-mail address. Neither admin "add" form ever
 * wrote it, so an account added by an administrator was saved with an empty
 * `u_userid` and could never sign in - the password was right, the lookup found
 * nothing.
 *
 * The forms write the column now. This repairs the rows they already made, by
 * copying the e-mail address the way registration would have. A row whose
 * e-mail is itself blank, or whose e-mail is already some other account's login
 * id, is left alone: there is nothing safe to put there, and overwriting would
 * hand one person's login id to another.
 */
class BackfillBlankUserLoginId extends Migration
{
    public function up()
    {
        $this->db->query("
            UPDATE users u
              LEFT JOIN users other
                     ON other.u_userid = u.u_email
                    AND other.u_id <> u.u_id
               SET u.u_userid = u.u_email
             WHERE (u.u_userid IS NULL OR u.u_userid = '')
               AND u.u_email IS NOT NULL
               AND u.u_email <> ''
               AND other.u_id IS NULL
        ");
    }

    public function down()
    {
        // Emptying the column again would only restore accounts that could not
        // log in, so this is deliberately not reversible.
    }
}
