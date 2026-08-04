<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * CLI counterpart of the legacy `cronjob1.php` script.
 *
 * Schedule it with:  php spark jobs:expire
 */
class ExpireJobs extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'jobs:expire';
    protected $description = 'Closes shifts (post_job) whose date has already passed.';

    public function run(array $params)
    {
        $db = Database::connect();

        $db->query(
            "UPDATE post_job SET p_status = 0 WHERE STR_TO_DATE(p_dates, '%d-%m-%Y') < STR_TO_DATE(?, '%d-%m-%Y')",
            [date('d-m-Y')]
        );

        CLI::write('Expired jobs updated successfully. (' . $db->affectedRows() . ' row(s))', 'green');
    }
}
