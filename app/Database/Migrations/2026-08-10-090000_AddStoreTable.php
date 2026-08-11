<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One login, multiple stores (change request B4).
 *
 * Until now one employer row in `users` *was* one store: the store name,
 * number and address lived on the login itself, and a shift copied its
 * province and city off that row. A store becomes its own record here:
 *
 *  - `store`            — name, number, address, phone, belonging to a login.
 *  - `post_job.p_store_id` — which location a shift belongs to.
 *  - `users.u_emp_role` — how the employer registered: 1 manager (can own
 *    several stores), 2 store account. 0 is every employer from before this
 *    feature; they are treated as managers so existing clients can add stores.
 *
 * Every existing employer is migrated into exactly one store built from the
 * columns on their login, and every existing shift is pointed at that store —
 * so nothing anybody sees changes on day one. The store columns on `users`
 * are left in place: they are still read as the fallback for rows created
 * outside the registration flow.
 */
class AddStoreTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            's_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'u_id' => [
                'type'     => 'INT',
                'comment'  => 'users.u_id of the owning employer login',
            ],
            's_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            's_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
                'comment'    => 'Store number - what u_licence_no held for an employer',
            ],
            's_province' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            's_city' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            's_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            's_pincode' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => '',
            ],
            's_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'default'    => '',
            ],
            's_status' => [
                'type'       => 'TINYINT',
                'default'    => 1,
            ],
            'created' => [
                'type'    => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified' => [
                'type'    => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('s_id', true);
        $this->forge->addKey('u_id');
        $this->forge->createTable('store');

        $this->forge->addColumn('post_job', [
            'p_store_id' => [
                'type'    => 'INT',
                'default' => 0,
                'after'   => 'u_id',
                'comment' => 'store.s_id the shift is at. 0 = from before B4; falls back to the owner\'s login columns.',
            ],
        ]);

        $this->forge->addColumn('users', [
            'u_emp_role' => [
                'type'    => 'TINYINT',
                'default' => 0,
                'after'   => 'u_usersubtype',
                'comment' => 'Employer role: 1 manager, 2 store. 0 = registered before B4, treated as manager.',
            ],
        ]);

        // Every existing employer becomes exactly one store, built from the
        // columns on their login, so nothing they see changes on day one.
        $this->db->query("
            INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
            SELECT u_id,
                   COALESCE(NULLIF(u_comp_name, ''), CONCAT(u_fname, ' ', u_lname)),
                   COALESCE(u_licence_no, ''),
                   u_provice, u_city,
                   COALESCE(u_address1, ''), COALESCE(u_pincode, ''), COALESCE(u_phone, ''),
                   1
              FROM users
             WHERE u_usertype = 1
        ");

        // And every existing shift points at its owner's (only) store, so past
        // bookings keep showing the address they always showed.
        $this->db->query('
            UPDATE post_job pj
              JOIN store s ON s.u_id = pj.u_id
               SET pj.p_store_id = s.s_id
             WHERE pj.p_store_id = 0
        ');
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'u_emp_role');
        $this->forge->dropColumn('post_job', 'p_store_id');
        $this->forge->dropTable('store');
    }
}
