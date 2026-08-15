<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Every employer becomes an Owner (Multi Store).
 *
 * Most accounts still carry `u_emp_role` 0 - they registered before the three
 * kinds existed (change request B4), so they belong to no kind at all. That
 * left them reachable only through "All Employers" in every kind filter, and
 * `storeOwnerBlocker()` had no basis to judge whether they may hold a second
 * location. Role 1 answers both: they are owners, and an owner may hold as
 * many stores as they like.
 *
 * Only the role moves. `u_parent_id` is left as it is: on an environment that
 * does have managers, the group they answer to is real information and worth
 * more than the tidiness of clearing it. `employerKindCode()` reads role 1
 * first, so such a row still reads as an owner.
 *
 * There is no going back. The column does not record what a row held before,
 * so `down()` cannot tell an account that was 0 from one that was already 1.
 */
class MakeEmployersMultiStore extends Migration
{
    public function up()
    {
        $this->db->query('UPDATE `users` SET `u_emp_role` = 1 WHERE `u_usertype` = 1 AND `u_emp_role` <> 1');
    }

    public function down()
    {
        // Deliberately nothing. Putting every employer back to 0 would be a
        // different change, not a reversal - it would also demote the accounts
        // that were owners before this ran.
    }
}
