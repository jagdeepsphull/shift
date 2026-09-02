<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hourly rates in dollars and cents.
 *
 * Both rate columns were `int`, so a shift could only ever be posted at a whole
 * number of dollars. Pharmacy rates are not whole numbers - $42.50 is an
 * ordinary offer - and the forms had no way to say one: the browser refuses a
 * fractional value in a `number` box whose step is the default 1, and anything
 * that got past it would have been rounded away by the column.
 *
 * DECIMAL, not FLOAT. These are money, they are compared and shown to the
 * dollar, and a binary fraction cannot hold 42.10 exactly - a rate entered and
 * read back has to be the same number. 6,2 leaves four digits in front of the
 * point, which is far past the 200 the forms cap a rate at.
 *
 * Existing rows convert exactly: every value on file is a whole number and
 * becomes the same number with .00 after it. Nothing is rounded and no rate
 * changes, which is why this needs no backfill.
 */
class ShiftRatesTakeCents extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('post_job', [
            'p_hourly_rate' => [
                'name'       => 'p_hourly_rate',
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'null'       => true,
                'default'    => null,
                'comment'    => 'What the employer is billed, per hour.',
            ],
            'p_ac_hourly_rate' => [
                'name'       => 'p_ac_hourly_rate',
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'null'       => true,
                'default'    => null,
                'comment'    => 'What the applicant is paid, per hour. The rate shown publicly.',
            ],
        ]);
    }

    public function down()
    {
        // Going back rounds the cents off - there is nowhere in an int to keep
        // them. A rate saved as 42.50 comes back as 43.
        $this->forge->modifyColumn('post_job', [
            'p_hourly_rate' => [
                'name'    => 'p_hourly_rate',
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
            'p_ac_hourly_rate' => [
                'name'    => 'p_ac_hourly_rate',
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
        ]);
    }
}
