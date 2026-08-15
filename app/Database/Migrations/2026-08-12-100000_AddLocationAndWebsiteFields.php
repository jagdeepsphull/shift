<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A store's map link, and a web address for both an employer and a store.
 *
 * Two requests, one migration, because they add plain optional columns to the
 * same two tables and there is nothing to be gained from migrating twice.
 *
 *  - `store.s_location_label` / `store.s_map_url` - where a location actually
 *    is. The postal address is already on the row, but an applicant standing
 *    outside a plaza needs the pin, and a pharmacy inside a supermarket is not
 *    findable from its street address alone. The label is what to call the spot
 *    ("Inside Real Canadian Superstore"); the URL is the Google Maps link,
 *    pasted by hand from Share > Copy link.
 *
 *  - `users.u_website` - the employer's own site. On a multi-store owner that
 *    is the group's corporate site; on a single store it is that store's page.
 *
 *  - `store.s_website` - a location with a page of its own, which a chain's
 *    individual branches often have. Blank means "use the owner's".
 *
 * All four are optional and default to empty, so nothing that exists today has
 * to be backfilled and no form becomes harder to complete.
 */
class AddLocationAndWebsiteFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('store', [
            's_location_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
                'after'      => 's_address',
                'comment'    => 'What to call the spot, when the street address alone will not find it',
            ],
            's_map_url' => [
                // Google's share links are long: a place URL with a CID and a
                // plus-code runs past 255 characters on its own.
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'default'    => '',
                'after'      => 's_location_label',
                'comment'    => 'Google Maps link for this location, pasted from Share > Copy link',
            ],
            's_website' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
                'after'      => 's_phone',
                'comment'    => "This location's own page. Blank falls back to the owner's u_website.",
            ],
        ]);

        $this->forge->addColumn('users', [
            'u_website' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
                'after'      => 'u_email',
                'comment'    => "The employer's web address - the group's site for a multi-store owner",
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('store', ['s_location_label', 's_map_url', 's_website']);
        $this->forge->dropColumn('users', 'u_website');
    }
}
