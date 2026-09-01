<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Scheduled maintenance tasks.
 *
 * Replaces the stand-alone CI3 `cronjob1.php`, which opened its own mysqli
 * connection. Reachable at `/cron/expire_jobs` (the old `/cronjob1.php` URL is
 * routed here too) or from the CLI with `php spark jobs:expire`.
 *
 * The web URLs take a key. They were open to anyone who typed them, and one of
 * them sends e-mail to every applicant booked for tomorrow - a stranger with a
 * loop could have spent the domain's sending reputation in an afternoon. Set
 * `cron.key` in `.env` and put it on the end of the cron URL:
 *
 *   https://pickashift.ca/cron/remind_shifts?key=...
 *
 * The command line is exempt: `php spark shifts:remind` and `jobs:expire` do
 * not come through here, and a shell on the server needs no further proof.
 */
class Cron extends BaseController
{
    /**
     * Mark shifts whose date has passed as Closed (Expired).
     *
     * The work itself lives in `expire_past_shifts()` so this and the
     * `jobs:expire` command cannot drift apart.
     */
    public function expire_jobs()
    {
        if (($denied = $this->denyUnlessKeyed()) !== null) {
            return $denied;
        }

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
        if (($denied = $this->denyUnlessKeyed()) !== null) {
            return $denied;
        }

        helper('common');

        $result = send_shift_reminders();

        if ($result['skipped'] === -1) {
            echo 'Column sj_reminder_sent_at is missing. Run: php spark migrate';

            return;
        }

        echo sprintf('Reminders - sent: %d, failed: %d, skipped: %d', $result['sent'], $result['failed'], $result['skipped']);
    }

    /**
     * Does this request carry the key from `.env`?
     *
     * A wrong key gets the same 404 an unknown URL would, so the existence of
     * these endpoints is not something to be discovered by trying them. No key
     * configured means the URLs are switched off rather than left open - the
     * safe way round for a setting somebody forgets to fill in.
     */
    private function denyUnlessKeyed(): ?ResponseInterface
    {
        $expected = (string) env('cron.key', '');
        $given    = (string) ($this->request->getGet('key') ?? '');

        if ($expected !== '' && $given !== '' && hash_equals($expected, $given)) {
            return null;
        }

        return $this->response->setStatusCode(404)->setBody('Not found');
    }
}
