<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Records when the day-before reminder was sent for a booking.
 *
 * The reminder job keys off this column, so running it twice in one day - or
 * running the web trigger and the cron job on the same host - cannot send the
 * applicant a second copy.
 */
class AddShiftReminderSentAt extends Migration
{
    public function up()
    {
        $this->forge->addColumn('stu_saved_applied_jobs', [
            'sj_reminder_sent_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'sj_accept_date',
                'comment' => 'When the day-before shift reminder was e-mailed. NULL = not sent.',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('stu_saved_applied_jobs', 'sj_reminder_sent_at');
    }
}
