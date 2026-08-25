<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Application lookup lists.
 *
 * These are the custom `$config['...']` arrays that lived at the bottom of the
 * CodeIgniter 3 `application/config/config.php`. In controllers and views they
 * are read with the `config_item()` helper (see app/Helpers/common_helper.php),
 * which keeps the original CI3 call signature working:
 *
 *     $usertype = config_item('usertype');
 */
class AppSettings extends BaseConfig
{
    /** Main user types. */
    public array $usertype = [
        1 => 'Employer',
        2 => 'Applicant',
    ];

    /**
     * The two kinds of employer, keyed by the number `users.u_emp_role` holds.
     *
     *   1 -> Owner   - owns its locations and adds them itself
     *   2 -> Manager - runs one of a group's existing stores, so it answers to
     *                  that group (`u_parent_id`) and points at the store
     *                  (`u_store_id`)
     *
     * There used to be a third, "Owner (Individual Store)", which was role 2
     * with no group - the same number as a manager, told apart only by whether
     * `u_parent_id` was set. It is gone, and with it the ambiguity: the role
     * alone now says which kind an account is, which is why every `filter`
     * below is a single column.
     *
     * `slug` is what appears in a URL (`/sadmin/employer/owner`); the key is
     * what goes in the database. Keeping both means links stay readable while
     * the stored value stays a number.
     *
     * `filter` is a where array for the `users` table. An account from before
     * these kinds existed carries role 0 and matches none of them, which is why
     * every screen keeps an "All Employers" entry above the list - nothing is
     * ever hidden by the filter.
     *
     * @var array<int, array{slug: string, label: string, short: string, filter: array<string, int>}>
     */
    public array $employerKinds = [
        1 => [
            'slug'   => 'owner',
            'label'  => 'Owners',
            'short'  => 'Owner',
            'filter' => ['u_emp_role' => 1],
        ],
        2 => [
            'slug'   => 'manager',
            'label'  => 'Managers',
            'short'  => 'Manager',
            'filter' => ['u_emp_role' => 2],
        ],
    ];

    /**
     * What the public "Select User Type" dropdown offers, and what each choice
     * means in the database.
     *
     * The employer entries are the `employerKinds` codes above, so the two
     * lists cannot disagree about what an Owner is. `applicant` is not an
     * employer kind at all - it is `u_usertype` 2 - so it carries no
     * `empRole`, and register() reads that as "this is not an employer".
     *
     * @var array<int, array{label: string, userType: int, empRole: int|null}>
     */
    public array $registerTypes = [
        1 => ['label' => 'Owner',     'userType' => 1, 'empRole' => 1],
        2 => ['label' => 'Manager',   'userType' => 1, 'empRole' => 2],
        3 => ['label' => 'Applicant', 'userType' => 2, 'empRole' => null],
    ];

    /** Sub types of Candidate/Job seekers. */
    public array $usersubtype = [
        1 => 'Pharmacist',
        2 => 'Pharmacy Assistant',
        3 => 'Registered Pharmacy Technician',
        4 => 'Physician',
        5 => 'Nurse',
        6 => 'Personal Support Worker',
    ];

    public array $posttype = [
        0 => 'Shifts',
        1 => 'Full Time',
    ];

    /**
     * Shift status.
     *
     * 2 was "Rejected"; it reads "Inactive" now. 4 is set by the nightly expiry
     * job and never chosen by hand - it is kept as its own code rather than
     * reusing 2 so the monthly reports can still tell "nobody wanted it" apart
     * from "the date went by", which one shared code would have thrown away.
     *
     * Anything the agency may pick from a dropdown is in `$approvedSelectable`.
     */
    public array $approved = [
        0 => 'Pending',
        1 => 'Live',
        2 => 'Inactive',
        3 => 'Closed',
        4 => 'Inactive (Expired)',
    ];

    /** Statuses the agency may set by hand - excludes the automatic ones. */
    public array $approvedSelectable = [
        0 => 'Pending',
        1 => 'Live',
        2 => 'Inactive',
        3 => 'Closed',
    ];

    /** Shift statuses that mean "over" - hidden from the public shift list. */
    public array $approvedInactive = [2, 3, 4];

    public array $application_approved = [
        0 => 'Pending',
        1 => 'Booked',
        2 => 'Rejected',
    ];

    public array $serchexp = [
        1  => '0 - 1',
        12 => '1 - 2',
        24 => '2 - 4',
        46 => '4 - 6',
        68 => '6 - 8',
        89 => '8 +',
    ];

    public array $search_salary = [
        500  => '$0 - $500',
        1000 => '$500 - $1000',
        1500 => '$1000 - $1500',
        2000 => '$1500 - $2000',
        2500 => '$2000 - $2500',
        3000 => '$25000 - $3000',
    ];

    public array $jobtype = [
        0 => 'Blue Collar',
        1 => 'White Collar',
    ];

    public array $featured = [
        0 => 'Non Featured',
        1 => 'Featured',
    ];

    public array $categorytype = [
        0 => 'Unskilled',
        1 => 'Skilled',
        2 => 'Qualified',
    ];

    public array $mr = [
        0 => 'Mr',
        1 => 'Mrs',
        2 => 'Ms',
    ];

    public array $worked_abroad = [
        0 => 'No',
        1 => 'Yes',
    ];

    public array $disabled = [
        0 => 'No',
        1 => 'Yes',
    ];

    public array $ecr = [
        0 => 'None',
        1 => 'ECR',
        2 => 'ECNR',
    ];

    public array $gender = [
        0 => 'Male',
        1 => 'Female',
        2 => 'Both',
    ];

    /**
     * The e-mails a user can be opted out of, keyed by the code stored in
     * `users.u_email_blocked` (comma separated, same shape as p_skills).
     *
     * `template` is the app/Views/emails/ file the send site renders, which is
     * how a guard finds the code for the mail it is about to send.
     *
     * reset-password is deliberately NOT here. It is the only channel the
     * reset token ever travels on, and Front.php tells the visitor the link
     * was sent whether or not it was - an opted-out account could never be
     * recovered, against a screen that keeps saying it can. It is always sent.
     * (`contact` goes to the administrator and `test` is CLI-only, so neither
     * belongs on a per-user list either.)
     *
     * booking-cancelled is not here for the same reason. It is not news about
     * the service: it is the correction to a booking confirmation we have
     * already sent, and somebody who does not get it turns up to work a shift
     * that is not theirs. Always sent.
     *
     * `audience` is which side of the site can ever receive it - an applicant is
     * never sent "your shift is live", and an employer is never sent the
     * day-before reminder, because neither send site would look at them. It is
     * what Manage Email offers each account, through `emailTypesFor()`: a
     * checkbox for an e-mail that cannot arrive is a promise the site does not
     * keep either way it is left.
     *
     * @var array<int, array{template: string, label: string, audience: string}>
     */
    public array $emailTypes = [
        1 => ['template' => 'welcome',           'label' => 'Welcome (on registration)',           'audience' => 'both'],
        2 => ['template' => 'account-approved',  'label' => 'Account approved',                    'audience' => 'both'],
        3 => ['template' => 'shift-posted',      'label' => 'Shift posted (your shift is live)',   'audience' => 'employer'],
        4 => ['template' => 'booking-applicant', 'label' => 'Booking confirmation (as applicant)', 'audience' => 'applicant'],
        5 => ['template' => 'booking-employer',  'label' => 'Booking confirmation (as employer)',  'audience' => 'employer'],
        6 => ['template' => 'shift-reminder',    'label' => 'Day-before shift reminder',           'audience' => 'applicant'],
    ];

    /**
     * Country code put in front of a phone number typed without one, for the
     * WhatsApp links in the back office.
     *
     * '1' is Canada, which is where the site operates. Ten digits is all
     * anybody types into the phone field, and WhatsApp needs the code - but
     * ten digits alone cannot say which country: 8360086621 is a Canadian
     * number here and an Indian mobile there. Hence a setting rather than a
     * guess. Set it to '91' to message Indian numbers while testing.
     *
     * A number typed with its own code - "+91 98765 43210" - is always used
     * exactly as typed and ignores this, so a single test number needs no
     * change here at all.
     */
    public string $phoneCountryCode = '1';

    /**
     * The number the site itself answers on, shown under the side menu behind
     * every login and linked into WhatsApp.
     *
     * Written the way it is printed - the link normalises it through
     * `whatsappNumber()` - and kept here rather than in the two sidebars so
     * changing it is one edit and not a hunt through the views. The same
     * number is on the Contact page.
     */
    public string $supportPhone = '+1 (905) 304-7303';

    /**
     * How many wrong passwords in a row an account takes before it stops
     * answering them, and for how long.
     *
     * Eight is generous for somebody who has forgotten which of their two
     * passwords they used here, and short work of a list of ten thousand. The
     * lock is per account and lifts by itself; Manage Employers can also clear
     * it by saving the row, which resets the counter.
     *
     * Set either to 0 to switch the lock off entirely.
     *
     * @see \App\Models\CustomModel::loginLockRemaining()
     */
    public int $loginMaxAttempts = 8;

    public int $loginLockoutMinutes = 15;

    public array $status = [
        0 => 'Deactive',
        1 => 'Active',
    ];

    public array $marital = [
        0 => 'Married',
        1 => 'Unmarried',
        2 => 'Divorced',
    ];

    public array $religion = [
        0  => 'Christianity',
        1  => 'Islam',
        2  => 'Hinduism',
        3  => 'Buddhism',
        4  => 'Sikhism',
        5  => 'Taoism',
        6  => 'Judaism',
        7  => 'Confucianism',
        8  => 'Baháí',
        9  => 'Shinto',
        10 => 'Jainism',
        11 => 'Zoroastrianism',
    ];

    public array $qualification = [
        0 => 'Below 10th',
        1 => 'Primary - 5th Pass',
        2 => 'Medium - 8th Pass',
        3 => 'Matric - 10th Pass',
        4 => 'Intermediate - 12th Pass',
        5 => 'Graduate',
        6 => 'Post Graduate',
        7 => 'Others',
    ];

    public array $appliedstatus = [
        0 => 'Saved',
        1 => 'Applied',
        2 => 'Shortlisted',
        3 => 'Rejected',
        4 => 'Resubmit',
        5 => 'Booked',
        // Where an approved application ends up, and where a booking the
        // administrator made outright starts. Missing here, it printed an
        // undefined-index notice in place of the status on every such row.
        6 => 'Booked',
    ];

    /** Sender address used by the mail helper (CI3 `send_email()`). */
    public string $mailFromEmail = 'donotreply@pickashift.ca';

    public string $mailFromName = 'PickAShift';

    /**
     * Fallback for the address copied on booking e-mails. The live value is
     * `settings.s_agency_copy_email`, editable at /sadmin/settings.
     */
    public string $agencyCopyEmail = 'info@reliefshifts.com';

    /**
     * The address on every "your shift is live", whoever else is told.
     *
     * The shift form asks who at the store to tell - its owner, its manager,
     * both or neither (`post_job.p_email_to`) - and this address is added to
     * whatever that comes to. It is shown on the form as a fixed recipient, so
     * an administrator can see where the mail is going without knowing the
     * config.
     *
     * It is what makes "neither" safe. Neither is a real answer: some shifts
     * are arranged over the phone and the pharmacy does not want the mail. The
     * shift still gets announced here, rather than going live as an event with
     * no record outside the database. The same holds when the chosen side
     * cannot be reached at all - a store with no manager on it, or a recipient
     * who opted out in Manage Email - and the send site logs which of them it
     * was.
     */
    public string $shiftEmailFallback = 'team@pickashift.ca';
}
