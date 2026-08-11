<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * CLI counterpart of the legacy `cronjob1.php` script.
 *
 * Schedule it with:  php spark jobs:expire
 */
class ExpireJobs extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'jobs:expire';
    protected $description = 'Marks shifts (post_job) whose date has already passed as Inactive (Expired).';

    public function run(array $params)
    {
        helper('common');

        CLI::write('Expired ' . expire_past_shifts() . ' shift(s).', 'green');
    }
}
