<?php

namespace App\Controllers;

/**
 * Employer (pharmacy) area.
 *
 * Ported from CI3 `application/controllers/Employer.php`.
 */
class Employer extends BaseController
{
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

        if (empty($this->isUserLoggedIn)) {
            if ($this->uri->segment(2) !== 'login' && $this->uri->segment(2) !== 'register') {
                ci_redirect('front/login');
            }
        }

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $this->data['lang'] = $this->session->userdata('site_lang');

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '1') {
                $this->data['pageTitle']     = 'Employer Dashboard';
                $this->data['myaccountLink'] = base_url('employer/all_jobs');
                $this->data['logoutLink']    = base_url('employer/logout');
            } else {
                $this->data['pageTitle']     = 'Candidate Dashboard';
                $this->data['myaccountLink'] = base_url('applicant/applied_jobs');
                $this->data['logoutLink']    = base_url('applicant/logout');
            }
        }

        $this->userinfo         = $this->custom->get_where('users', ['u_id' => $this->data['uid']]);
        $this->data['userinfo'] = $this->userinfo;

        $this->data['company_logo'] = ! empty($this->userinfo[0]->u_a_company_logo)
            ? $this->userinfo[0]->u_a_company_logo
            : 'nologo.jpg';

        $this->data['usertype'] = $this->config->item('usertype');
        $this->data['posttype'] = $this->config->item('posttype');

        $this->data['gender']        = $this->config->item('gender');
        $this->data['jobtype']       = $this->config->item('jobtype');
        $this->data['serchexp']      = $this->config->item('serchexp');
        $this->data['marital']       = $this->config->item('marital');
        $this->data['religion']      = $this->config->item('religion');
        $this->data['mr']            = $this->config->item('mr');
        $this->data['qualification'] = $this->config->item('qualification');
        $this->data['appliedstatus'] = $this->config->item('appliedstatus');
        $this->data['search_salary'] = $this->config->item('search_salary');

        $this->data['approved']             = $this->config->item('approved');
        $this->data['application_approved'] = $this->config->item('application_approved');

        $pagesection = $this->uri->segment(2);

        $this->data['dashcls'] = ($pagesection === 'dashboard') ? 'active' : '';
        $this->data['stcls']   = in_array($pagesection, ['stores', 'add_store', 'edit_store'], true) ? 'active' : '';
        $this->data['pjcls']   = ($pagesection === 'post_job') ? 'active' : '';
        $this->data['ajcls']   = ($pagesection === 'all_jobs') ? 'active' : '';
        $this->data['lgcls']   = ($pagesection === 'logout') ? 'active' : '';
        $this->data['sccls']   = ($pagesection === 'search_applicants') ? 'active' : '';
        $this->data['epcls']   = ($pagesection === 'edit_profile') ? 'active' : '';
        $this->data['iccls']   = ($pagesection === 'invited_applicants') ? 'active' : '';
        $this->data['apcls']   = ($pagesection === 'applications') ? 'active' : '';
    }

    public function index()
    {
        $this->setup();

        if ($this->isUserLoggedIn) {
            ci_redirect('employer/all_jobs');
        } else {
            ci_redirect('front/login');
        }
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

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $module                  = $this->uri->segment(1);
        $this->data['pageinfo']  = ['title' => 'Province', 'link' => $module];

        $this->session->unset_userdata('error_msg');

        // "Recent Shift Jobs" on the dashboard - soonest shift first, matching
        // the full list at employer/all_jobs rather than the record number.
        $this->joblists = $this->custom->get_where_order(
            'post_job',
            ['u_id' => $this->userinfo[0]->u_id],
            shiftDateOrderBy(),
            '',
            false
        );
        $this->data['restot'] = $this->custom->query("select count(p_id) as totjobs from post_job where u_id = '" . $this->userinfo[0]->u_id . "'");

        $this->data['joblists'] = $this->joblists;

        $this->load->owner_inner_view('dashboard', $this->data);
    }

    public function post_job()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        if ($this->input->post('savepostjob')) {
            $this->form_validation->set_rules('p_shift_for', 'Shift Requested For', 'required');
            $this->form_validation->set_rules('p_store_id', 'Store', 'required');

            $rowData = cleanArray($this->input->post());

            $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
            $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
            $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
            // Keep the sortable date column in step with the text one.
            $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);

            // The shift belongs to a chosen store (location) - one this login
            // owns - and its province/city come from that store rather than
            // from the login row (change request B4).
            $store = $this->ownStore((int) $this->input->post('p_store_id'));

            $rowData['p_store_id'] = $store ? $store->s_id : 0;
            $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $this->userinfo[0]->u_provice;
            $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $this->userinfo[0]->u_city;

            $rowData['created']   = date('Y-m-d H:i:s');
            $rowData['modified']  = date('Y-m-d H:i:s');
            $rowData['u_id']      = $this->userinfo[0]->u_id;
            $rowData['p_status']  = 0;
            unset($rowData['savepostjob'], $rowData['files']);

            if (! $store) {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of your stores.</div>');

                foreach ($rowData as $ky => $vl) {
                    $this->data[$ky] = $vl;
                }
            } elseif (insertQry('post_job', $rowData, 'newjob')) {
                $id = $this->db->insertID();

                $shiftID              = 'PAS-' . $id;
                $uData['p_job_title'] = $shiftID;

                updateQry('post_job', $uData, ['p_id' => $id]);

                ci_redirect('employer/all_jobs');
            } else {
                foreach ($rowData as $ky => $vl) {
                    $this->data[$ky] = $vl;
                }
            }
        } else {
            getTableInfo($this->dbname, 'post_job');
        }

        $this->data['shift_for']       = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');
        $this->data['province']        = $this->custom->get_where('province', ['p_status' => 1]);
        $this->data['city']            = $this->custom->get_where('city', ['c_status' => 1]);
        $this->data['hourly_rate']     = $this->custom->get_where('hourly_rate', ['hr_status' => 1]);
        $this->data['software_skills'] = $this->custom->get_where('software_skills', ['ss_status' => 1]);
        $this->data['store_service']   = $this->custom->get_where('store_service', ['st_status' => 1]);

        // The login's active stores, for the location picker on the form.
        $this->data['stores'] = $this->custom->get_where_order(
            'store',
            ['u_id' => $this->userinfo[0]->u_id, 's_status' => 1],
            's_name',
            'asc'
        );

        $this->job_owner         = $this->custom->get_where('post_job', ['u_id' => $this->userinfo[0]->u_id]);
        $this->data['job_owner'] = $this->job_owner;

        $this->load->owner_inner_view('post_job', $this->data);
    }

    public function edit_job()
    {
        $this->setup();

        $id = $this->uri->segment(3);

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $count = $this->custom->get_where_count('post_job', ['p_id' => $id, 'p_status' => 0, 'p_approved' => 0]);

        if ($count) {
            if ($this->input->post('savepostjob')) {
                $this->form_validation->set_rules('p_shift_for', 'Shift Requested For', 'required');
                $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                $rowData = cleanArray($this->input->post());

                $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
                $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
                $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
                $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);

                // Same as on create: the location comes off the chosen store.
                $store = $this->ownStore((int) $this->input->post('p_store_id'));

                $rowData['p_store_id'] = $store ? $store->s_id : 0;
                $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $this->userinfo[0]->u_provice;
                $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $this->userinfo[0]->u_city;

                $rowData['created']  = date('Y-m-d H:i:s');
                $rowData['modified'] = date('Y-m-d H:i:s');
                $rowData['u_id']     = $this->userinfo[0]->u_id;
                $rowData['p_status'] = 0;
                unset($rowData['savepostjob'], $rowData['files']);

                if (! $store) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of your stores.</div>');

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } elseif (updateQry('post_job', $rowData, ['p_id' => $id])) {
                    ci_redirect('employer/all_jobs');
                } else {
                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                }
            } else {
                getTableInfo($this->dbname, 'post_job', ['p_id' => $id, 'p_status' => 0, 'p_approved' => 0]);
            }
        } else {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid Shift Post</div>');
            ci_redirect('employer/all_jobs');
        }

        $this->data['shift_for']       = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');
        $this->data['province']        = $this->custom->get_where('province', ['p_status' => 1]);
        $this->data['city']            = $this->custom->get_where('city', ['c_status' => 1]);
        $this->data['hourly_rate']     = $this->custom->get_where('hourly_rate', ['hr_status' => 1]);
        $this->data['software_skills'] = $this->custom->get_where('software_skills', ['ss_status' => 1]);
        $this->data['store_service']   = $this->custom->get_where('store_service', ['st_status' => 1]);

        // The login's active stores, for the location picker on the form.
        $this->data['stores'] = $this->custom->get_where_order(
            'store',
            ['u_id' => $this->userinfo[0]->u_id, 's_status' => 1],
            's_name',
            'asc'
        );

        $this->job_owner         = $this->custom->get_where('post_job', ['u_id' => $this->userinfo[0]->u_id]);
        $this->data['job_owner'] = $this->job_owner;

        $this->load->owner_inner_view('edit_job', $this->data);
    }

    public function delete_job()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $id = $this->uri->segment(3);

        $count = $this->custom->get_where_count('post_job', ['p_id' => $id, 'p_status' => 0, 'p_approved' => 0]);

        if ($count) {
            $this->custom->delete_where('post_job', ['p_id' => $id, 'p_status' => 0, 'p_approved' => 0]);
            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Shift Post Deleted Successfully</div>');
        } else {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid Shift Post</div>');
        }

        ci_redirect('employer/all_jobs');
    }

    public function all_jobs()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $this->jobslist         = $this->custom->get_where_order(
            'post_job',
            ['u_id' => $this->userinfo[0]->u_id],
            shiftDateOrderBy(),
            '',
            false
        );
        $this->data['jobslist'] = $this->jobslist;

        $this->load->owner_inner_view('all_jobs', $this->data);
    }

    /**
     * The login's stores (locations) - change request B4.
     *
     * One login owns many stores; every shift is posted against one of them.
     */
    public function stores()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $this->data['stores'] = $this->custom->get_where_order(
            'store',
            ['u_id' => $this->userinfo[0]->u_id],
            's_name',
            'asc'
        );

        // A store-role login has its single location; only a manager (or an
        // employer from before the feature) adds more.
        $this->data['can_add_store'] = (($this->userinfo[0]->u_emp_role ?? 0) != 2);

        $this->load->owner_inner_view('stores', $this->data);
    }

    public function add_store()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        if (($this->userinfo[0]->u_emp_role ?? 0) == 2) {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">A store account cannot add stores. Ask your manager.</div>');
            ci_redirect('employer/stores');
        }

        if ($this->input->post('savestore')) {
            $this->form_validation->set_rules('s_name', 'Store Name', 'required');
            $this->form_validation->set_rules('s_number', 'Store Number', 'required');

            $rowData = $this->storeRowFromPost();

            if (insertQry('store', $rowData)) {
                ci_redirect('employer/stores');
            }

            foreach ($rowData as $ky => $vl) {
                $this->data[$ky] = $vl;
            }
        } else {
            getTableInfo($this->dbname, 'store');
        }

        $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);

        $this->load->owner_inner_view('add_store', $this->data);
    }

    public function edit_store()
    {
        $this->setup();

        $id = (int) $this->uri->segment(3);

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        // Only a store this login owns.
        $count = $this->custom->get_where_count('store', ['s_id' => $id, 'u_id' => $this->userinfo[0]->u_id]);

        if (! $count) {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid Store</div>');
            ci_redirect('employer/stores');
        }

        if ($this->input->post('savestore')) {
            $this->form_validation->set_rules('s_name', 'Store Name', 'required');
            $this->form_validation->set_rules('s_number', 'Store Number', 'required');

            $rowData = $this->storeRowFromPost();

            if (updateQry('store', $rowData, ['s_id' => $id, 'u_id' => $this->userinfo[0]->u_id])) {
                ci_redirect('employer/stores');
            }

            foreach ($rowData as $ky => $vl) {
                $this->data[$ky] = $vl;
            }
        } else {
            getTableInfo($this->dbname, 'store', ['s_id' => $id]);
        }

        $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);

        $this->load->owner_inner_view('edit_store', $this->data);
    }

    /**
     * The store columns as posted, owned by the logged-in employer.
     */
    private function storeRowFromPost(): array
    {
        return [
            'u_id'       => $this->userinfo[0]->u_id,
            's_name'     => strip_tags((string) $this->input->post('s_name')),
            's_number'   => strip_tags((string) $this->input->post('s_number')),
            's_province' => (int) $this->input->post('s_province'),
            's_city'     => (int) $this->input->post('s_city'),
            's_address'  => strip_tags((string) $this->input->post('s_address')),
            's_pincode'  => strip_tags((string) $this->input->post('s_pincode')),
            's_phone'    => strip_tags((string) $this->input->post('s_phone')),
            's_status'   => $this->input->post('s_status') !== null ? (int) $this->input->post('s_status') : 1,
            'modified'   => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * One of the logged-in employer's active stores, or null.
     */
    private function ownStore(int $storeId)
    {
        if ($storeId <= 0) {
            return null;
        }

        $rows = $this->custom->get_where('store', [
            's_id'     => $storeId,
            'u_id'     => $this->userinfo[0]->u_id,
            's_status' => 1,
        ]);

        return $rows[0] ?? null;
    }

    public function applications()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $this->applicationslist = $this->custom->get_where('stu_saved_applied_jobs', [
            'agency_id'      => $this->userinfo[0]->u_id,
            'sj_status'      => 1,
            'sj_is_approved' => 1,
        ]);

        $this->data['applicationslist'] = $this->applicationslist;

        $this->load->owner_inner_view('applications', $this->data);
    }

    public function applied_applicants()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $pid = $this->uri->segment(3);

        $this->data['job'] = $this->custom->get_where('post_job', ['p_id' => $pid]);

        $this->appliedlist         = $this->custom->query(
            'select * from stu_saved_applied_jobs ssa, users u '
            . "where ssa.u_id = u.u_id and ssa.p_id = ? and ssa.sj_status = '1' and ssa.sj_is_approved = '1'",
            [$pid]
        );
        $this->data['appliedlist'] = $this->appliedlist;

        // The view file is named applied_candidates.php; this action used to ask
        // for 'applied_applicants' and so returned a server error every time.
        $this->load->owner_inner_view('applied_candidates', $this->data);
    }

    public function edit_profile()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        $table = 'users';

        if ($this->input->post('updateprofile')) {
            $this->form_validation->set_rules('u_phone', 'Mobile No.', 'required');

            $rowData = cleanArray($this->input->post());

            $rowData['u_comp_name']  = $this->userinfo[0]->u_comp_name;
            $rowData['u_l_provice']  = $this->userinfo[0]->u_l_provice;
            $rowData['u_licence_no'] = $this->userinfo[0]->u_licence_no;
            $rowData['u_email']      = $this->userinfo[0]->u_email;

            $rowData['modified'] = date('Y-m-d H:i:s');
            unset($rowData['updateprofile']);

            if (updateQry($table, $rowData, ['u_id' => $this->data['uid']])) {
                /* logo upload */
                if ($this->request->getFile('u_a_company_logo') !== null && $this->request->getFile('u_a_company_logo')->isValid()) {
                    $upload_param = [
                        'filename' => 'u_a_company_logo',
                        'path'     => 'owner',
                        'types'    => 'gif|jpg|png',
                        'size'     => '2048',
                        'width'    => '1024',
                        'height'   => '768',
                        'table'    => $table,
                        'field'    => 'u_a_company_logo',
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

                if ($this->request->getFile('u_a_ra_licence_file') !== null && $this->request->getFile('u_a_ra_licence_file')->isValid()) {
                    /* ra license upload */
                    $upload_param = [
                        'filename' => 'u_a_ra_licence_file',
                        'path'     => 'owner',
                        'types'    => 'gif|jpg|png',
                        'size'     => '2048',
                        'width'    => '1024',
                        'height'   => '768',
                        'table'    => $table,
                        'field'    => 'u_a_ra_licence_file',
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

                ci_redirect('employer/edit_profile');
            } else {
                foreach ($rowData as $ky => $vl) {
                    $this->data[$ky] = $vl;
                }
            }
        } else {
            getTableInfo($this->dbname, $table, ['u_id' => $this->data['uid']]);
        }

        $this->data['province'] = $this->custom->get_data('province');

        $this->users         = $this->custom->get_where($table, ['u_usertype' => 2]);
        $this->data['users'] = $this->users;

        $this->load->owner_inner_view('edit_profile', $this->data);
    }

    public function ajax_shortlist()
    {
        $this->setup();

        $uid         = $this->input->post('uid');
        $jobid       = $this->input->post('jobid');
        $invite_date = date('Y-m-d  H:i:s');
        $sj_status   = 3;

        $invite = $this->custom->get_where('stu_saved_applied_jobs', [
            'agency_id' => $this->data['uid'],
            'sj_status' => 3,
        ]);

        if (count($invite) <= $this->data['settings'][0]->s_resume_access) {
            $inarr = [
                'u_id'        => $uid,
                'agency_id'   => $this->data['uid'],
                'p_id'        => $jobid,
                'invite_date' => $invite_date,
                'sj_status'   => $sj_status,
                'created'     => $invite_date,
                'modified'    => $invite_date,
            ];

            $insert = $this->custom->insert('stu_saved_applied_jobs', $inarr);

            echo $insert ? 1 : 0;
        } else {
            echo 2;
        }
    }

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
                ci_redirect('employer/all_jobs');
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Current password is incorrect.</div>');
            }
        }

        // Reload the form if validation fails
        $this->load->owner_inner_view('changepassword', $this->data);
    }
}
