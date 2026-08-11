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
     * Mark shifts whose date has passed as Inactive (Expired).
     *
     * The work itself lives in `expire_past_shifts()` so this and the
     * `jobs:expire` command cannot drift apart.
     */
    public function expire_jobs()
    {
        helper('common');

        echo 'Expired ' . expire_past_shifts() . ' shift(s).';
    }

    /**
     * E-mail applicants booked for a shift tomorrow.
     *
     * Web-triggered twin of `php spark shifts:remind`, for hosts without
     * command-line cron. Safe to hit more than once a day.
     */
    public function remind_shifts()
    {
        helper('common');

        $result = send_shift_reminders();

        if ($result['skipped'] === -1) {
            echo 'Column sj_reminder_sent_at is missing. Run: php spark migrate';

            return;
        }

        echo sprintf('Reminders - sent: %d, failed: %d, skipped: %d', $result['sent'], $result['failed'], $result['skipped']);
    }
}
