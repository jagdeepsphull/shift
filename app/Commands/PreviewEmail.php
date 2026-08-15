<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Print one of the lifecycle e-mails, built from a real row, without sending it.
 *
 * Asked for as "what does the message at each stage actually look like": the
 * bodies live in `app/Views/emails/` and are only otherwise visible by causing
 * the event and reading the inbox. This renders one against real data, so the
 * wording, the fields and the links can be checked - and diffed - before
 * anybody books a shift to find out.
 *
 *   php spark email:preview booking-applicant 412
 *   php spark email:preview booking-employer 412 > booking.html
 *
 * It writes to stdout and sends nothing. Redirect it to a file and open that in
 * a browser to see the message as a recipient would.
 */
class PreviewEmail extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'email:preview';
    protected $description = 'Renders a lifecycle e-mail for a real shift, to stdout. Sends nothing.';
    protected $usage       = 'email:preview <template> <shiftId> [applicantId]';
    protected $arguments   = [
        'template'    => 'booking-applicant, booking-employer or shift-posted',
        'shiftId'     => 'post_job.p_id to build the message from',
        'applicantId' => 'users.u_id of the applicant, for booking-employer',
    ];

    /**
     * Every stage that mails somebody, in the order an account meets them.
     *
     * The ones before `shift-posted` describe a person rather than a shift, so
     * they need no shift id.
     */
    private const TEMPLATES = [
        'welcome',
        'account-approved',
        'reset-password',
        'shift-posted',
        'booking-applicant',
        'booking-employer',
        'shift-reminder',
        'booking-cancelled',
    ];

    /** Stages that describe an account, not a shift. */
    private const ACCOUNT_TEMPLATES = ['welcome', 'account-approved', 'reset-password'];

    public function run(array $params)
    {
        helper('common');

        $template = (string) ($params[0] ?? '');
        $shiftId  = (int) ($params[1] ?? 0);

        if (! in_array($template, self::TEMPLATES, true)) {
            CLI::error('Unknown template "' . $template . '". One of: ' . implode(', ', self::TEMPLATES));

            return EXIT_ERROR;
        }

        $custom = model('App\Models\CustomModel');

        // The account-stage messages carry no shift, so they are rendered from
        // a name alone and need nothing from the database.
        if (in_array($template, self::ACCOUNT_TEMPLATES, true)) {
            CLI::write($this->accountStage($template));

            return EXIT_SUCCESS;
        }

        $shift = $custom->get_where_row('post_job', ['p_id' => $shiftId]);

        if (! $shift) {
            CLI::error('No shift with p_id ' . $shiftId . '. Pass one: email:preview ' . $template . ' <shiftId>');

            return EXIT_ERROR;
        }

        $employer = $custom->get_where_row('users', ['u_id' => $shift['u_id']]);

        if (! $employer) {
            CLI::error('Shift ' . $shiftId . ' has no employer row.');

            return EXIT_ERROR;
        }

        // The same call the controller makes: the shift's own store, falling
        // back to the employer's login columns for a shift that has none.
        $store = shiftStore((object) $shift);

        if ($template === 'shift-posted') {
            CLI::write(email_body('shift-posted', [
                'title'       => 'Your shift is live',
                'name'        => trim($employer['u_fname'] . ' ' . $employer['u_lname']),
                'shift_title' => $shift['p_job_title'],
            ]));

            return EXIT_SUCCESS;
        }

        if ($template === 'booking-applicant') {
            CLI::write(email_body('booking-applicant', [
                'title'            => 'You have been approved for a shift',
                'name'             => 'Preview Applicant',
                'shift'            => $shift,
                'employer'         => $employer,
                'store'            => $store,
                'approval_comment' => '',
            ]));

            return EXIT_SUCCESS;
        }

        // Last of the applicant's three: the correction to the first one, sent
        // when the booking is taken back off them on the shift form. Like
        // booking-applicant it describes the shift, so it needs no applicant
        // row - only the name it opens with.
        if ($template === 'booking-cancelled') {
            CLI::write(email_body('booking-cancelled', [
                'title'    => 'Your shift booking has been cancelled',
                'name'     => 'Preview Applicant',
                'shift'    => $shift,
                'employer' => $employer,
                'store'    => $store,
            ]));

            return EXIT_SUCCESS;
        }

        // booking-employer needs an applicant to describe. Prefer the one
        // actually booked on this shift; fall back to whoever was asked for.
        $applicantId = (int) ($params[2] ?? 0);

        $applicant = $custom->db()->table('stu_saved_applied_jobs s')
            ->select('u.*')
            ->join('users u', 'u.u_id = s.u_id', 'inner')
            ->where('s.p_id', $shiftId)
            ->where('s.sj_is_approved', 1)
            ->get()
            ->getRow();

        if (! $applicant && $applicantId > 0) {
            $applicant = $custom->db()->table('users')->getWhere(['u_id' => $applicantId])->getRow();
        }

        if (! $applicant) {
            CLI::error('Shift ' . $shiftId . ' has nobody booked on it. Pass an applicant id as the third argument.');

            return EXIT_ERROR;
        }

        if ($template === 'shift-reminder') {
            CLI::write(email_body('shift-reminder', [
                'title'    => 'Your shift is tomorrow',
                'name'     => trim($applicant->u_fname . ' ' . $applicant->u_lname),
                'shift'    => $shift,
                'employer' => $employer,
                'store'    => $store,
            ]));

            return EXIT_SUCCESS;
        }

        CLI::write(email_body('booking-employer', [
            'title'          => 'An applicant has been approved for your shift',
            'name'           => trim($employer['u_fname'] . ' ' . $employer['u_lname']),
            'applicant_name' => trim($applicant->u_fname . ' ' . $applicant->u_lname),
            'applicant'      => $applicant,
            'shift'          => $shift,
            'store'          => $store,
        ]));

        return EXIT_SUCCESS;
    }

    /**
     * The three messages that describe an account rather than a shift.
     */
    private function accountStage(string $template): string
    {
        if ($template === 'welcome') {
            return email_body('welcome', [
                'title' => 'Welcome to PickAShift!',
                'name'  => 'Preview Person',
            ]);
        }

        if ($template === 'account-approved') {
            return email_body('account-approved', [
                'title' => 'Your account has been approved',
                'name'  => 'Preview Person',
            ]);
        }

        return email_body('reset-password', [
            'title'           => 'Reset your password',
            'reset_link'      => base_url('front/reset_password/preview-token'),
            'expires_minutes' => 60,
        ]);
    }
}
