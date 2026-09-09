<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The person behind the quote.
 *
 * `testimonial` started as a heading plus a body, which is all the first
 * carousel drew. A quote with nobody's name on it reads as copy the agency
 * wrote about itself, so the home page now shows who said it - their name, what
 * they are (job seeker, employer, HR), where they are, a star rating and a
 * photo - and these are the columns behind that.
 *
 * All five are nullable but for `t_rating`, which defaults to 5. The rating is
 * drawn as stars on every card, so a row with no answer has to have one: NULL
 * would render as an empty strip where four of the cards show five stars.
 *
 * `t_image` holds a bare filename, not a path - the same convention as
 * `users.u_photo`. Files land in uploads/testimonial/, and a row with none
 * falls back to the placeholder thumb (see `testimonialPhoto()`), so a
 * photoless testimonial still fills its circle rather than showing a broken
 * image.
 *
 * They go after `t_description` so the columns read in the order the card does;
 * `t_status` and `t_order`, which are bookkeeping rather than content, stay at
 * the end where they were.
 */
class AddTestimonialPersonFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('testimonial', [
            't_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 't_description',
                'comment'    => 'Who said it, as shown on the card - "Sarah M.".',
            ],
            't_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 't_name',
                'comment'    => 'Their standing - "Job Seeker", "HR Manager". Blank hides the line.',
            ],
            't_location' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 't_role',
                'comment'    => 'City and province. Blank hides the pin.',
            ],
            't_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 't_location',
                'comment'    => 'Filename under uploads/testimonial/. Blank falls back to the placeholder thumb.',
            ],
            't_rating' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 5,
                'after'      => 't_image',
                'comment'    => 'Stars out of five, 1-5.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('testimonial', ['t_name', 't_role', 't_location', 't_image', 't_rating']);
    }
}
