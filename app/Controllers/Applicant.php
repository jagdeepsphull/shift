<?php

namespace App\Controllers;

/**
 * Applicant (job seeker) area.
 *
 * Ported from CI3 `application/controllers/Applicant.php`.
 */
class Applicant extends BaseController
{
    /**
     * The rows on a shift that are this applicant's own business.
     *
     * `sj_status` records where a row came from: 1 is an application they made
     * themselves, 6 is a booking an administrator made for them - which is also
     * where an approved application ends up. Both are a shift the applicant
     * holds, so both belong on their screens.
     *
     * Deliberately not 0 or 3. 0 is the never-finished "save for later" feature
     * (`Front::save_job()`), and 3 is an employer's invitation, which is counted
     * on its own and has no screen yet; neither is a shift they applied for or
     * were given.
     */
    private const SHIFT_STATUSES = [1, 6];

    /**
     * Shared set-up + access control that CI3 performed in the constructor.
     */
    protected function setup(): void
    {
        $this->data['settings'] = $this->custom->getSettings();

        $this->data['uid'] = $this->session->userdata('userId');

        // User login status
        $this->isUserLoggedIn         = $this->session->userdata('isUserLoggedIn');
        $this->data['isUserLoggedIn'] = $this->isUserLoggedIn;

        $this->clink         = $this->uri->segment(1);
        $this->data['clink'] = $this->clink;

        // Remember where an applicant was when they get bounced to the login
        // screen, so they land back on the shift they wanted to apply for.
        if ($this->uri->segment(2) === 'apply_job') {
            $this->session->set_userdata('last_url', current_url());
        }

        if (empty($this->isUserLoggedIn)) {
            if ($this->uri->segment(2) !== 'login' && $this->uri->segment(2) !== 'register') {
                ci_redirect('front/login');
            }
        }

        if ($this->session->userdata('userType') != '2') {
            ci_redirect('front/login');
        }

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '1') {
                $this->data['pageTitle']     = 'Employer Dashboard';
                $this->data['myaccountLink'] = base_url('employer/all_jobs');
                $this->data['logoutLink']    = base_url('employer/logout');
            } else {
                $this->data['pageTitle']     = 'Applicant Dashboard';
                $this->data['myaccountLink'] = base_url('applicant/applied_jobs');
                $this->data['logoutLink']    = base_url('applicant/logout');
            }
        }

        $this->userinfo         = $this->custom->get_where('users', ['u_id' => $this->session->userdata('userId')]);
        $this->data['userinfo'] = $this->userinfo;

        // As on the employer's side: an account switched off in the back office
        // stops working now, not when its session happens to expire.
        $this->stopIfAccountClosed($this->userinfo, 'front/login');

        $this->data['usertype']    = $this->config->item('usertype');
        $this->data['usersubtype'] = $this->config->item('usersubtype');
        $this->data['posttype']    = $this->config->item('posttype');

        $this->data['gender']   = $this->config->item('gender');
        $this->data['marital']  = $this->config->item('marital');
        $this->data['religion'] = $this->config->item('religion');

        $this->data['jobtype'] = $this->config->item('jobtype');

        $this->data['appliedstatus']        = $this->config->item('appliedstatus');
        $this->data['application_approved'] = $this->config->item('application_approved');

        $this->data['ecr']           = $this->config->item('ecr');
        $this->data['qualification'] = $this->config->item('qualification');
        $this->data['worked_abroad'] = $this->config->item('worked_abroad');
        $this->data['disabled']      = $this->config->item('disabled');

        $pagesection = $this->uri->segment(2);

        $this->data['dashcls'] = ($pagesection === 'dashboard') ? 'active' : '';
        $this->data['ajcls']   = ($pagesection === 'applied_jobs' || $pagesection === 'apply_job') ? 'active' : '';
        $this->data['sjcls']   = ($pagesection === 'saved_jobs') ? 'active' : '';
        $this->data['alcls']   = ($pagesection === 'alert_jobs') ? 'active' : '';
        $this->data['picls']   = ($pagesection === 'personal_info') ? 'active' : '';
        $this->data['wecls']   = ($pagesection === 'work_experience') ? 'active' : '';
        $this->data['qacls']   = ($pagesection === 'qualification') ? 'active' : '';
        $this->data['crcls']   = ($pagesection === 'certification') ? 'active' : '';
        $this->data['dccls']   = ($pagesection === 'documents') ? 'active' : '';
        $this->data['trcls']   = ($pagesection === 'tranings') ? 'active' : '';
        // Change Password used to share the logout flag, so the link it marks
        // was never the screen you were on.
        $this->data['cpcls']   = ($pagesection === 'change_password') ? 'active' : '';
        $this->data['lgcls']   = ($pagesection === 'logout') ? 'active' : '';
    }

    public function index()
    {
        $this->setup();

        $this->session->set_userdata('site_lang', 'english');

        if ($this->isUserLoggedIn) {
            ci_redirect('applicant/applied_jobs');
        } else {
            ci_redirect('front/login');
        }
    }

    public function switchLang($language = '')
    {
        $this->setup();

        $language = ($language !== '') ? $language : 'english';
        $this->session->set_userdata('site_lang', $language);

        ci_redirect($this->request->getServer('HTTP_REFERER') ?? base_url());
    }

    public function logout()
    {
        $this->session->unset_userdata('isUserLoggedIn');
        $this->session->unset_userdata('userId');
        $this->session->sess_destroy();

        ci_redirect('front/login');
    }

    public function dashboard()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '2') {
            ci_redirect('front/login');
        }

        // Every shift this account holds a row on, applied for or handed to
        // them - the same rule as applied_jobs(), because this number is the
        // count of what that screen lists and the two cannot disagree. Asking
        // for sj_status = 1 alone counted only the applications, so an
        // applicant the agency had booked onto three shifts, who had never
        // applied for anything, read 0 beside a list of three.
        //
        // Bound rather than pasted into the string, like the query below it.
        $this->data['jobapp'] = $this->custom->query(
            'SELECT COUNT(s.sj_id) AS tot'
            . ' FROM stu_saved_applied_jobs s'
            . ' INNER JOIN post_job p ON p.p_id = s.p_id'
            . ' WHERE s.u_id = ? AND s.sj_status IN (' . implode(', ', self::SHIFT_STATUSES) . ')',
            [$this->data['uid']]
        );

        $this->data['invtot'] = $this->custom->query(
            'select count(sj_id) as tot from stu_saved_applied_jobs where u_id = ? and sj_status = 3',
            [$this->data['uid']]
        );

        $this->joblists         = $this->custom->get_data('post_job');
        $this->data['joblists'] = $this->joblists;

        $this->load->applicant_inner_view('dashboard', $this->data);
    }

    public function apply_job()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '2') {
            ci_redirect('front/login');
        }

        $id    = $this->uri->segment(3);
        $uid   = $this->session->userdata('userId');
        $this->session->set_userdata('site_lang', 'english');
        $table = 'stu_saved_applied_jobs';

        $this->data['appliedjob'] = $this->custom->get_where($table, ['p_id' => $id, 'u_id' => $uid]);

        $this->data['applied'] = 0;

        if (count($this->data['appliedjob']) > 0) {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Shift Already applied!</div>');
            ci_redirect('applicant/applied_jobs');
        }

        if ($this->input->post('applyjob')) {
            $jobdet = $this->custom->get_where('post_job', ['p_id' => $id]);

            $rowData = cleanArray($this->input->post());

            $rowData['p_id']            = $id;
            $rowData['u_id']            = $uid;
            $rowData['agency_id']       = $jobdet[0]->u_id;
            $rowData['sj_status']       = 1;
            $rowData['sj_applied_date'] = date('Y-m-d H:i:s');
            unset($rowData['applyjob'], $rowData['_wysihtml5_mode']);

            if (insertQryNoValidation($table, $rowData)) {
                ci_redirect('applicant/applied_jobs');
            }

            foreach ($rowData as $ky => $vl) {
                $this->data[$ky] = $vl;
            }
        } else {
            getTableInfo($this->dbname, $table);
        }

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '2') {
                $this->data['applylink'] = base_url('front/applyjob');
            } else {
                $this->data['applylink'] = base_url('employer/all_jobs');
            }
        } else {
            $this->data['applylink'] = base_url('applicant/applied_jobs');
        }

        $this->data['jobdetail'] = $this->custom->get_where('post_job', ['p_id' => $id]);

        $this->load->applicant_inner_view('apply_job', $this->data);
    }

    public function personal_info()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '2') {
            ci_redirect('front/login');
        }

        if ($this->input->post('updateprofile')) {
            $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
            $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
            $this->form_validation->set_rules('u_phone', 'Mobile Number', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);

            $userData = cleanArray($this->input->post());

            $userData['u_email']  = $this->userinfo[0]->u_email;
            $userData['modified'] = date('Y-m-d H:i:s');
            unset($userData['updateprofile']);

            if (updateQry('users', $userData, ['u_id' => $this->data['uid']])) {
                /* photo upload */
                if ($this->request->getFile('u_photo') !== null && $this->request->getFile('u_photo')->isValid()) {
                    $upload_param = [
                        'filename' => 'u_photo',
                        'path'     => 'profile',
                        'types'    => 'jpg|jpeg|png',
                        'size'     => '2048',
                        'width'    => '1024',
                        'height'   => '1024',
                        'table'    => 'users',
                        'field'    => 'u_photo',
                        'pkfield'  => 'u_id',
                        'pkval'    => $this->data['uid'],
                    ];

                    $filestaus = fileupload($upload_param);

                    if ($filestaus['error'] == 1) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">File format not supported.</div>');
                    } else {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">File has been uploaded.</div>');
                    }
                }

                ci_redirect('applicant/personal_info');
            } else {
                foreach ($userData as $ky => $vl) {
                    $this->data[$ky] = $vl;
                }
            }
        } else {
            getTableInfo($this->dbname, 'users', ['u_id' => $this->userinfo[0]->u_id]);
        }

        $this->data['province'] = $this->custom->get_data('province');

        $this->load->applicant_inner_view('personal_info', $this->data);
    }

    public function applied_jobs()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '2') {
            ci_redirect('front/login');
        }

        // Shifts the applicant applied for and shifts the agency booked them
        // onto, which is the whole of what this screen is for: it is the only
        // place an applicant sees their own shifts. It asked for sj_status = 1,
        // so a booking made from the admin's shift form - written at 6, since
        // nobody applied for it - never appeared here at all. The applicant was
        // sent the booking e-mail, signed in to check it, and found nothing.
        //
        // The Status column reads `sj_is_approved`, which is already right for
        // these rows: 1 shows as Booked, and 2 - set when the booking is moved
        // to somebody else - as Assigned To Someone Else.
        $this->appliedjobs = $this->custom->query(
            'SELECT ssaj.*, pj.* FROM stu_saved_applied_jobs ssaj '
            . 'JOIN post_job pj ON ssaj.p_id = pj.p_id '
            . 'JOIN users u ON u.u_id = ssaj.u_id '
            . 'WHERE u.u_id = ? AND ssaj.sj_status IN (' . implode(', ', self::SHIFT_STATUSES) . ') '
            . 'ORDER BY ' . shiftDateOrderBy('pj'),
            [$this->data['uid']]
        );

        $this->data['appliedjobs'] = $this->appliedjobs;

        $this->load->applicant_inner_view('applied_jobs', $this->data);
    }

    /*
     * `saved_jobs()` was removed on 4 Aug 2026. The "save a shift for later"
     * feature was never finished: there is no saved_jobs view, its query asked
     * for a `u_a_comp_name` column that does not exist in this database, and the
     * navigation link has been commented out for as long as the file has existed.
     * The URL returned a server error on every request. Applied shifts are at
     * /applicant/applied_jobs.
     */

    public function change_password()
    {
        $this->setup();

        $this->form_validation->set_rules('current_password', 'Current Password', 'required');
        $this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[8]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');

        if ($this->form_validation->run() !== false) {
            $current_password = $this->input->post('current_password');
            $new_password     = $this->custom->hashPassword((string) $this->input->post('new_password'));

            $user_id = $this->userinfo[0]->u_id;

            // Check if the current password matches
            if ($this->custom->verify_password($user_id, $current_password)) {
                $this->custom->update_password($user_id, $new_password);
                $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Password updated successfully.</div>');
                ci_redirect('applicant/applied_jobs');
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Current password is incorrect.</div>');
            }
        }

        // Reload the form if validation fails
        $this->load->applicant_inner_view('changepassword', $this->data);
    }
}
