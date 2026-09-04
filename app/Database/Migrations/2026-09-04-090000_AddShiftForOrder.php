<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The order the Shift For list is shown in, chosen by the agency.
 *
 * Every screen that offers these read them alphabetically, which puts "Dental
 * Assistant" above "Pharmacist (R Ph)" on a site where all but a handful of
 * shifts are for a pharmacist. Alphabetical is an accident of spelling, not an
 * order anybody wanted; this column is the order somebody picked, set with the
 * arrows on the back-office list and read by the dropdowns on both sides of
 * the login.
 *
 * INT rather than a TINYINT: the list is nine rows today and the column costs
 * nothing either way, and a positions column that overflows at 127 is a trap
 * for whoever grows the list.
 *
 * The backfill is what the list already looked like - the alphabetical order
 * every screen was reading - so nothing moves on the day this runs. Numbering
 * from 1 leaves 0 meaning "never placed", which is what a row inserted by hand
 * carries; `SHIFT_FOR_ORDER` sorts on the name after this column, so such a row
 * sits at the top in its alphabetical place rather than in an order nobody set.
 */
class AddShiftForOrder extends Migration
{
    public function up()
    {
        $this->forge->addColumn('shift_for', [
            'sf_order' => [
                'type'       => 'INT',
                'null'       => false,
                'default'    => 0,
                'after'      => 'sf_name',
                'comment'    => 'Where this sits in the list. Set by the arrows in /sadmin/shift_for.',
            ],
        ]);

        // Deactivated rows are numbered too. Status says whether a row may be
        // picked, not where it sits, and one switched back on later should
        // return to its place rather than to the top.
        $rows = $this->db->table('shift_for')
            ->select('sf_id')
            ->orderBy('sf_name', 'asc')
            ->get()
            ->getResult();

        $position = 0;

        foreach ($rows as $row) {
            $this->db->table('shift_for')
                ->where('sf_id', (int) $row->sf_id)
                ->update(['sf_order' => ++$position]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('shift_for', 'sf_order');
    }
}
