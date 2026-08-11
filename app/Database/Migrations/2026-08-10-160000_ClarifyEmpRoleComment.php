<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Re-word the `u_emp_role` comment only - no data or type change.
 *
 * The column was added when the two employer kinds were called "manager" and
 * "store". Registration now offers Manager, Owner (Multi Store) and Owner
 * (Individual Store), and "Manager" means the opposite of what the old comment
 * says: role 1 is the multi-store owner, and role 2 covers both a manager and
 * an individual owner, which `u_parent_id` tells apart.
 */
class ClarifyEmpRoleComment extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('users', [
            'u_emp_role' => [
                'name'    => 'u_emp_role',
                'type'    => 'TINYINT',
                'default' => 0,
                'comment' => 'Employer kind: 1 owns many stores, 2 owns one (a manager when u_parent_id is set, an individual owner when it is 0). 0 = registered before B4.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('users', [
            'u_emp_role' => [
                'name'    => 'u_emp_role',
                'type'    => 'TINYINT',
                'default' => 0,
                'comment' => 'Employer role: 1 manager, 2 store. 0 = registered before B4, treated as manager.',
            ],
        ]);
    }
}
