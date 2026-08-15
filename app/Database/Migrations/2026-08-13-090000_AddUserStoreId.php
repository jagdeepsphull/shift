<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Which of the corporate group's stores a manager runs.
 *
 * A manager used to type a store's name and address at registration and got a
 * `store` row of their own. They now pick one of the stores the group already
 * added, so the row belongs to the group and this column says which one.
 *
 * 0 means the login owns its stores outright - every account before this, and
 * still every owner. Only a manager (`u_emp_role` 2 with `u_parent_id` set)
 * ever carries a store id here.
 */
class AddUserStoreId extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'u_store_id' => [
                'type'    => 'INT',
                'default' => 0,
                'after'   => 'u_parent_id',
                'comment' => 'store.s_id this manager runs, owned by their u_parent_id group. 0 = the login owns its own stores.',
            ],
        ]);

        // Managers registered before this change own the store they typed in.
        // Pointing the column at it keeps them resolving through the same path
        // as everyone else instead of needing a special case.
        $this->db->query('UPDATE `users` u
                            JOIN `store` s ON s.`u_id` = u.`u_id`
                             SET u.`u_store_id` = s.`s_id`
                           WHERE u.`u_emp_role` = 2
                             AND u.`u_parent_id` > 0
                             AND COALESCE(u.`u_store_id`, 0) = 0');
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'u_store_id');
    }
}
