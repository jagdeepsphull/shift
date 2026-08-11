<?php

namespace App\Controllers;

/**
 * Super-admin back office.
 *
 * Ported from CI3 `application/controllers/Sadmin.php`. Each CRUD screen keeps
 * the original shape: the third URI segment selects the action
 * (`add` / `edit` / `changestatus` / `delete`) and the fourth is the row id.
 */
class Sadmin extends BaseController
{
    /**
     * Shared set-up + access control that CI3 performed in the constructor.
     */
    protected function setup(): void
    {
        $this->data['settings']  = $this->custom->getSettings();
        $this->data['adminpath'] = $this->data['settings'][0]->s_adminpath;

        // User login status
        $this->isAdminUserLoggedIn = $this->session->userdata('isAdminUserLoggedIn');

        $this->data['link'] = $this->uri->segment(2);

        if (empty($this->isAdminUserLoggedIn)) {
            if ($this->uri->segment(2) !== 'login') {
                ci_redirect('sadmin/login');
            }
        } else {
            $this->data['userdet'] = $this->custom->get_where('users', ['u_id' => $this->session->userdata('adminUserId')]);

            $menu = $this->custom->query('select * from menu where m_status=1 order by m_order asc ');

            $menuarr = [];

            if ($menu) {
                foreach ($menu as $mn) {
                    $menuarr[$mn->m_id]['mname'] = $mn->m_name;
                    $menuarr[$mn->m_id]['micon'] = $mn->m_icon;
                    $menuarr[$mn->m_id]['mlink'] = $mn->m_link;
                }
            }

            $this->data['menuarr'] = $menuarr;

            // The sidebar splits Manage Employers by the kind chosen at
            // registration and badges each entry with how many of that kind are
            // still waiting to be activated.
            $this->data['employerKinds'] = $this->config->item('employerKinds');
            $this->data['pendingUsers']  = $this->pendingUserCounts();
        }

        $this->data['usersubtype']          = $this->config->item('usersubtype');
        $this->data['posttype']             = $this->config->item('posttype');
        $this->data['approved']             = $this->config->item('approved');
        $this->data['approvedSelectable']   = $this->config->item('approvedSelectable');
        $this->data['application_approved'] = $this->config->item('application_approved');

        $this->data['gender']        = $this->config->item('gender');
        $this->data['marital']       = $this->config->item('marital');
        $this->data['religion']      = $this->config->item('religion');
        $this->data['categorytype']  = $this->config->item('categorytype');
        $this->data['featured']      = $this->config->item('featured');
        $this->data['status']        = $this->config->item('status');
        $this->data['qualification'] = $this->config->item('qualification');
    }

    /**
     * How many accounts of each kind are still deactivated, for the sidebar
     * badges. One grouped query rather than one per entry, because `setup()`
     * runs on every back-office page.
     *
     * @return array<string, int> keyed by `employerKinds` slug, plus the
     *                            'applicant' and 'employer' totals
     */
    private function pendingUserCounts(): array
    {
        $counts = ['applicant' => 0, 'employer' => 0];

        foreach (array_keys((array) $this->config->item('employerKinds')) as $slug) {
            $counts[$slug] = 0;
        }

        $rows = $this->custom->query(
            'SELECT u_usertype, u_emp_role, (u_parent_id > 0) AS has_parent, COUNT(*) AS total
               FROM users
              WHERE u_status = 0 AND u_usertype IN (1, 2)
           GROUP BY u_usertype, u_emp_role, has_parent'
        );

        foreach ($rows ?: [] as $row) {
            $total = (int) $row->total;

            if ((int) $row->u_usertype === 2) {
                $counts['applicant'] += $total;

                continue;
            }

            $counts['employer'] += $total;

            // `has_parent` is 0/1, which is all employerKindSlug() looks at.
            $slug = employerKindSlug([
                'u_emp_role'  => $row->u_emp_role,
                'u_parent_id' => $row->has_parent,
            ]);

            if ($slug !== '' && isset($counts[$slug])) {
                $counts[$slug] += $total;
            }
        }

        return $counts;
    }

    public function index()
    {
        $this->setup();

        if ($this->isAdminUserLoggedIn) {
            ci_redirect('sadmin/dashboard');
        } else {
            ci_redirect('sadmin/login');
        }
    }

    public function login()
    {
        $this->setup();

        // If login request submitted
        if ($this->input->post('loginSubmit')) {
            $user_captcha   = $this->input->post('captcha');
            $stored_captcha = $this->session->userdata('captcha_code');

            $this->form_validation->set_rules('username', 'Email', 'required');
            $this->form_validation->set_rules('password', 'password', 'required');

            if ($this->form_validation->run() === true) {
                if ($user_captcha != $stored_captcha) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid CAPTCHA. Please try again.</div>');
                } else {
                    // Look the administrator up, then check the password. It was
                    // previously matched inside the query as an MD5 digest.
                    $checkLogin = $this->custom->findUserForLogin(
                        (string) $this->input->post('username'),
                        ['u_status' => 1, 'u_usertype' => 0]
                    );

                    if ($checkLogin && $this->custom->passwordMatches((string) $this->input->post('password'), $checkLogin)) {
                        $this->session->set_userdata('isAdminUserLoggedIn', true);
                        $this->session->set_userdata('adminUserId', $checkLogin['u_id']);

                        ci_redirect('sadmin/dashboard');
                    } else {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Wrong Username or password, please try again.</div>');
                    }
                }
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">Please fill all the mandatory fields.</div>');
            }
        }

        // Load view
        $this->load->admin_view('login', $this->data, 1);
    }

    public function logout()
    {
        $this->session->unset_userdata('isAdminUserLoggedIn');
        $this->session->unset_userdata('adminUserId');
        $this->session->sess_destroy();

        ci_redirect('sadmin/login');
    }

    /**
     * Existing e-mail check during validation (CI3 `callback_email_check`).
     */
    public function email_check($str)
    {
        $con = [
            'returnType' => 'count',
            'conditions' => [
                'email' => $str,
            ],
        ];

        $checkEmail = $this->custom->getRows('users', $con);

        if ($checkEmail > 0) {
            $this->form_validation->set_message('email_check', 'The given email already exists.');

            return false;
        }

        return true;
    }

    public function dashboard()
    {
        $this->setup();

        $con_applicant = [
            'returnType' => 'count',
            'conditions' => ['u_usertype' => 2],
        ];
        $this->data['applicant_users'] = $this->custom->getRows('users', $con_applicant);

        $con_employer = [
            'returnType' => 'count',
            'conditions' => ['u_usertype' => 1],
        ];
        $this->data['employer_users'] = $this->custom->getRows('users', $con_employer);

        $this->data['active_jobs'] = $this->custom->query('SELECT pj.* FROM post_job pj LEFT JOIN stu_saved_applied_jobs ssaj ON pj.p_id = ssaj.p_id WHERE ssaj.sj_is_approved !=1 AND pj.p_status = 1 AND pj.p_approved = 1; ');

        $this->data['new_jobs'] = $this->custom->get_where('post_job', ['p_approved' => 0]);

        $this->data['jobs'] = $this->custom->get_where('post_job', []);

        $this->data['applicationslist'] = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates from stu_saved_applied_jobs ssa, users u, post_job pj where ssa.agency_id=u.u_id and  ssa.p_id = pj.p_id ');

        $this->data['booked_applications'] = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates from stu_saved_applied_jobs ssa, users u, post_job pj where ssa.agency_id=u.u_id and  ssa.p_id = pj.p_id and ssa.sj_is_approved = 1 ');

        // "What's new" panel. The window is a plain number of days rather than
        // "since you last looked": a per-admin last-seen marker needs a column,
        // and a fixed window is at least the same for everyone reading it.
        $days  = (int) ($this->input->get('new_days') ?: 7);
        $days  = max(1, min($days, 90));
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->data['new_days']  = $days;
        $this->data['new_since'] = $since;

        $this->data['new_applications'] = $this->custom->query(
            'SELECT ssa.sj_id, ssa.created, ssa.sj_is_approved, pj.p_job_title, pj.p_dates,
                    ap.u_fname, ap.u_lname, em.u_comp_name
               FROM stu_saved_applied_jobs ssa
               JOIN post_job pj ON pj.p_id = ssa.p_id
               JOIN users ap    ON ap.u_id = ssa.u_id
          LEFT JOIN users em    ON em.u_id = ssa.agency_id
              WHERE ssa.created >= ?
           ORDER BY ssa.created DESC
              LIMIT 25',
            [$since]
        );

        $this->data['new_employers'] = $this->custom->query(
            'SELECT u_id, u_comp_name, u_fname, u_lname, u_email, u_status, created
               FROM users WHERE u_usertype = 1 AND created >= ?
           ORDER BY created DESC LIMIT 25',
            [$since]
        );

        $this->data['new_applicants'] = $this->custom->query(
            'SELECT u_id, u_fname, u_lname, u_email, u_status, u_usersubtype, created
               FROM users WHERE u_usertype = 2 AND created >= ?
           ORDER BY created DESC LIMIT 25',
            [$since]
        );

        $this->data['new_shifts'] = $this->custom->query(
            'SELECT pj.p_id, pj.p_job_title, pj.p_dates, pj.p_approved, pj.created, u.u_comp_name
               FROM post_job pj
          LEFT JOIN users u ON u.u_id = pj.u_id
              WHERE pj.created >= ?
           ORDER BY pj.created DESC LIMIT 25',
            [$since]
        );

        $this->load->admin_view('dashboard', $this->data);
    }

    public function resources()
    {
        $this->setup();

        $module      = $this->uri->segment(2);
        $action      = $this->uri->segment(3);
        $id          = $this->uri->segment(4);
        $table       = 'headermenu';
        $idnotFound  = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Resources Links', 'link' => $module];

        $this->data['headermenu']        = $this->custom->query('select m.*,mp.m_name as mp_name from ' . $table . ' m left join ' . $table . " mp on m.m_parentid=mp.m_id ");
        $this->data['headermenu_select'] = $this->custom->query('select * from ' . $table . " where m_parentid = 0  AND (m_link IS NULL OR m_link = '') order by m_name asc; ");

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('m_name', 'Header menu name', 'required|is_unique[headermenu.m_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());

                    $cparent     = $this->input->post('m_parentid');
                    $cparentData = $this->custom->get_where($table, ['m_id' => $cparent]);

                    $rowData['m_level'] = ($cparentData[0]->m_level ?? 0) + 1;
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['m_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('m_name') !== $original_row[0]->m_name)
                                ? '|is_unique[headermenu.m_name]'
                                : '';

                            $this->form_validation->set_rules('m_name', 'Header menu name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['m_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['m_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['m_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 'm_status', 'm_id', $id);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['m_id' => $id]);

                    if ($original_row) {
                        $this->custom->delete_where($table, ['m_id' => $id]);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                }
                break;
        }
    }

    public function city()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'city';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'city', 'link' => $module];
        $this->data['city']              = $this->custom->get_data($table);

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('c_name', 'city name', 'required|is_unique[city.c_name]');
                    $this->form_validation->set_rules('c_province', 'Province name', 'required');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->data['province'] = $this->custom->get_data_order('province', 'p_name');

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['c_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('c_name') !== $original_row[0]->c_name)
                                ? '|is_unique[city.c_name]'
                                : '';

                            $this->form_validation->set_rules('c_name', 'city name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['c_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['c_id' => $id]);
                        }

                        $this->data['province'] = $this->custom->get_data('province');

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['c_id' => $id]);

                    if ($original_row) {
                        $active_status = $this->custom->get_where_count($table, ['c_id' => $id, 'c_status' => 1]);

                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_city'],
                            ['table' => 'users', 'column' => 'u_city'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->toggleStatus($table, 'c_status', 'c_id', $id);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                            ci_redirect('sadmin/' . $module);
                        }
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['c_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_city'],
                            ['table' => 'users', 'column' => 'u_city'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        if ($is_dependent) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot delete this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->delete_where($table, ['c_id' => $id]);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        }

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function province()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'province';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Province', 'link' => $module];
        $this->data['province']          = $this->custom->get_data($table);

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('p_name', 'Province name', 'required|is_unique[province.p_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['p_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('p_name') !== $original_row[0]->p_name)
                                ? '|is_unique[province.p_name]'
                                : '';

                            $this->form_validation->set_rules('p_name', 'Province name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['p_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['p_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['p_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_province'],
                            ['table' => 'users', 'column' => 'u_provice'],
                            ['table' => 'city', 'column' => 'c_provice'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        $active_status = $this->custom->get_where_count($table, ['p_id' => $id, 'p_status' => 1]);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->toggleStatus($table, 'p_status', 'p_id', $id);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                            ci_redirect('sadmin/' . $module);
                        }
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['p_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_province'],
                            ['table' => 'users', 'column' => 'u_provice'],
                            ['table' => 'city', 'column' => 'c_provice'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        if ($is_dependent) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot delete this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->delete_where($table, ['p_id' => $id]);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        }

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function hourly()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'hourly_rate';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Hourly Rate', 'link' => $module];
        $this->data['hourly']            = $this->custom->get_data($table);

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('hr_name', 'hourly name', 'required|is_unique[hourly_rate.hr_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['hr_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('hr_name') !== $original_row[0]->hr_name)
                                ? '|is_unique[hourly_rate.hr_name]'
                                : '';

                            $this->form_validation->set_rules('hr_name', 'hourly name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['hr_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['hr_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['hr_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 'hr_status', 'hr_id', $id);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['hr_id' => $id]);

                    if ($original_row) {
                        $this->custom->delete_where($table, ['hr_id' => $id]);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function shift_for()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'shift_for';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Shift For', 'link' => $module];
        $this->data['shift_for']         = $this->custom->get_data_order($table, 'sf_name', 'asc');

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('sf_name', 'Shift For name', 'required|is_unique[shift_for.sf_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['sf_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('sf_name') !== $original_row[0]->sf_name)
                                ? '|is_unique[shift_for.sf_name]'
                                : '';

                            $this->form_validation->set_rules('sf_name', 'Shift For name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['sf_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['sf_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['sf_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_shift_for'],
                            ['table' => 'users', 'column' => 'u_usersubtype'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        $active_status = $this->custom->get_where_count($table, ['sf_id' => $id, 'sf_status' => 1]);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->toggleStatus($table, 'sf_status', 'sf_id', $id);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                            ci_redirect('sadmin/' . $module);
                        }
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['sf_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_shift_for'],
                            ['table' => 'users', 'column' => 'u_usersubtype'],
                        ];

                        if ($this->custom->check_dependencies($id, $dependent_tables)) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot delete this record. Already used in Jobs.</div>');
                        } else {
                            $this->custom->delete_where($table, ['sf_id' => $id]);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        }

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function storeservice()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'store_service';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Service', 'link' => $module];
        $this->data['shift_for']         = $this->custom->get_data_order($table, 'st_service_name', 'asc');

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('st_service_name', 'Service name', 'required|is_unique[store_service.st_service_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['st_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('st_service_name') !== $original_row[0]->st_service_name)
                                ? '|is_unique[store_service.st_service_name]'
                                : '';

                            $this->form_validation->set_rules('st_service_name', 'Service name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['st_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['st_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['st_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_services'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        $active_status = $this->custom->get_where_count($table, ['st_id' => $id, 'st_status' => 1]);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->toggleStatus($table, 'st_status', 'st_id', $id);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                            ci_redirect('sadmin/' . $module);
                        }
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['st_id' => $id]);

                    if ($original_row) {
                        $exist = $this->custom->query('select * from post_job where FIND_IN_SET(' . (int) $id . ', p_services)');

                        if ($exist) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot delete this record. Already used in Jobs.</div>');
                        } else {
                            $this->custom->delete_where($table, ['st_id' => $id]);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        }

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function softwareskills()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'software_skills';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Software', 'link' => $module];
        $this->data['shift_for']         = $this->custom->get_data_order($table, 'ss_name', 'asc');

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('ss_name', 'Software name', 'required|is_unique[software_skills.ss_name]');
                    $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                    $rowData = cleanArray($this->input->post());
                    unset($rowData['savedata']);

                    if (insertQry_N($table, $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($id) {
                    $original_row     = $this->custom->get_where($table, ['ss_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('ss_name') !== $original_row[0]->ss_name)
                                ? '|is_unique[software_skills.ss_name]'
                                : '';

                            $this->form_validation->set_rules('ss_name', 'Software name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['ss_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['ss_id' => $id]);
                        }

                        $this->load->admin_view($module . '/edit', $this->data);
                    } else {
                        $idnotFound = 1;
                    }
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'changestatus':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['ss_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_skills'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        $active_status = $this->custom->get_where_count($table, ['ss_id' => $id, 'ss_status' => 1]);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->toggleStatus($table, 'ss_status', 'ss_id', $id);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                            ci_redirect('sadmin/' . $module);
                        }
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;

            case 'delete':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['ss_id' => $id]);

                    if ($original_row) {
                        $dependent_tables = [
                            ['table' => 'post_job', 'column' => 'p_skills'],
                        ];

                        $is_dependent = $this->custom->check_dependencies($id, $dependent_tables);

                        $active_status = $this->custom->get_where_count($table, ['ss_id' => $id, 'ss_status' => 1]);

                        if ($is_dependent && $active_status > 0) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">You cannot inactivate this record. Already used in other modules.</div>');
                        } else {
                            $this->custom->delete_where($table, ['ss_id' => $id]);
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been deleted.</div>');
                        }

                        ci_redirect('sadmin/' . $module);
                    } else {
                        $idnotFound = 1;
                    }

                    $this->load->admin_view($module . '/index', $this->data);
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module);
                }
                break;
        }
    }

    public function employer()
    {
        $this->setup();

        $module = $this->uri->segment(2);
        $action = $this->uri->segment(3);
        $id     = $this->uri->segment(4);
        $table  = 'users';

        $kinds = (array) $this->config->item('employerKinds');

        // The sidebar links each employer kind as /sadmin/employer/<slug>;
        // anything else in that segment is an action, the way it always was.
        $kind = isset($kinds[(string) $action]) ? (string) $action : '';

        // add/edit/delete/changestatus carry the list they were reached from as
        // ?kind=, so saving or activating comes back to that list rather than
        // dropping the admin into All Employers.
        if ($kind === '') {
            $requested = (string) $this->input->get('kind');
            $kind      = isset($kinds[$requested]) ? $requested : '';
        }

        $backTo = 'sadmin/' . $module . ($kind !== '' ? '/' . $kind : '');

        $this->data['pageinfo'] = [
            'title'     => 'Employer',
            'listtitle' => $kind !== '' ? $kinds[$kind]['label'] : 'All Employers',
            'link'      => $this->data['link'],
        ];

        $this->data['kind']   = $kind;
        $this->data['backTo'] = $backTo;

        switch ($action) {
            default:
                // Pre-B4 rows carry role 0 and so match no kind - they show up
                // under All Employers, which is why that entry is kept.
                $where = ['u_usertype' => 1];

                if ($kind !== '') {
                    $where = array_merge($where, $kinds[$kind]['filter']);
                }

                $this->data['users'] = $this->custom->get_where_order('users', $where, 'u_comp_name', 'asc');

                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_comp_name', 'Employer Name', 'required');
                    $this->form_validation->set_rules('u_email', 'Email', 'required');
                    $this->form_validation->set_rules('u_phone', 'Company Conatct No.', 'required');

                    $rowData = cleanArray($this->input->post());

                    $rowData['u_pass']     = $this->custom->hashPassword((string) $this->input->post('u_password'));
                    $rowData['created']    = date('Y-m-d H:i:s');
                    $rowData['modified']   = date('Y-m-d H:i:s');
                    $rowData['u_usertype'] = 1;
                    unset($rowData['savedata'], $rowData['u_password']);

                    if (insertQry('users', $rowData)) {
                        ci_redirect($backTo);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, 'users');
                }

                $this->data['province'] = $this->custom->get_data('province');

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                $employer_status = $this->custom->get_where_row('users', ['u_id' => $id]);

                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_comp_name', 'Employer Name', 'required');
                    $this->form_validation->set_rules('u_email', 'Email', 'required');
                    $this->form_validation->set_rules('u_phone', 'Company Conatct No.', 'required');

                    $rowData = cleanArray($this->input->post());

                    $rowData['modified'] = date('Y-m-d H:i:s');
                    unset($rowData['savedata']);

                    if (updateQry($table, $rowData, ['u_id' => $id])) {
                        // Tell the employer as soon as their account goes live.
                        if ($employer_status['u_status'] == 0 && $rowData['u_status'] == 1) {
                            $this->sendAccountApprovedEmail($employer_status);
                        }

                        ci_redirect($backTo, 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table, ['u_id' => $id]);
                }

                $this->data['province'] = $this->custom->get_data('province');

                $this->load->admin_view($module . '/edit', $this->data);
                break;

            case 'delete':
                $this->custom->delete_where($table, ['u_id' => $id]);

                ci_redirect($backTo, 'refresh');
                break;

            case 'changestatus':
                if ($id) {
                    $this->toggleUserStatus((int) $id);
                }

                ci_redirect($backTo, 'refresh');
                break;
        }
    }

    /**
     * Flip a user between active and deactivated from a listing, and send the
     * approval e-mail when that turns the account on - the activate button has
     * to behave exactly like setting the status on the edit form.
     */
    private function toggleUserStatus(int $id): void
    {
        $user = $this->custom->get_where_row('users', ['u_id' => $id]);

        if (! $user) {
            return;
        }

        $this->custom->toggleStatus('users', 'u_status', 'u_id', $id);

        if ((int) $user['u_status'] === 0) {
            $this->sendAccountApprovedEmail($user);

            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Account activated. The user has been e-mailed.</div>');
        } else {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Account deactivated.</div>');
        }
    }

    /**
     * Tell a user their account is live. Used by both the edit form and the
     * activate button, on employers and applicants alike.
     *
     * @param array $user the `users` row as it stood before activation
     */
    private function sendAccountApprovedEmail(array $user): void
    {
        $subject = 'Your Account Has Been Approved – Welcome to ' . $this->data['settings'][0]->s_sitename . '!';
        $message = email_body('account-approved', [
            'title'    => 'Your account has been approved',
            'name'     => trim($user['u_fname'] . ' ' . $user['u_lname']),
            'settings' => $this->data['settings'],
        ]);

        if (send_email($user['u_email'], $subject, $message)) {
            log_message('info', 'Email sent successfully!');
        } else {
            log_message('error', 'Failed to send email.');
        }
    }

    public function applicant()
    {
        $this->setup();

        $module = $this->uri->segment(2);
        $action = $this->uri->segment(3);
        $id     = $this->uri->segment(4);
        $table  = 'users';

        $this->data['pageinfo'] = ['title' => 'Applicant', 'link' => $this->data['link']];

        switch ($action) {
            default:
                $this->data['users'] = $this->custom->get_where_order('users', ['u_usertype' => 2], 'u_lname, u_fname', 'asc');

                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_email', 'Email', 'required');
                    $this->form_validation->set_rules('u_phone', 'Mobile No.', 'required');

                    $rowData = cleanArray($this->input->post());

                    $rowData['u_pass']     = $this->custom->hashPassword((string) $this->input->post('u_password'));
                    $rowData['created']    = date('Y-m-d H:i:s');
                    $rowData['modified']   = date('Y-m-d H:i:s');
                    $rowData['u_usertype'] = 2;
                    unset($rowData['savedata'], $rowData['u_password']);

                    if (insertQry('users', $rowData)) {
                        ci_redirect('sadmin/' . $module);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, 'users');
                }

                $this->data['shift_for'] = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');
                $this->data['province']  = $this->custom->get_data('province');

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_email', 'Email', 'required');
                    $this->form_validation->set_rules('u_phone', 'Mobile No.', 'required');

                    $rowData = cleanArray($this->input->post());

                    $rowData['modified'] = date('Y-m-d H:i:s');
                    unset($rowData['savedata']);

                    $applicant_status = $this->custom->get_where_row('users', ['u_id' => $id]);

                    if (updateQry($table, $rowData, ['u_id' => $id])) {
                        if ($applicant_status['u_status'] == 0 && $rowData['u_status'] == 1) {
                            $this->sendAccountApprovedEmail($applicant_status);
                        }

                        ci_redirect('sadmin/' . $module, 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table, ['u_id' => $id]);
                }

                $this->data['province']  = $this->custom->get_data('province');
                $this->data['shift_for'] = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');

                $this->load->admin_view($module . '/edit', $this->data);
                break;

            case 'delete':
                $this->custom->delete_where($table, ['u_id' => $id]);

                ci_redirect('sadmin/' . $module, 'refresh');
                break;

            case 'changestatus':
                if ($id) {
                    $this->toggleUserStatus((int) $id);
                }

                ci_redirect('sadmin/' . $module, 'refresh');
                break;
        }
    }

    public function postjobs()
    {
        $this->setup();

        $action = $this->uri->segment(3);
        $id     = $this->uri->segment(4);
        $table  = 'post_job';

        $this->data['pageinfo']        = ['title' => 'Shifts', 'link' => $this->data['link']];
        $this->data['jobs']            = $this->custom->get_data('post_job');
        $this->data['shift_for']       = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');
        $this->data['province']        = $this->custom->get_where('province', ['p_status' => 1]);
        $this->data['city']            = $this->custom->get_where('city', ['c_status' => 1]);
        $this->data['hourly_rate']     = $this->custom->get_where('hourly_rate', ['hr_status' => 1]);
        $this->data['software_skills'] = $this->custom->get_where('software_skills', ['ss_status' => 1]);
        $this->data['store_service']   = $this->custom->get_where('store_service', ['st_status' => 1]);

        switch ($action) {
            default:
                // Latest shift date first here, unlike the applicant-facing
                // lists: the admin is looking at what has just been posted
                // rather than shopping for the next shift to work.
                if ($this->input->get('filter') && $this->input->get('filter') === 'new') {
                    $jobs = $this->custom->get_where_order('post_job', ['p_approved' => 0], shiftDateOrderBy('', 'DESC'), '', false);
                } else {
                    $jobs = $this->custom->get_data_order('post_job', shiftDateOrderBy('', 'DESC'), '', false);
                }

                $this->data['jobs'] = $jobs;

                $this->load->admin_view('postjobs/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_id', 'Agency/Owner Name', 'required');
                    $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                    $rowData = cleanArray($this->input->post());

                    $u_data = $this->custom->get_where('users', ['u_id' => $this->input->post('u_id')]);

                    // The shift's location is the chosen store - one belonging
                    // to the chosen employer - falling back to the employer's
                    // login columns as before (change request B4).
                    $store = $this->employerStore((int) $this->input->post('p_store_id'), (int) $this->input->post('u_id'));

                    $rowData['p_store_id'] = $store ? $store->s_id : 0;
                    $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $u_data[0]->u_provice;
                    $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $u_data[0]->u_city;

                    $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
                    $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
                    $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
                    $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);

                    $rowData['created']  = date('Y-m-d H:i:s');
                    $rowData['modified'] = date('Y-m-d H:i:s');
                    $rowData['p_status'] = 1;
                    unset($rowData['savedata'], $rowData['files']);

                    if (insertQry($table, $rowData, 'newjob')) {
                        $id = $this->db->insertID();

                        $uData['p_job_title'] = 'PAS-' . $id;

                        updateQry($table, $uData, ['p_id' => $id]);

                        ci_redirect('sadmin/postjobs', 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);
                }

                $this->data['agencies'] = $this->custom->get_where_order('users', ['u_usertype' => 1, 'u_status' => 1], 'u_comp_name', 'asc');

                // Stores of the already-chosen employer, so the picker is
                // populated on a re-render; picking an employer refreshes the
                // list over ajax_getstorelist.
                $this->data['agency_stores'] = empty($this->data['u_id'])
                    ? []
                    : $this->custom->get_where_order('store', ['u_id' => $this->data['u_id'], 's_status' => 1], 's_name', 'asc');

                $this->load->admin_view('postjobs/add', $this->data);
                break;

            case 'edit':
                $shift_approved = $this->custom->get_where_row('post_job', ['p_id' => $id]);

                // A shift that already has a booked applicant is frozen.
                $applied_approved = $this->db->table('stu_saved_applied_jobs')
                    ->where('p_id', $id)
                    ->where('sj_is_approved', 1)
                    ->countAllResults();

                if ($applied_approved === 0) {
                    if ($this->input->post('savedata')) {
                        $this->form_validation->set_rules('u_id', 'Agency/Owner Name', 'required');
                        $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                        $rowData = cleanArray($this->input->post());

                        $u_data = $this->custom->get_where('users', ['u_id' => $this->input->post('u_id')]);

                        // Same as on add: the location comes off the chosen store.
                        $store = $this->employerStore((int) $this->input->post('p_store_id'), (int) $this->input->post('u_id'));

                        $rowData['p_store_id'] = $store ? $store->s_id : 0;
                        $rowData['p_province'] = ($store && $store->s_province) ? $store->s_province : $u_data[0]->u_provice;
                        $rowData['p_city']     = ($store && $store->s_city) ? $store->s_city : $u_data[0]->u_city;

                        $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
                        $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
                        $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
                        $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);

                        $rowData['modified'] = date('Y-m-d H:i:s');
                        $rowData['p_status'] = 1;
                        unset($rowData['savedata'], $rowData['files']);

                        if (updateQry($table, $rowData, ['p_id' => $id])) {
                            if ($rowData['p_approved'] == 2 || $rowData['p_approved'] == 3) {
                                $rejected = $this->db->table($table)
                                    ->whereIn('p_id', [$rowData['p_id']])
                                    ->where('p_approved', 2)
                                    ->countAllResults();

                                if ($rejected > 0) {
                                    $this->db->table('stu_saved_applied_jobs')
                                        ->where('p_id', $id)
                                        ->update(['sj_is_approved' => 2]);
                                }
                            }

                            if ($shift_approved['p_approved'] != 1 && $rowData['p_approved'] == 1) {
                                $email = $u_data[0]->u_email;

                                $this->data['name'] = $u_data[0]->u_fname . ' ' . $u_data[0]->u_lname;

                                $subject = 'Your Shift Has Been Posted on ' . $this->data['settings'][0]->s_sitename . '!';
                                $message = email_body('shift-posted', [
                                    'title'       => 'Your shift is now live',
                                    'name'        => $this->data['name'],
                                    'shift_title' => $shift_approved['p_job_title'],
                                    'settings'    => $this->data['settings'],
                                ]);

                                if (send_email($email, $subject, $message)) {
                                    log_message('info', 'Email sent successfully!');
                                } else {
                                    log_message('error', 'Failed to send email.');
                                }
                            }

                            ci_redirect('sadmin/postjobs', 'refresh');
                        }

                        foreach ($rowData as $ky => $vl) {
                            $this->data[$ky] = $vl;
                        }
                    } else {
                        getTableInfo($this->dbname, $table, ['p_id' => $id]);
                    }
                } else {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">This Shift cannot be modified!</div>');
                    ci_redirect('sadmin/postjobs', 'refresh');
                }

                $this->data['agencies'] = $this->custom->get_where_order('users', ['u_usertype' => 1, 'u_status' => 1], 'u_comp_name', 'asc');

                $this->data['agency_stores'] = empty($this->data['u_id'])
                    ? []
                    : $this->custom->get_where_order('store', ['u_id' => $this->data['u_id'], 's_status' => 1], 's_name', 'asc');

                $this->load->admin_view('postjobs/edit', $this->data);
                break;

            case 'delete':
                $applied_approved = $this->db->table('stu_saved_applied_jobs')
                    ->where('p_id', $id)
                    ->where('sj_is_approved', 1)
                    ->countAllResults();

                if ($applied_approved === 0) {
                    $this->custom->delete_where($table, ['p_id' => $id]);
                } else {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">This Shift cannot be deleted!</div>');
                }

                ci_redirect('sadmin/postjobs', 'refresh');
                break;
        }
    }

    public function applications()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'stu_saved_applied_jobs';
        $idnotFound = 0;

        $this->data['pageinfo'] = ['title' => 'Job Applications', 'link' => $this->data['link']];

        switch ($action) {
            default:
                if ($this->input->get('filter') && $this->input->get('filter') === 'booked') {
                    $applicationslist = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates from stu_saved_applied_jobs ssa, users u, post_job pj where ssa.agency_id=u.u_id and  ssa.p_id = pj.p_id and ssa.sj_is_approved = 1 ');
                } else {
                    $applicationslist = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates from stu_saved_applied_jobs ssa, users u, post_job pj where ssa.agency_id=u.u_id and  ssa.p_id = pj.p_id ');
                }

                $this->data['applicationslist'] = $applicationslist;

                $this->load->admin_view('application/index', $this->data);
                break;

            case 'view':
                $this->form_validation->set_rules('sj_is_approved', 'Select Approval Status', 'required');
                $this->form_validation->set_rules('sj_admin_comment', 'Enter Response/Message', 'required');

                if ($this->input->post('savedata')) {
                    $rowData = cleanArray($this->input->post());

                    unset($rowData['savedata']);
                    $rowData['sj_accept_date'] = date('Y-m-d H:i:s');

                    if ($this->input->post('sj_is_approved') != 1) {
                        updateQry($table, $rowData, ['sj_id' => $id]);

                        ci_redirect('sadmin/' . $module, 'refresh');
                    }

                    // The approval path writes directly rather than through
                    // updateQry(), so the rules above have to be run explicitly -
                    // otherwise a booking could be confirmed with no message.
                    if ($this->form_validation->run() !== true) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">' . validation_errors() . '</div>');
                        ci_redirect('sadmin/' . $module . '/view/' . $id, 'refresh');
                    }

                    // Approving one applicant rejects everybody else on the shift.
                    $approvedCase = 'CASE WHEN sj_id = ' . (int) $id . ' THEN 1 ELSE 2 END';
                    $commentCase  = 'CASE WHEN sj_id = ' . (int) $id . ' THEN ' . $this->db->escape($rowData['sj_admin_comment']) . ' ELSE sj_admin_comment END';

                    // When the booking happened. This path bypasses updateQry(),
                    // so the sj_accept_date set above never reached the database
                    // and every booked row was left NULL - which is why nothing
                    // could report bookings per month. Only the approved row is
                    // stamped; the ones being rejected keep theirs unset.
                    $now         = $this->db->escape(date('Y-m-d H:i:s'));
                    $acceptCase  = 'CASE WHEN sj_id = ' . (int) $id . ' THEN ' . $now . ' ELSE sj_accept_date END';

                    $updated = $this->db->table($table)
                        ->set('sj_is_approved', $approvedCase, false)
                        ->set('sj_admin_comment', $commentCase, false)
                        ->set('sj_accept_date', $acceptCase, false)
                        ->set('modified', $now, false)
                        ->where('p_id', $rowData['p_id'])
                        ->update();

                    if ($updated) {
                        // Once somebody is booked, the shift itself is closed.
                        $booked = $this->db->table($table)
                            ->where('p_id', $rowData['p_id'])
                            ->where('sj_is_approved', 1)
                            ->countAllResults();

                        if ($booked > 0) {
                            $this->db->table('post_job')
                                ->where('p_id', $rowData['p_id'])
                                ->update(['p_approved' => 3]);
                        }

                        $query = $this->db->table('stu_saved_applied_jobs s')
                            ->select('u.*, s.agency_id')
                            ->join('users u', 'u.u_id = s.u_id', 'inner')
                            ->where('s.p_id', $rowData['p_id'])
                            ->where('s.u_id', $rowData['u_id'])
                            ->where('s.sj_is_approved', 1)
                            ->get();

                        if ($query->getNumRows() > 0) {
                            $user       = $query->getRow();
                            $user_email = $user->u_email;
                            $user_name  = $user->u_fname . ' ' . $user->u_lname;

                            $shift_detail = $this->custom->get_where_row('post_job', ['p_id' => $rowData['p_id']]);

                            $employer_detail = $this->custom->get_where_row('users', ['u_id' => $user->agency_id]);

                            // The agency keeps a copy of both halves of the booking.
                            $agency_copy = getAgencyCopyEmail();

                            $applicant_email   = $user_email;
                            $applicant_subject = 'Congratulations! You Have Been Approved for Shift ID : ' . $shift_detail['p_job_title'];
                            $applicant_message = email_body('booking-applicant', [
                                'title'            => 'You have been approved for a shift',
                                'name'             => $user_name,
                                'shift'            => $shift_detail,
                                'employer'         => $employer_detail,
                                'approval_comment' => $rowData['sj_admin_comment'],
                                'settings'         => $this->data['settings'],
                            ]);

                            if (send_email($applicant_email, $applicant_subject, $applicant_message, $agency_copy)) {
                                log_message('info', 'Email sent successfully!');
                            } else {
                                log_message('error', 'Failed to send email.');
                            }

                            $employer_email   = $employer_detail['u_email'];
                            $employer_subject = 'A New Applicant Has Been Approved for Shift ID : ' . $shift_detail['p_job_title'];
                            $employer_message = email_body('booking-employer', [
                                'title'          => 'An applicant has been approved for your shift',
                                'name'           => $employer_detail['u_fname'] . ' ' . $employer_detail['u_lname'],
                                'applicant_name' => $user_name,
                                'applicant'      => $user,
                                'shift'          => $shift_detail,
                                'settings'       => $this->data['settings'],
                            ]);

                            if (send_email($employer_email, $employer_subject, $employer_message, $agency_copy)) {
                                log_message('info', 'Email sent successfully!');
                            } else {
                                log_message('error', 'Failed to send email.');
                            }
                        }

                        $this->session->set_flashdata('error_msg', UPDATE);
                    } else {
                        $this->session->set_flashdata('error_msg', WRONG);
                    }

                    ci_redirect('sadmin/' . $module, 'refresh');
                }

                $application = $this->custom->query(
                    'select ssa.*, phrmcist.*, pj.p_job_title, pj.p_hourly_rate, pj.p_ac_hourly_rate, pj.p_dates, pj.p_shift_time '
                    . 'from stu_saved_applied_jobs ssa, users phrmcist, post_job pj '
                    . 'where sj_id = ? and ssa.u_id = phrmcist.u_id and ssa.p_id = pj.p_id',
                    [$id]
                );

                $this->data['application'] = $application[0];

                getTableInfo($this->dbname, 'users', ['u_id' => $application[0]->agency_id]);

                $this->load->admin_view('application/view', $this->data);
                break;

            case 'approve':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['sj_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 'sj_is_approved', 'sj_id', $id);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                    }

                    ci_redirect('sadmin/' . $module, 'refresh');
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module, 'refresh');
                }
                break;

            case 'reject':
                if ($id) {
                    $original_row = $this->custom->get_where($table, ['sj_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 'sj_is_approved', 'sj_id', $id, 2);
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Record has been updated.</div>');
                    }

                    ci_redirect('sadmin/' . $module, 'refresh');
                } else {
                    $idnotFound = 1;
                }

                if ($idnotFound === 1) {
                    ci_redirect('sadmin/' . $module, 'refresh');
                }
                break;
        }
    }

    /*
     * `applied_applicants()` was removed on 4 Aug 2026. It asked for the view
     * admin/application/applied_applicants.php, which has never existed, so the
     * URL returned a server error on every request. Nothing linked to it, and
     * /sadmin/applications already lists the same information.
     */

    public function settings()
    {
        $this->setup();

        $table = 'settings';

        $this->data['pageinfo'] = ['title' => 'Settings', 'link' => $this->data['link']];
        $this->data['settings'] = $this->custom->get_data($table);

        if ($this->input->post('updatedata')) {
            $this->form_validation->set_rules('s_sitename', 'Website Name', 'required');

            $rowData = cleanArray($this->input->post());

            $rowData['s_disclaimer']       = $this->input->post('s_disclaimer');
            $rowData['s_terms_conditions'] = $this->input->post('s_terms_conditions');
            $rowData['s_privacy_policy']   = $this->input->post('s_privacy_policy');
            unset($rowData['updatedata'], $rowData['files']);

            if (updateQry($table, $rowData, ['s_id' => 1])) {
                ci_redirect('sadmin/settings');
            }

            foreach ($rowData as $ky => $vl) {
                $this->data[$ky] = $vl;
            }
        } else {
            getTableInfo($this->dbname, $table, ['s_id' => 1]);
        }

        $this->load->admin_view('settings/edit', $this->data);
    }

    /**
     * One of the given employer's active stores, or null - so a shift can
     * only be filed under a store its employer actually owns.
     */
    private function employerStore(int $storeId, int $employerId)
    {
        if ($storeId <= 0 || $employerId <= 0) {
            return null;
        }

        $rows = $this->custom->get_where('store', [
            's_id'     => $storeId,
            'u_id'     => $employerId,
            's_status' => 1,
        ]);

        return $rows[0] ?? null;
    }

    /**
     * `<option>`s for the store picker on the shift form, refreshed when the
     * employer changes - same shape as ajax_getcitylist above.
     */
    public function ajax_getstorelist()
    {
        $this->setup();

        $uid = (int) $this->input->post('u_id');
        $sid = (int) $this->input->post('sid');

        $stores = $this->db->table('store')
            ->orderBy('s_name', 'asc')
            ->getWhere(['u_id' => $uid, 's_status' => 1])
            ->getResult();

        $store_data = '<option value="">-- Select Store --</option>';

        foreach ($stores as $store) {
            $selected = ($sid == $store->s_id) ? 'selected' : '';
            $label    = $store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : '');
            $store_data .= '<option value="' . $store->s_id . '" ' . $selected . '>' . esc($label) . '</option>';
        }

        echo $store_data;
    }

    public function ajax_getcitylist()
    {
        $this->setup();

        $cval = $this->input->post('statecode');
        $ciid = $this->input->post('ciid');

        $cities = $this->db->table('city')
            ->orderBy('c_name', 'asc')
            ->getWhere(['c_province' => $cval, 'c_status' => 1])
            ->getResult();

        $city_data = '<option value="">Select City</option>';

        foreach ($cities as $city) {
            $selected = ($ciid == $city->c_id) ? 'selected' : '';
            $city_data .= '<option value="' . $city->c_id . '" ' . $selected . '>' . $city->c_name . '</option>';
        }

        echo $city_data;
    }

    /**
     * Bulk e-mail sender used by the "Send Email" admin screen.
     */
    public function send()
    {
        $this->setup();

        $to      = $this->input->post('to'); // Recipients (comma-separated)
        $subject = $this->input->post('subject');
        $message = $this->input->post('message');

        $emails = array_filter(array_map('trim', explode(',', (string) $to)), 'strlen');

        if ($emails === []) {
            echo 'Message could not be sent: no recipients.';

            return;
        }

        // Sends through this site's own mail configuration (app/Config/Email.php,
        // overridable in .env). It previously carried a third party's SMTP
        // credentials in the source; those have been removed.
        $settings = config('AppSettings');

        $email = service('email');

        $email->initialize([
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'newline'  => "\r\n",
            'CRLF'     => "\r\n",
        ]);

        $email->setFrom($settings->mailFromEmail, $settings->mailFromName);
        $email->setTo($emails);
        $email->setSubject((string) $subject);
        $email->setMessage((string) $message);
        $email->setAltMessage(strip_tags((string) $message));

        if ($email->send()) {
            echo 'Message has been sent to all recipients';
        } else {
            log_message('error', $email->printDebugger(['headers']));
            echo 'Message could not be sent.';
        }
    }

    /**
     * Monthly figures: bookings, new employers, new applicants.
     *
     * `?export=csv` returns the same rows as a download rather than a page.
     *
     * Bookings are dated by `sj_accept_date`, which was never written before
     * 6 Aug 2026 - see the note in the view. Rows without it fall back to the
     * date the application arrived, which is the closest thing that exists.
     */
    public function reports()
    {
        $this->setup();

        $this->data['pageinfo'] = ['title' => 'Reports', 'link' => $this->data['link']];

        $from = $this->input->get('from') ?: date('Y-m-01', strtotime('-11 months'));
        $to   = $this->input->get('to') ?: date('Y-m-t');

        // Normalise, so a hand-edited URL cannot reach the query as anything
        // other than a date.
        $from = date('Y-m-d', strtotime($from) ?: strtotime('-11 months'));
        $to   = date('Y-m-d', strtotime($to) ?: time());

        $this->data['from'] = $from;
        $this->data['to']   = $to;

        $bookings = $this->custom->query(
            "SELECT DATE_FORMAT(COALESCE(sj_accept_date, created), '%Y-%m') AS mth, COUNT(*) AS n
               FROM stu_saved_applied_jobs
              WHERE sj_is_approved = 1
                AND DATE(COALESCE(sj_accept_date, created)) BETWEEN ? AND ?
           GROUP BY mth",
            [$from, $to]
        );

        $employers = $this->custom->query(
            "SELECT DATE_FORMAT(created, '%Y-%m') AS mth, COUNT(*) AS n
               FROM users
              WHERE u_usertype = 1 AND created IS NOT NULL
                AND DATE(created) BETWEEN ? AND ?
           GROUP BY mth",
            [$from, $to]
        );

        $applicants = $this->custom->query(
            "SELECT DATE_FORMAT(created, '%Y-%m') AS mth, COUNT(*) AS n
               FROM users
              WHERE u_usertype = 2 AND created IS NOT NULL
                AND DATE(created) BETWEEN ? AND ?
           GROUP BY mth",
            [$from, $to]
        );

        $byMonth = static function (array $rows): array {
            $out = [];

            foreach ($rows as $r) {
                $out[$r->mth] = (int) $r->n;
            }

            return $out;
        };

        $bookings   = $byMonth($bookings);
        $employers  = $byMonth($employers);
        $applicants = $byMonth($applicants);

        // Every month in the range, so a month with nothing in it reads as a
        // zero rather than vanishing from the table.
        $rows   = [];
        $cursor = strtotime(date('Y-m-01', strtotime($from)));
        $end    = strtotime(date('Y-m-01', strtotime($to)));

        while ($cursor <= $end) {
            $key    = date('Y-m', $cursor);
            $rows[] = [
                'month'      => $key,
                'label'      => date('M Y', $cursor),
                'bookings'   => $bookings[$key] ?? 0,
                'employers'  => $employers[$key] ?? 0,
                'applicants' => $applicants[$key] ?? 0,
            ];
            $cursor = strtotime('+1 month', $cursor);
        }

        $this->data['rows']   = $rows;
        $this->data['totals'] = [
            'bookings'   => array_sum(array_column($rows, 'bookings')),
            'employers'  => array_sum(array_column($rows, 'employers')),
            'applicants' => array_sum(array_column($rows, 'applicants')),
        ];

        if ($this->input->get('export') === 'csv') {
            $this->exportReportCsv($rows, $from, $to);

            return;
        }

        $this->load->admin_view('reports/index', $this->data);
    }

    /** Stream the monthly figures as a CSV download. */
    private function exportReportCsv(array $rows, string $from, string $to): void
    {
        $name = 'pickashift-monthly-' . $from . '-to-' . $to . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Month', 'Shifts booked', 'New employers', 'New applicants']);

        foreach ($rows as $r) {
            fputcsv($out, [$r['label'], $r['bookings'], $r['employers'], $r['applicants']]);
        }

        fputcsv($out, [
            'Total',
            array_sum(array_column($rows, 'bookings')),
            array_sum(array_column($rows, 'employers')),
            array_sum(array_column($rows, 'applicants')),
        ]);

        fclose($out);
        exit;
    }

    /**
     * The e-mail form view.
     */
    public function send_email()
    {
        $this->setup();

        $this->data['pageinfo'] = ['title' => 'Email', 'link' => $this->data['link']];

        $this->load->admin_view('settings/email_send', $this->data);
    }

    public function changepassword()
    {
        $this->setup();

        $this->form_validation->set_rules('current_password', 'Current Password', 'required');
        $this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[5]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');

        if ($this->form_validation->run() !== false) {
            $current_password = $this->input->post('current_password');
            $new_password     = $this->custom->hashPassword((string) $this->input->post('new_password'));

            $user_id = $this->session->userdata('adminUserId');

            // Check if the current password matches
            if ($this->custom->verify_password($user_id, $current_password)) {
                $this->custom->update_password($user_id, $new_password);
                $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Password updated successfully.</div>');
                ci_redirect('sadmin/changepassword');
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Current password is incorrect.</div>');
            }
        }

        // Reload the form if validation fails
        $this->load->admin_view('changepassword', $this->data);
    }
}
