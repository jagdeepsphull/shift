<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use DateTime;

/**
 * Fills `post_job.p_date_start` for rows the migration could not parse.
 *
 * The migration handles the dd-mm-yyyy text the date picker writes. A handful of
 * older rows were typed by hand ("Feb 21, 2026", "March 17 2026"), so they are
 * parsed here with a looser reader. Anything still ambiguous - a date with no
 * year, for instance - is reported and left alone rather than guessed at.
 *
 *   php spark shifts:backfill-dates          report only
 *   php spark shifts:backfill-dates --write  apply the parseable ones
 */
class BackfillShiftDates extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'shifts:backfill-dates';
    protected $description = 'Parses hand-typed shift dates into post_job.p_date_start.';
    protected $usage       = 'shifts:backfill-dates [--write]';
    protected $options     = ['--write' => 'Save the parsed dates instead of only reporting them.'];

    public function run(array $params)
    {
        $write = array_key_exists('write', $params) || CLI::getOption('write');
        $db    = Database::connect();

        $rows = $db->table('post_job')
            ->select('p_id, p_dates')
            ->where('p_date_start', null)
            ->where('p_dates IS NOT NULL')
            ->where("p_dates <> ''")
            ->get()
            ->getResult();

        if ($rows === []) {
            CLI::write('Nothing to do - every shift already has a parsed date.', 'green');

            return;
        }

        $fixed = 0;
        $left  = [];

        foreach ($rows as $row) {
            $parsed = $this->parse($row->p_dates);

            if ($parsed === null) {
                $left[] = $row;

                continue;
            }

            CLI::write(sprintf('  #%-5s %-22s -> %s', $row->p_id, $row->p_dates, $parsed));

            if ($write) {
                $db->table('post_job')->where('p_id', $row->p_id)->update(['p_date_start' => $parsed]);
            }

            $fixed++;
        }

        CLI::newLine();
        CLI::write($write ? "Updated {$fixed} shift(s)." : "{$fixed} shift(s) can be parsed. Re-run with --write to save.", 'green');

        if ($left !== []) {
            CLI::newLine();
            CLI::write('Left alone - no year given, so the date cannot be established:', 'yellow');
            foreach ($left as $row) {
                CLI::write(sprintf('  #%-5s %s', $row->p_id, $row->p_dates), 'yellow');
            }
            CLI::write('Set these by editing the shift, or update p_date_start directly.', 'yellow');
        }
    }

    /**
     * Read a hand-typed date. Returns Y-m-d, or null when it is not safe to guess.
     */
    private function parse(string $value): ?string
    {
        $value = trim($value);

        // A year must be present; "December 17" alone is not a date.
        if (! preg_match('/(19|20)\d{2}/', $value)) {
            return null;
        }

        // Tidy the separators that trip strtotime up: "December 18,2025".
        $tidy = preg_replace('/,(?=\S)/', ', ', $value);

        $timestamp = strtotime((string) $tidy);

        if ($timestamp === false) {
            return null;
        }

        $date = (new DateTime())->setTimestamp($timestamp)->format('Y-m-d');

        // Refuse anything wildly outside the plausible range for a shift.
        $year = (int) substr($date, 0, 4);

        return ($year >= 2015 && $year <= 2100) ? $date : null;
    }
}
