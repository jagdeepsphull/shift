<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Testimonials master.
 *
 * Same shape as the other back-office lists (`shift_for`, `additional_details`):
 * an id, the text, and an active flag the admin toggles. It carries two text
 * columns rather than one because a testimonial is a heading plus the quote
 * itself, and the home page carousel draws both.
 *
 * `t_status` defaults to 1, following `additional_details`: the add form posts
 * the two text fields and nothing else, so a column with no default would leave
 * a newly added testimonial Deactive and needing a second click before it ever
 * showed on the home page.
 */
class AddTestimonialTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            't_id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            't_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            't_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            't_status' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
        ]);
        $this->forge->addKey('t_id', true);
        $this->forge->createTable('testimonial');
    }

    public function down()
    {
        $this->forge->dropTable('testimonial');
    }
}
