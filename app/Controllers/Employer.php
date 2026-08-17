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
        // the full list at employer/all_jobs rather than the record number, and
        // holding what this login may manage rather than only what it posted:
        // an owner's managers post on their behalf.
        [$scope, $binds] = employerShiftScope($this->userinfo[0]);

        $this->joblists = $this->custom->query(
            'SELECT * FROM post_job WHERE ' . $scope . ' ORDER BY ' . shiftDateOrderBy(),
            $binds
        );

        $this->data['restot'] = $this->custom->query(
            'SELECT COUNT(p_id) AS totjobs FROM post_job WHERE ' . $scope,
            $binds
        );

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

            $rowData = $this->shiftRowFromPost();

            // The shift belongs to a chosen store (location) - one this login
            // owns - and its province/city come from that store rather than
            // from the login row (change request B4).
            $store = $this->ownStore((int) $this->input->post('p_store_id'));

            $rowData['p_store_id'] = $store ? $store->s_id : 0;
            $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $this->userinfo[0]->u_provice;
            $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $this->userinfo[0]->u_city;

            $rowData['created']  = date('Y-m-d H:i:s');
            $rowData['modified'] = date('Y-m-d H:i:s');
            $rowData['u_id']     = $this->userinfo[0]->u_id;
            $rowData['p_status'] = 0;

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
        // Ordered by name, unlike the two above: this master is maintained by
        // hand and its ids come out in the order they happened to be added.
        $this->data['additional_details'] = $this->custom->get_where_order('additional_details', ['ad_status' => 1], 'ad_name', 'asc');

        // The login's active stores, for the location picker on the form. A
        // manager owns none - theirs is the group's store they were assigned -
        // so this resolves both cases rather than reading ownership directly.
        $this->data['stores'] = employerStores($this->userinfo[0]);

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

        // Whose shift this is, as well as whether it may still be edited. It
        // used to ask only for the id and the two flags, so every pending shift
        // on the site opened in anybody's edit form - and saving it stamped the
        // editor's own store and ownership over the top.
        $count = $this->manageableShift($id);

        if ($count) {
            if ($this->input->post('savepostjob')) {
                $this->form_validation->set_rules('p_shift_for', 'Shift Requested For', 'required');
                $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                $rowData = $this->shiftRowFromPost();

                // Same as on create: the location comes off the chosen store.
                $store = $this->ownStore((int) $this->input->post('p_store_id'));

                $rowData['p_store_id'] = $store ? $store->s_id : 0;
                $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $this->userinfo[0]->u_provice;
                $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $this->userinfo[0]->u_city;

                // Neither `created` nor `u_id` is written here, and neither can
                // arrive from the post either - see shiftRowFromPost(). `created`
                // is when the shift was posted and an edit is not a posting; the
                // front page reads it to decide what counts as recently posted.
                // `u_id` is whose shift it is, and this form is reachable by the
                // owner and by the branch manager as well as by whoever raised
                // it, so writing the editor there would move the shift to
                // whoever opened it last.
                $rowData['modified'] = date('Y-m-d H:i:s');
                $rowData['p_status'] = 0;

                if (! $store) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of your stores.</div>');

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                    // The same two flags the gate above matched on, so a shift
                    // approved while this form was open is not written anyway.
                } elseif (updateQry('post_job', $rowData, ['p_id' => $id, 'p_status' => 0, 'p_approved' => 0])) {
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
        // Ordered by name, unlike the two above: this master is maintained by
        // hand and its ids come out in the order they happened to be added.
        $this->data['additional_details'] = $this->custom->get_where_order('additional_details', ['ad_status' => 1], 'ad_name', 'asc');

        // The login's active stores, for the location picker on the form. A
        // manager owns none - theirs is the group's store they were assigned -
        // so this resolves both cases rather than reading ownership directly.
        $this->data['stores'] = employerStores($this->userinfo[0]);

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

        // Whose shift this is, as above: the id and the flags alone let anybody
        // delete anybody's pending shift.
        $count = $this->manageableShift($id);

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

        // Everything this login may manage, not only what it posted: an owner
        // sees their managers' shifts here, and a manager sees whatever stands
        // against the branch they run. The Edit and Delete buttons on each row
        // lead to the same rule, so nothing is listed that cannot be opened.
        [$scope, $binds] = employerShiftScope($this->userinfo[0]);

        $this->jobslist = $this->custom->query(
            'SELECT * FROM post_job WHERE ' . $scope . ' ORDER BY ' . shiftDateOrderBy(),
            $binds
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

        // Deactivated ones included, so they can be seen and turned back on.
        // A manager gets the one branch they run, not the rest of their
        // corporate group's: they manage a store, not the chain.
        $this->data['stores'] = employerStores($this->userinfo[0], false);

        // A store-role login has its single location; only a manager (or an
        // employer from before the feature) adds more.
        $this->data['can_add_store'] = (($this->userinfo[0]->u_emp_role ?? 0) != 2);

        // A manager is shown their corporate group's store, which they do not
        // own: edit_store() would refuse it, so the row is listed without the
        // button rather than with one that leads nowhere.
        $this->data['store_owner_id'] = (int) $this->userinfo[0]->u_id;

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
        $this->loadShiftDefaultLists();

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
        $this->loadShiftDefaultLists();

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
            // Where the place actually is, for an applicant who has the street
            // address and still cannot find the door.
            's_location_label' => strip_tags((string) $this->input->post('s_location_label')),
            // Normalised and scheme-checked rather than stored as typed: these
            // end up in an href on the shift page and in booking e-mail.
            's_map_url'  => safeUrl($this->input->post('s_map_url')),
            's_pincode'  => strip_tags((string) $this->input->post('s_pincode')),
            // Digits only, PHONE_LENGTH of them - the form caps it, this is what
            // makes the cap true of what gets stored.
            's_phone'    => normalisePhone($this->input->post('s_phone')),
            's_website'  => safeUrl($this->input->post('s_website')),
            // What a shift at this store starts with, the same three columns the
            // back office's store form writes. Set unconditionally: an all-clear
            // group posts nothing at all, and clearing the last box has to clear
            // the column rather than leave the old list standing.
            's_skills'             => implode(',', array_map('intval', (array) $this->input->post('s_skills'))),
            's_services'           => implode(',', array_map('intval', (array) $this->input->post('s_services'))),
            's_additional_details' => implode(',', array_map('intval', (array) $this->input->post('s_additional_details'))),
            's_status'   => $this->input->post('s_status') !== null ? (int) $this->input->post('s_status') : 1,
            'modified'   => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * The three masters the store form's shift-default boxes are drawn from.
     *
     * The same three lists the shift form loads, so a store cannot offer a
     * choice a shift at that store could not then hold.
     */
    private function loadShiftDefaultLists(): void
    {
        $this->data['software_skills'] = $this->custom->get_where('software_skills', ['ss_status' => 1]);
        $this->data['store_service']   = $this->custom->get_where('store_service', ['st_status' => 1]);
        // Ordered by name, unlike the two above: this master is maintained by
        // hand and its ids come out in the order they happened to be added.
        $this->data['additional_details'] = $this->custom->get_where_order('additional_details', ['ad_status' => 1], 'ad_name', 'asc');
    }

    /**
     * A store's shift defaults, for the employer's own shift form.
     *
     * The back office has the same endpoint. This one is not it: it answers
     * only for a store this login may actually post against, so asking for
     * somebody else's store id returns the empty answer rather than telling a
     * stranger what that branch offers.
     */
    public function ajax_getstoredefaults()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            return $this->response->setJSON(['s_id' => 0, 'p_skills' => [], 'p_services' => [], 'p_additional_details' => []]);
        }

        $storeId = (int) $this->input->post('s_id');
        $store   = null;

        // Their own stores, resolved the same way the picker on the form is -
        // so a manager gets their group's branch and an owner gets theirs.
        foreach (employerStores($this->userinfo[0]) as $candidate) {
            if ((int) $candidate->s_id === $storeId) {
                $store = $candidate;
                break;
            }
        }

        $ids = static function ($list): array {
            // The columns hold "3,7,12"; '' has to come back as no ids at all
            // rather than as one empty one.
            return array_values(array_filter(array_map('intval', explode(',', (string) $list))));
        };

        return $this->response->setJSON([
            // 0 when the id matched no store this login may use.
            's_id'                 => $store ? (int) $store->s_id : 0,
            'p_skills'             => $store ? $ids($store->s_skills ?? '') : [],
            'p_services'           => $store ? $ids($store->s_services ?? '') : [],
            'p_additional_details' => $store ? $ids($store->s_additional_details ?? '') : [],
        ]);
    }

    /**
     * The shift columns the employer's own form owns, read off the post.
     *
     * A whitelist, not `cleanArray($this->input->post())`. That returned every
     * key the browser sent, so a hand-made request could set any column on
     * `post_job`: `p_approved`, which is an employer approving their own shift;
     * `u_id`, which is handing it to somebody else; `created`, which is lying
     * about when it was raised. Create and edit both used to overwrite the last
     * two afterwards, which hid the hole rather than closing it - and the moment
     * edit stopped overwriting them, to stop an owner's edit stealing their
     * manager's shift, the post was writing them again.
     *
     * Only what the form asks for is here. The location columns are not: they
     * come off the chosen store. Neither is `p_job_title`, which the system
     * names `PAS-<id>`, nor any flag that decides a shift's status.
     */
    private function shiftRowFromPost(): array
    {
        $row = [];

        foreach (['p_shift_for', 'p_hourly_rate', 'p_dates', 'p_shift_time'] as $field) {
            $row[$field] = strip_tags((string) $this->input->post($field));
        }

        // Master ids, stored as a comma separated list - cast, because that is
        // all they ever are.
        foreach (['p_skills', 'p_services', 'p_additional_details'] as $group) {
            $row[$group] = implode(',', array_map('intval', (array) $this->input->post($group)));
        }

        // The one field that keeps its markup: it is a rich-text box, and the
        // back office stores it the same way.
        $row['p_jobinfo'] = $this->input->post('p_jobinfo');

        // Keep the sortable date column in step with the text one.
        $row['p_date_start'] = parseShiftDate($row['p_dates']);

        return $row;
    }

    /**
     * Whether this shift is one the logged-in employer may act on.
     *
     * Ownership is `employerShiftScope()`: their own shifts, their managers'
     * if they are an owner, and the branch they run if they are a manager.
     *
     * @param bool $pendingOnly also require the shift to be still awaiting
     *                          approval, which editing and deleting do and
     *                          looking at the candidate list does not. Checking
     *                          `p_approved` as well as `p_status` stops the
     *                          nightly expiry job, which sets `p_status` to 0,
     *                          from re-opening shifts that have already run.
     */
    private function shiftInScope($id, bool $pendingOnly): bool
    {
        $id = (int) $id;

        if ($id <= 0) {
            return false;
        }

        [$scope, $binds] = employerShiftScope($this->userinfo[0]);

        $rows = $this->custom->query(
            'SELECT COUNT(p_id) AS n FROM post_job WHERE p_id = ? AND ' . $scope
                . ($pendingOnly ? ' AND p_status = 0 AND p_approved = 0' : ''),
            array_merge([$id], $binds)
        );

        return (int) ($rows[0]->n ?? 0) > 0;
    }

    /** Whether this shift is theirs to edit or delete. */
    private function manageableShift($id): bool
    {
        return $this->shiftInScope($id, true);
    }

    /**
     * One of the stores this login may post against, or null.
     *
     * Matched against the same list the form offered rather than against
     * ownership, so a manager's assigned store counts and the rule that decides
     * it lives in one place.
     */
    private function ownStore(int $storeId)
    {
        if ($storeId <= 0) {
            return null;
        }

        foreach (employerStores($this->userinfo[0]) as $store) {
            if ((int) $store->s_id === $storeId) {
                return $store;
            }
        }

        return null;
    }

    public function applications()
    {
        $this->setup();

        if ($this->session->userdata('userType') != '1') {
            ci_redirect('front/login');
        }

        // By the shift rather than by `agency_id`, so this holds the bookings
        // for every shift this login may manage. Scoping it to their own u_id
        // left an owner able to open their manager's shift and its candidate
        // list but not the booking made on it.
        [$scope, $binds] = employerShiftScope($this->userinfo[0], 'pj');

        $this->applicationslist = $this->custom->query(
            'SELECT ssa.* FROM stu_saved_applied_jobs ssa'
            . ' JOIN post_job pj ON pj.p_id = ssa.p_id'
            . ' WHERE ssa.sj_status = 1 AND ssa.sj_is_approved = 1 AND ' . $scope,
            $binds
        );

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

        // Who applied for a shift is as much the employer's business as the
        // shift itself, and no more anybody else's: this used to answer for any
        // id at all. Not restricted to pending shifts - the point of the screen
        // is the candidates on a shift that has been approved.
        if (! $this->shiftInScope($pid, false)) {
            // The message does not reach All Shifts - flashdata is lost across
            // every redirect on this side of the site, which is a separate
            // defect - but the refusal itself is what matters here.
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid Shift Post</div>');
            ci_redirect('employer/all_jobs');
        }

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
            $this->form_validation->set_rules('u_phone', 'Mobile No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);

            // A whitelist, for the reason spelled out on shiftRowFromPost().
            // This wrote every key the browser sent, straight onto the `users`
            // row - so an employer could post `u_emp_role` and `u_store_id` and
            // make themselves the manager of anybody's branch, which is exactly
            // what employerShiftScope() then hands them the shifts of. The four
            // fields the form shows but does not let anyone change (the company
            // name, licence and e-mail) are re-read off the row as before.
            $rowData = [];

            foreach (['u_fname', 'u_lname', 'u_phone', 'u_pincode'] as $field) {
                $rowData[$field] = strip_tags((string) $this->input->post($field));
            }

            // Digits only, PHONE_LENGTH of them - see normalisePhone().
            $rowData['u_phone'] = normalisePhone($rowData['u_phone']);

            foreach (['u_provice', 'u_city'] as $field) {
                $rowData[$field] = (int) $this->input->post($field);
            }

            $rowData['u_comp_name']  = $this->userinfo[0]->u_comp_name;
            $rowData['u_l_provice']  = $this->userinfo[0]->u_l_provice;
            $rowData['u_licence_no'] = $this->userinfo[0]->u_licence_no;
            $rowData['u_email']      = $this->userinfo[0]->u_email;

            $rowData['modified'] = date('Y-m-d H:i:s');

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

        $uid         = (int) $this->input->post('uid');
        $jobid       = (int) $this->input->post('jobid');
        $invite_date = date('Y-m-d  H:i:s');
        $sj_status   = 3;

        // Both ids arrive from the browser, and this used to trust them: any
        // employer could invite anybody to any shift, including one that is not
        // theirs. The shift has to be one they may act on - not only pending,
        // since inviting is worth doing on an approved shift too - and the
        // person has to be a real, active applicant.
        $applicant = $this->custom->get_where_count('users', [
            'u_id'       => $uid,
            'u_usertype' => 2,
            'u_status'   => 1,
        ]);

        if (! $this->shiftInScope($jobid, false) || ! $applicant) {
            echo 0;

            return;
        }

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
