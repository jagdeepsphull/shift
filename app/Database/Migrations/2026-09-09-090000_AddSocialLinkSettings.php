<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The three social profiles the front end links to, editable at /sadmin/settings.
 *
 * They were written into two views by hand - the site footer and the contact
 * page - and had drifted apart: the footer pointed at the reliefshifts accounts
 * while the contact page pointed at pickashift, and the Facebook link in both
 * was "#", which goes nowhere. One row in `settings` gives both pages the same
 * answer and puts changing it in the agency's hands.
 *
 * The seed is the footer's set, because that is the one every page carried.
 * Facebook starts blank on purpose: the icon is hidden while a link is empty,
 * which is better than an icon that does nothing.
 *
 * `s_disclaimer`, `s_terms_conditions` and `s_privacy_policy` are deliberately
 * left in place. The editors for them come off the settings screen with this
 * change, but the text stays in the table for whenever it is wanted again.
 */
class AddSocialLinkSettings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('settings', [
            's_facebook_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_agency_copy_email',
                'comment'    => 'Footer and contact-page Facebook link. Blank hides the icon.',
            ],
            's_twitter_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_facebook_url',
                'comment'    => 'Footer and contact-page X/Twitter link. Blank hides the icon.',
            ],
            's_instagram_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 's_twitter_url',
                'comment'    => 'Footer and contact-page Instagram link. Blank hides the icon.',
            ],
        ]);

        $this->db->table('settings')
            ->where('s_id', 1)
            ->update([
                's_facebook_url'  => '',
                's_twitter_url'   => 'https://x.com/reliefshifts',
                's_instagram_url' => 'https://www.instagram.com/reliefshifts',
            ]);
    }

    public function down()
    {
        $this->forge->dropColumn('settings', ['s_facebook_url', 's_twitter_url', 's_instagram_url']);
    }
}
