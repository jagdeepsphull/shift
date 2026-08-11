<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A single-store owner can belong to a multi-store owner's pharmacy group.
 *
 * The link is chosen at registration and is optional: 0 means the store owns
 * itself and answers to nobody, which is what every existing account is.
 */
class AddUserParentId extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'u_parent_id' => [
                'type'    => 'INT',
                'default' => 0,
                'after'   => 'u_emp_role',
                'comment' => 'users.u_id of the multi-store owner this single-store login belongs to. 0 = independent.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'u_parent_id');
    }
}
