<?php

namespace App\Libraries;

/**
 * Visit tracker, ported from CI3 `application/libraries/Iptracker.php`.
 *
 * The CI3 version ran itself at the bottom of the file (it was autoloaded);
 * here it is wired to the `pre_system`-equivalent event in app/Config/Events.php,
 * so every page view is still written to the `visit_log` table.
 */
class Iptracker
{
    public static function get_ip_address(): string
    {
        return service('request')->getIPAddress();
    }

    public static function get_page_visit(): string
    {
        return current_url();
    }

    public function save_site_visit(): void
    {
        $page = self::get_page_visit();
        $seg  = explode('-', $page);

        if (in_array('dimiyo', $seg, true)) {
            return;
        }

        db_connect()->table('visit_log')->insert([
            'ip'         => self::get_ip_address(),
            'page_view'  => $page,
            'user_agent' => (string) service('request')->getUserAgent(),
            'date'       => time(),
        ]);
    }
}
