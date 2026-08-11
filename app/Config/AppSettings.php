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
     * The three employer kinds the registration form offers (change request
     * B4). All are `users.u_usertype` 1 and differ only by `u_emp_role` plus
     * `u_parent_id`, so the back office needs this map to tell them apart:
     *
     *   manager          -> role 2 answering to a group (u_parent_id set)
     *   owner_multi      -> role 1, adds its own stores
     *   owner_individual -> role 2 with no group (u_parent_id 0)
     *
     * `filter` is a where array for the `users` table. Accounts registered
     * before B4 have role 0 and match none of them, which is why the sidebar
     * keeps an "All Employers" entry above the three - nothing gets hidden.
     *
     * @var array<string, array{label: string, short: string, filter: array<string, int>}>
     */
    public array $employerKinds = [
        'manager' => [
            'label'  => 'Managers',
            'short'  => 'Manager',
            'filter' => ['u_emp_role' => 2, 'u_parent_id >' => 0],
        ],
        'owner_multi' => [
            'label'  => 'Owners (Multi Store)',
            'short'  => 'Owner (Multi Store)',
            'filter' => ['u_emp_role' => 1],
        ],
        'owner_individual' => [
            'label'  => 'Owners (Individual Store)',
            'short'  => 'Owner (Individual Store)',
            'filter' => ['u_emp_role' => 2, 'u_parent_id' => 0],
        ],
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
    ];

    /** Sender address used by the mail helper (CI3 `send_email()`). */
    public string $mailFromEmail = 'pickashift@reliefshifts.com';

    public string $mailFromName = 'PickAShift';

    /**
     * Fallback for the address copied on booking e-mails. The live value is
     * `settings.s_agency_copy_email`, editable at /sadmin/settings.
     */
    public string $agencyCopyEmail = 'info@reliefshifts.com';
}
