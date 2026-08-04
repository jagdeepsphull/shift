<?php

namespace App\Controllers;

/**
 * Scheduled maintenance tasks.
 *
 * Replaces the stand-alone CI3 `cronjob1.php`, which opened its own mysqli
 * connection. Reachable at `/cron/expire_jobs` (the old `/cronjob1.php` URL is
 * routed here too) or from the CLI with `php spark jobs:expire`.
 */
class Cron extends BaseController
{
    /**
     * Close shifts whose date has passed.
     *
     * `post_job.p_dates` is stored as a dd-mm-yyyy string, hence STR_TO_DATE.
     */
    public function expire_jobs()
    {
        $current_date = date('d-m-Y');

        $this->db->query(
            "UPDATE post_job SET p_status = 0 WHERE STR_TO_DATE(p_dates, '%d-%m-%Y') < STR_TO_DATE(?, '%d-%m-%Y')",
            [$current_date]
        );

        $affected = $this->db->affectedRows();

        echo 'Expired jobs updated successfully. (' . $affected . ' row(s))';
    }
}
