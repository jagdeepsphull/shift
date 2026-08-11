<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * E-mails every applicant booked for a shift tomorrow.
 *
 * Schedule it once a day, in the morning:
 *
 *   php spark shifts:remind
 *
 * Running it more than once a day is safe - each booking is stamped when its
 * reminder goes out and is skipped afterwards.
 */
class RemindShifts extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'shifts:remind';
    protected $description = 'E-mails applicants booked for a shift tomorrow.';
    protected $usage       = 'shifts:remind [date]';
    protected $arguments   = ['date' => 'Optional Y-m-d shift date. Defaults to tomorrow.'];

    public function run(array $params)
    {
        helper(['common', 'ci3compat']);

        $date = $params[0] ?? null;

        if ($date !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            CLI::error("'{$date}' is not a Y-m-d date.");

            return;
        }

        $result = send_shift_reminders($date);

        if ($result['skipped'] === -1) {
            CLI::error('Column sj_reminder_sent_at is missing. Run: php spark migrate');

            return;
        }

        CLI::write(sprintf(
            'Reminders for %s - sent: %d, failed: %d, skipped: %d',
            $date ?? date('Y-m-d', strtotime('+1 day')),
            $result['sent'],
            $result['failed'],
            $result['skipped']
        ), $result['failed'] > 0 ? 'yellow' : 'green');
    }
}
