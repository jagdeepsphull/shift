<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sends one test message, to check mail delivery from this server.
 *
 * Replaces the old `/front/test_email` URL, which auto-routing exposed to the
 * whole internet. Run it from the command line only:
 *
 *   php spark email:test someone@example.com
 */
class TestEmail extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'email:test';
    protected $description = 'Sends a test e-mail, to verify delivery from this server.';
    protected $usage       = 'email:test <address>';
    protected $arguments   = ['address' => 'Where to send the test message.'];

    public function run(array $params)
    {
        helper(['common', 'ci3compat']);

        $to = $params[0] ?? CLI::prompt('Send the test message to', null, 'required');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            CLI::error("'{$to}' is not a valid e-mail address.");

            return;
        }

        $settings = config('AppSettings');
        $subject  = 'Test message from ' . $settings->mailFromName;
        $body     = '<p>This is a test message sent at ' . date('Y-m-d H:i:s') . '.</p>'
                  . '<p>If it reached you, mail delivery from this server is working.</p>';

        CLI::write("Sending to {$to} as {$settings->mailFromEmail} ...");

        if (send_email($to, $subject, $body)) {
            CLI::write('Accepted for delivery. Check the inbox, and the junk folder.', 'green');
            CLI::write('If it lands in junk, the domain still needs SPF and DKIM records.', 'yellow');
        } else {
            CLI::error('The mailer refused it. See writable/logs for the reason.');
        }
    }
}
