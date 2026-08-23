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

            // An administrator account that has been switched off, or removed,
            // stops working on the next page rather than at the end of its
            // session. Same rule as the two portals - see
            // BaseController::stopIfAccountClosed().
            $this->stopIfAccountClosed($this->data['userdet'], 'sadmin/login');

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
     * Keyed by `employerKinds` code, so the sidebar can look a badge up by the
     * same number it draws the entry from, plus the 'applicant' and 'employer'
     * totals.
     *
     * @return array<int|string, int>
     */
    private function pendingUserCounts(): array
    {
        $counts = ['applicant' => 0, 'employer' => 0];

        foreach (array_keys((array) $this->config->item('employerKinds')) as $code) {
            $counts[$code] = 0;
        }

        // The role alone says which kind an account is now, so the group is one
        // column narrower than it was.
        $rows = $this->custom->query(
            'SELECT u_usertype, u_emp_role, COUNT(*) AS total
               FROM users
              WHERE u_status = 0 AND u_usertype IN (1, 2)
           GROUP BY u_usertype, u_emp_role'
        );

        foreach ($rows ?: [] as $row) {
            $total = (int) $row->total;

            if ((int) $row->u_usertype === 2) {
                $counts['applicant'] += $total;

                continue;
            }

            $counts['employer'] += $total;

            $code = employerKindCode(['u_emp_role' => $row->u_emp_role]);

            if ($code !== 0 && isset($counts[$code])) {
                $counts[$code] += $total;
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

                    // The back office is the account worth guessing at, so it
                    // locks the same way the front one does. See
                    // CustomModel::loginLockRemaining().
                    $lockedFor = $checkLogin ? $this->custom->loginLockRemaining($checkLogin) : 0;

                    if ($lockedFor > 0) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Too many failed sign-in attempts. Try again in ' . (int) ceil($lockedFor / 60) . ' minute(s).</div>');
                    } elseif ($checkLogin && $this->custom->passwordMatches((string) $this->input->post('password'), $checkLogin)) {
                        $this->custom->clearLoginAttempts($checkLogin['u_id']);

                        // A new session id for the administrator's session, for
                        // the reason given in Front::login(): the id the browser
                        // arrived with may not be one it chose for itself.
                        $this->session->sess_regenerate(true);

                        $this->session->set_userdata('isAdminUserLoggedIn', true);
                        $this->session->set_userdata('adminUserId', $checkLogin['u_id']);

                        ci_redirect('sadmin/dashboard');
                    } else {
                        if ($checkLogin) {
                            $this->custom->recordFailedLogin($checkLogin['u_id']);
                        }

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

    /**
     * One `employerKinds` filter as a bound WHERE fragment.
     *
     * The filters are written as query-builder arrays (`['u_parent_id >' => 0]`)
     * because that is how the employer list applies them; the dashboard needs
     * the same conditions inside a hand-written query. Keys come from config
     * and never from a request, and the values are bound.
     *
     * @param array<string, int> $filter
     *
     * @return array{0: string, 1: list<int>} clause and its binds
     */
    private function kindFilterSql(array $filter): array
    {
        $clauses = [];
        $binds   = [];

        foreach ($filter as $key => $value) {
            // 'u_parent_id >' is column and operator in one key, the way the
            // query builder reads it; a bare key means equality.
            $parts = explode(' ', trim($key), 2);

            $clauses[] = $parts[0] . ' ' . ($parts[1] ?? '=') . ' ?';
            $binds[]   = $value;
        }

        return [implode(' AND ', $clauses), $binds];
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

        $this->data['applicationslist'] = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates, pj.p_job_title, ap.u_fname AS applicant_fname, ap.u_lname AS applicant_lname from stu_saved_applied_jobs ssa join users u on u.u_id = ssa.agency_id join post_job pj on pj.p_id = ssa.p_id left join users ap on ap.u_id = ssa.u_id where 1 = 1 ');

        $this->data['booked_applications'] = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates, pj.p_job_title, ap.u_fname AS applicant_fname, ap.u_lname AS applicant_lname from stu_saved_applied_jobs ssa join users u on u.u_id = ssa.agency_id join post_job pj on pj.p_id = ssa.p_id left join users ap on ap.u_id = ssa.u_id where 1 = 1 and ssa.sj_is_approved = 1 ');

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

        $newEmployerCols = 'u_id, u_comp_name, u_fname, u_lname, u_email, u_status, created';

        $this->data['new_employers'] = $this->custom->query(
            'SELECT ' . $newEmployerCols . '
               FROM users WHERE u_usertype = 1 AND created >= ?
           ORDER BY created DESC LIMIT 25',
            [$since]
        );

        // The panel splits new employers the way the sidebar splits the
        // employer list, a tab per kind. Each kind is asked for on its own
        // rather than sliced out of the 25 above: a busy week of one kind would
        // push the others past the limit, and an empty tab reads as "none
        // registered" rather than "crowded out".
        $newEmployersByKind = [];

        foreach ((array) $this->config->item('employerKinds') as $code => $kindDef) {
            [$clause, $binds] = $this->kindFilterSql($kindDef['filter']);

            $newEmployersByKind[$code] = $this->custom->query(
                'SELECT ' . $newEmployerCols . '
                   FROM users WHERE u_usertype = 1 AND created >= ? AND ' . $clause . '
               ORDER BY created DESC LIMIT 25',
                array_merge([$since], $binds)
            );
        }

        $this->data['new_employers_by_kind'] = $newEmployersByKind;

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

    /**
     * The Additional Details master, the same CRUD as Services.
     *
     * No dependency guard on delete or change status, unlike `storeservice`:
     * nothing references `ad_id` yet. Whatever screen comes to use this list
     * should add one here the way Services checks `post_job.p_services`,
     * otherwise a row still in use can be removed from under it.
     */
    public function additionaldetails()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'additional_details';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Additional Detail', 'link' => $module];
        $this->data['additionaldetails'] = $this->custom->get_data_order($table, 'ad_name', 'asc');

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('ad_name', 'Additional detail name', 'required|is_unique[additional_details.ad_name]');
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
                    $original_row     = $this->custom->get_where($table, ['ad_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $is_unique = ($this->input->post('ad_name') !== $original_row[0]->ad_name)
                                ? '|is_unique[additional_details.ad_name]'
                                : '';

                            $this->form_validation->set_rules('ad_name', 'Additional detail name', 'required' . $is_unique);
                            $this->form_validation->set_message('is_unique', 'Sorry, the {field} you entered is already taken. Please choose another.');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['ad_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['ad_id' => $id]);
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
                    $original_row = $this->custom->get_where($table, ['ad_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 'ad_status', 'ad_id', $id);
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
                    $original_row = $this->custom->get_where($table, ['ad_id' => $id]);

                    if ($original_row) {
                        $this->custom->delete_where($table, ['ad_id' => $id]);
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

    /**
     * Testimonials: the quotes the home page rotates through, under "What Makes
     * Us Stand Out".
     *
     * Unlike the other master lists, the title is not unique. Two testimonials
     * praising the same thing may well be headed the same way, and it is the
     * quote underneath that differs.
     *
     * Ordered oldest first, so the carousel opens on the first one added and the
     * admin can predict the running order from the list screen.
     */
    public function testimonials()
    {
        $this->setup();

        $module     = $this->uri->segment(2);
        $action     = $this->uri->segment(3);
        $id         = $this->uri->segment(4);
        $table      = 'testimonial';
        $idnotFound = 0;

        $this->data['validation_errors'] = '';
        $this->data['pageinfo']          = ['title' => 'Testimonial', 'link' => $module];
        $this->data['testimonials']      = $this->custom->get_data_order($table, 't_id', 'asc');

        switch ($action) {
            default:
                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('t_title', 'Title', 'required');
                    $this->form_validation->set_rules('t_description', 'Description', 'required');

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
                    $original_row     = $this->custom->get_where($table, ['t_id' => $id]);
                    $this->data['id'] = $id;

                    if ($original_row) {
                        if ($this->input->post('updatedata')) {
                            $this->form_validation->set_rules('t_title', 'Title', 'required');
                            $this->form_validation->set_rules('t_description', 'Description', 'required');

                            $rowData = cleanArray($this->input->post());
                            unset($rowData['updatedata']);

                            if (updateQry($table, $rowData, ['t_id' => $id])) {
                                ci_redirect('sadmin/' . $module);
                            }

                            foreach ($rowData as $ky => $vl) {
                                $this->data[$ky] = $vl;
                            }
                        } else {
                            getTableInfo($this->dbname, $table, ['t_id' => $id]);
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
                    $original_row = $this->custom->get_where($table, ['t_id' => $id]);

                    if ($original_row) {
                        $this->custom->toggleStatus($table, 't_status', 't_id', $id);
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
                    $original_row = $this->custom->get_where($table, ['t_id' => $id]);

                    if ($original_row) {
                        $this->custom->delete_where($table, ['t_id' => $id]);
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

    /**
     * Manage Email: who receives which of the site's e-mails.
     *
     * The list shows every account - administrators, employers and applicants
     * alike - because every one of them can be a recipient. Each row's button
     * opens the per-user page of checkboxes; a ticked box means "send it", and
     * what is stored is the inverse (`u_email_blocked`), so a new e-mail type
     * added to the config later is on for everybody at once.
     *
     * The boxes are the e-mails that account can actually be sent, not the
     * whole list - `emailTypesFor()` decides, from the side of the site they
     * are on. An applicant was being offered a switch for "your shift is live"
     * and an employer one for the day-before reminder; neither send site would
     * ever have looked at them.
     *
     * reset-password is not on the page at all - see the note on `emailTypes`
     * in Config\AppSettings: it is the only channel the reset token travels,
     * so it is always sent. booking-cancelled is absent for the reason given
     * there too.
     */
    public function manageemail()
    {
        $this->setup();

        $module = $this->uri->segment(2);
        $action = $this->uri->segment(3);
        $id     = (int) $this->uri->segment(4);

        $this->data['pageinfo']   = ['title' => 'Email', 'link' => $this->data['link']];
        $this->data['emailTypes'] = (array) $this->config->item('emailTypes');

        switch ($action) {
            default:
                $this->data['users'] = $this->custom->get_where_order('users', [], 'u_email', 'asc');

                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'permissions':
                $user = $this->custom->get_where_row('users', ['u_id' => $id]);

                if (! $user) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid user.</div>');
                    ci_redirect('sadmin/' . $module);
                }

                // Only the e-mails this account can actually be sent - an
                // applicant is never told a shift of theirs is live.
                $offered = emailTypesFor($user);

                if ($this->input->post('savedata')) {
                    // Ticked = may receive. Stored = the rest. An all-clear
                    // form posts no boxes at all, which correctly blocks
                    // every type rather than being mistaken for "no change".
                    $allowed = array_map('intval', (array) $this->input->post('email_allowed'));
                    $blocked = array_diff(array_keys($offered), $allowed);

                    // A form changes only what it showed. Anything blocked on a
                    // type this account is not offered - set before the list was
                    // split by side, or by an earlier account type - is carried
                    // through rather than quietly cleared.
                    $wasBlocked = array_map('intval', array_filter(explode(',', (string) ($user['u_email_blocked'] ?? '')), 'strlen'));
                    $blocked    = array_unique(array_merge($blocked, array_diff($wasBlocked, array_keys($offered))));

                    sort($blocked);

                    $this->custom->updateData(
                        'users',
                        ['u_email_blocked' => implode(',', $blocked), 'modified' => date('Y-m-d H:i:s')],
                        ['u_id' => $id]
                    );

                    $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Email permissions updated.</div>');
                    ci_redirect('sadmin/' . $module);
                }

                $this->data['emailTypes'] = $offered;
                $this->data['user']       = $user;

                $this->load->admin_view($module . '/permissions', $this->data);
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

        // The sidebar links each employer kind by its slug -
        // /sadmin/employer/owner - so anything else in that segment is an
        // action, the way it always was. The slug is only ever a URL: what the
        // rest of this method carries is the code behind it.
        $kind = employerKindBySlug((string) $action);

        // add/edit/delete/changestatus carry the list they were reached from as
        // ?kind=, so saving or activating comes back to that list rather than
        // dropping the admin into All Employers.
        if ($kind === 0) {
            $kind = employerKindBySlug((string) $this->input->get('kind'));
        }

        $kindSlug = $kind !== 0 ? $kinds[$kind]['slug'] : '';
        $backTo   = 'sadmin/' . $module . ($kindSlug !== '' ? '/' . $kindSlug : '');

        $this->data['pageinfo'] = [
            'title'     => 'Employer',
            'listtitle' => $kind !== 0 ? $kinds[$kind]['label'] : 'All Employers',
            'link'      => $this->data['link'],
        ];

        $this->data['kind']     = $kind;
        $this->data['kindSlug'] = $kindSlug;
        $this->data['backTo']   = $backTo;

        switch ($action) {
            default:
                // An account from before the kinds existed carries role 0 and
                // so matches none of them - it shows up under All Employers,
                // which is why that entry is kept.
                $where = ['u_usertype' => 1];

                if ($kind !== 0) {
                    $where = array_merge($where, $kinds[$kind]['filter']);
                }

                $this->data['users'] = $this->custom->get_where_order('users', $where, 'u_comp_name', 'asc');

                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    // Employer Name is required below, once the kind is known -
                    // a manager is never asked for one.
                    // Same rule the public form uses: the e-mail becomes the
                    // login id, so it has to be a real address and unused.
                    $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|is_unique[users.u_userid]');
                    $this->form_validation->set_rules('u_phone', 'Company Conatct No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);
                    // The same name rule the public forms apply: letters, spaces
                    // and the punctuation real names contain. Without it the back
                    // office accepted digits and symbols the rest of the site
                    // rejects, on the very column those forms guard.
                    $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('emp_kind', 'Employer Type', 'required|in_list[' . implode(',', array_keys($kinds)) . ']');
                    $this->form_validation->set_message('is_unique', 'The %s is already taken');

                    $empKind = (string) $this->input->post('emp_kind');
                    $shape   = employerKindRole($empKind);

                    if ($shape['needsParent']) {
                        $this->form_validation->set_rules('u_parent_id', 'Corporate Group', 'required');
                    }

                    // A manager runs one of the group's existing stores and says
                    // which, rather than describing one of their own. The back
                    // office used to ignore this and type an address instead,
                    // which produced a different record from the one the same
                    // person would have created by registering.
                    $picksStore = $shape['picksStore'];

                    if ($picksStore) {
                        $this->form_validation->set_rules('u_store_id', 'Store', 'required');
                    } else {
                        // The account's own name - a group for an owner, a
                        // store for one location. A manager has neither.
                        //
                        // It has to be theirs alone: the employer list, the
                        // employer dropdown on both shift forms and the booking
                        // e-mails all show an employer by this column, so two
                        // accounts under one name is unreadable on every one of
                        // them. Registration refuses a taken name from the same
                        // helper, so the two forms cannot disagree.
                        $compNameLabel = $shape['role'] === 1 ? 'Corporate Group Name' : 'Store Name';

                        $this->form_validation->set_rules('u_comp_name', $compNameLabel, [
                            'required',
                            employerNameRule($compNameLabel),
                        ]);
                    }

                    $rowData = cleanArray($this->input->post());

                    // The login screen looks accounts up by `u_userid`, and this
                    // form never wrote it - so every employer added here was
                    // saved unable to sign in. Registration fills it from the
                    // e-mail address; this now does the same.
                    $rowData['u_userid']   = $rowData['u_email'] ?? '';
                    // Normalised and scheme-checked: it is rendered as a link.
                    $rowData['u_website']  = safeUrl($this->input->post('u_website'));
                    $rowData['u_pass']     = $this->custom->hashPassword((string) $this->input->post('u_password'));
                    $rowData['created']    = date('Y-m-d H:i:s');
                    $rowData['modified']   = date('Y-m-d H:i:s');
                    $rowData['u_usertype'] = 1;
                    // A clear tick box posts nothing at all, so the answer is
                    // taken from whether the field arrived rather than from its
                    // value - the same way the applicant form reads it.
                    $rowData['u_agreement_done'] = $this->input->post('u_agreement_done') ? 1 : 0;

                    // The same three-way choice registration offers, saved into
                    // the same two columns. Without it every employer added here
                    // landed on role 0 - the pre-B4 shape - and so appeared
                    // under no kind in the sidebar.
                    $rowData['u_emp_role']  = $shape['role'];
                    $rowData['u_parent_id'] = $shape['needsParent'] ? $this->resolvePharmacyGroup($this->input->post('u_parent_id')) : 0;

                    // A manager describes no location and creates no store: the
                    // one they picked already has both.
                    $asksForLocation = $shape['asksForLocation'] && ! $picksStore;
                    $ownsStore       = $shape['ownsStore'] && ! $picksStore;

                    // The store is checked against the group that was just
                    // resolved, never the posted one - that is what stops a
                    // hand-edited form attaching a manager to another group's
                    // location. Same rule registration applies.
                    $store = ($picksStore && $rowData['u_parent_id'] > 0)
                        ? $this->resolveGroupStore($this->input->post('u_store_id'), $rowData['u_parent_id'])
                        : null;

                    $rowData['u_store_id'] = $store ? (int) $store->s_id : 0;

                    // A manager has no company name of their own, exactly as at
                    // registration.
                    if ($picksStore) {
                        $rowData['u_comp_name'] = '';
                    }

                    // A multi-store owner is not asked for a location; blank the
                    // columns rather than saving whatever the hidden inputs held.
                    if (! $asksForLocation) {
                        $rowData = array_merge($rowData, [
                            'u_l_provice'  => 0,
                            'u_licence_no' => '',
                            'u_provice'    => 0,
                            'u_city'       => 0,
                            'u_address1'   => '',
                            'u_pincode'    => '',
                        ]);
                    }

                    // ...and then the store they picked is copied over those
                    // blanks, which is what registration does. Without it a
                    // manager added here has no name and no address, and reads
                    // as an empty row on the employer list and in the employer
                    // dropdown on both shift forms.
                    if ($store) {
                        $rowData = array_merge($rowData, storeSnapshotForManager($store));
                    }

                    // `emp_kind` picks the shape; it is not a column of its own.
                    unset($rowData['savedata'], $rowData['u_password'], $rowData['emp_kind']);

                    if ($shape['needsParent'] && $rowData['u_parent_id'] === 0) {
                        // `required` only proves something was posted; this
                        // catches an id that is not one of the offered groups.
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose the corporate group this manager belongs to.</div>');
                    } elseif ($picksStore && $rowData['u_store_id'] === 0) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of that group\'s stores for this manager to run.</div>');
                    } elseif (insertQry('users', $rowData)) {
                        // A single-location employer's address is their first
                        // store, exactly as at registration - and without one
                        // they could never have a shift posted, because the
                        // shift form only offers stores.
                        if ($ownsStore) {
                            $this->createStoreFromEmployer((int) $this->db->insertID(), $rowData);
                        }

                        ci_redirect($backTo);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }

                    $this->data['emp_kind'] = $empKind;
                } else {
                    getTableInfo($this->dbname, 'users');

                    // Adding from a kind's own list starts on that kind.
                    $this->data['emp_kind'] = $kind;
                }

                $this->data['province']        = $this->custom->get_data('province');
                $this->data['pharmacy_groups'] = pharmacyGroups();

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                $employer_status = $this->custom->get_where_row('users', ['u_id' => $id]);

                if ($this->input->post('savedata')) {
                    // Employer Name is required below, once the kind is known -
                    // a manager is never asked for one.
                    // Ignoring this row, so re-saving without touching the
                    // e-mail is not rejected as a duplicate of itself.
                    $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|is_unique[users.u_userid,u_id,' . (int) $id . ']');
                    $this->form_validation->set_rules('u_phone', 'Company Conatct No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);
                    // The same name rule the public forms apply: letters, spaces
                    // and the punctuation real names contain. Without it the back
                    // office accepted digits and symbols the rest of the site
                    // rejects, on the very column those forms guard.
                    $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('emp_kind', 'Employer Type', 'required|in_list[' . implode(',', array_keys($kinds)) . ']');
                    $this->form_validation->set_message('is_unique', 'The %s is already taken');

                    $empKind = (string) $this->input->post('emp_kind');
                    $shape   = employerKindRole($empKind);

                    if ($shape['needsParent']) {
                        $this->form_validation->set_rules('u_parent_id', 'Corporate Group', 'required');
                    }

                    // The same pair of rules the add form applies, so a manager
                    // edited here is asked exactly what one being created is:
                    // which of the group's stores they run, and no name of
                    // their own.
                    $picksStore = $shape['picksStore'];

                    if ($picksStore) {
                        $this->form_validation->set_rules('u_store_id', 'Store', 'required');
                    } else {
                        // The same name rule the add form applies, and for the
                        // same reason - with two differences. This account is
                        // left out of the search, so re-saving it is not read as
                        // a duplicate of itself; and a name it already holds is
                        // left alone, because accounts that shared one before
                        // the rule existed would otherwise become uneditable
                        // rather than unique. Changing the name is checked.
                        $compNameLabel = $shape['role'] === 1 ? 'Corporate Group Name' : 'Store Name';
                        $postedName    = trim((string) $this->input->post('u_comp_name'));
                        $currentName   = trim((string) ($employer_status['u_comp_name'] ?? ''));
                        $compNameRules = ['required'];

                        if (strcasecmp($postedName, $currentName) !== 0) {
                            $compNameRules[] = employerNameRule($compNameLabel, (int) $id);
                        }

                        $this->form_validation->set_rules('u_comp_name', $compNameLabel, $compNameRules);
                    }

                    $rowData = cleanArray($this->input->post());

                    // The e-mail is the login id, so changing one has to change
                    // the other - otherwise the admin edits the address and the
                    // employer carries on signing in with the old one.
                    $rowData['u_userid']  = $rowData['u_email'] ?? '';
                    $rowData['u_website'] = safeUrl($this->input->post('u_website'));
                    $rowData['modified']  = date('Y-m-d H:i:s');
                    // Read from whether the box arrived, not its value: clearing
                    // it posts nothing, so leaving this out would make the tick
                    // impossible to undo.
                    $rowData['u_agreement_done'] = $this->input->post('u_agreement_done') ? 1 : 0;

                    // Changing this dropdown is how an employer becomes a
                    // multi-store owner - the 62 accounts that predate the
                    // feature all sit on role 0 and belong to no kind.
                    $rowData['u_emp_role']  = $shape['role'];
                    $rowData['u_parent_id'] = $shape['needsParent'] ? $this->resolvePharmacyGroup($this->input->post('u_parent_id')) : 0;

                    // Checked against the group just resolved, never the posted
                    // one - the same rule as on add and at registration, and
                    // what stops a hand-edited form moving a manager onto
                    // another group's store. Cleared for a kind that has no
                    // store of somebody else's to run.
                    $store = ($picksStore && $rowData['u_parent_id'] > 0)
                        ? $this->resolveGroupStore($this->input->post('u_store_id'), $rowData['u_parent_id'])
                        : null;

                    $rowData['u_store_id'] = $store ? (int) $store->s_id : 0;

                    // The store's own name and address, copied onto the login
                    // the way registration and the add form do. It follows the
                    // manager when they are moved to another branch, which is
                    // the point of taking it again on every save: the columns
                    // are hidden on this form, so a stale copy could not be put
                    // right by hand.
                    if ($store) {
                        $rowData = array_merge($rowData, storeSnapshotForManager($store));
                    } elseif ($picksStore) {
                        // Refused below, but the name must not be left as the
                        // account's old one if it somehow reaches the save.
                        $rowData['u_comp_name'] = '';
                    }

                    // Unlike the add form, the location columns are left alone
                    // even for a multi-store owner: a shift created outside the
                    // store flow carries `p_store_id` 0 and reads its address
                    // off them, so blanking them would empty a live shift.
                    unset($rowData['savedata'], $rowData['emp_kind']);

                    $blocked = $this->employerKindChangeBlocker((int) $id, $employer_status, $empKind, $shape);

                    if ($blocked !== '') {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">' . $blocked . '</div>');
                    } elseif ($shape['needsParent'] && $rowData['u_parent_id'] === 0) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose the corporate group this manager belongs to.</div>');
                    } elseif ($picksStore && $rowData['u_store_id'] === 0) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of that group\'s stores for this manager to run.</div>');
                    } elseif (updateQry($table, $rowData, ['u_id' => $id])) {
                        // An employer added from the back office before it
                        // created stores has none, so a kind that owns one gets
                        // it built from their login columns now. Never a
                        // manager: the store they run is somebody else's, and
                        // building one from their own columns - which are blank -
                        // would put a nameless store on the group's list. Add
                        // draws the same distinction.
                        if ($shape['ownsStore'] && ! $picksStore
                            && $this->custom->get_where_count('store', ['u_id' => $id]) === 0) {
                            $this->createStoreFromEmployer((int) $id, $rowData);
                        }

                        // Tell the employer as soon as their account goes live.
                        if ($employer_status['u_status'] == 0 && $rowData['u_status'] == 1) {
                            $this->sendAccountApprovedEmail($employer_status);
                        }

                        ci_redirect($backTo, 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }

                    $this->data['emp_kind'] = $empKind;
                } else {
                    getTableInfo($this->dbname, $table, ['u_id' => $id]);

                    $this->data['emp_kind'] = employerKindCode($employer_status ?: []);
                }

                $this->data['province']        = $this->custom->get_data('province');
                $this->data['pharmacy_groups'] = pharmacyGroups();
                $this->data['store_count']     = $this->custom->get_where_count('store', ['u_id' => $id]);

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
     * The posted corporate group, checked against the list that was offered.
     *
     * The id is never trusted as posted: without this, a hand-edited form could
     * attach a manager to any account at all.
     *
     * @param mixed $posted
     * @return int 0 when it is not one of the approved multi-store owners
     */
    private function resolvePharmacyGroup($posted): int
    {
        $parent = $this->custom->get_where('users', [
            'u_id'       => (int) $posted,
            'u_usertype' => 1,
            'u_emp_role' => 1,
            'u_status'   => 1,
        ]);

        return $parent ? (int) $parent[0]->u_id : 0;
    }

    /**
     * The store a manager runs, proved to belong to their corporate group.
     *
     * Checked against the group that was already resolved, never the posted
     * one: that pairing is what stops a hand-edited form attaching a manager to
     * another group's location. Returns null when the pair does not hold, and
     * the caller refuses the save rather than storing a manager with no store.
     *
     * The whole row rather than its id: the caller copies the store's name and
     * address onto the manager's login, exactly as registration does.
     *
     * @param mixed $posted the posted `u_store_id`
     * @return object|null a `store` row
     */
    private function resolveGroupStore($posted, int $groupId)
    {
        if ($groupId <= 0) {
            return null;
        }

        $stores = $this->custom->get_where('store', [
            's_id'     => (int) $posted,
            'u_id'     => $groupId,
            's_status' => 1,
        ]);

        return $stores[0] ?? null;
    }

    /**
     * Build an employer's first store out of their login columns.
     *
     * A single-location employer registers one address, and that address is the
     * store a shift is posted against - the shift form offers stores and
     * nothing else, so an employer without one can never be given work.
     *
     * @param array<string, mixed> $rowData the `users` row as it was just saved
     */
    private function createStoreFromEmployer(int $userId, array $rowData): void
    {
        if ($userId === 0) {
            return;
        }

        $name = trim((string) ($rowData['u_comp_name'] ?? ''));

        if ($name === '') {
            $name = trim(($rowData['u_fname'] ?? '') . ' ' . ($rowData['u_lname'] ?? ''));
        }

        $this->custom->insert('store', [
            'u_id'       => $userId,
            's_name'     => $name,
            's_number'   => (string) ($rowData['u_licence_no'] ?? ''),
            's_province' => (int) ($rowData['u_provice'] ?? 0),
            's_city'     => (int) ($rowData['u_city'] ?? 0),
            's_address'  => (string) ($rowData['u_address1'] ?? ''),
            's_pincode'  => (string) ($rowData['u_pincode'] ?? ''),
            's_phone'    => (string) ($rowData['u_phone'] ?? ''),
            's_status'   => 1,
        ]);
    }

    /**
     * Why this employer may not become the kind that was chosen, or '' if it may.
     *
     * Converting between kinds moves real records around, and two directions
     * would leave the data contradicting itself: a single-location kind cannot
     * hold the several stores a multi-store owner has collected, and a group
     * that managers answer to cannot stop being a group while they still point
     * at it. Both are reported rather than silently repaired, because only the
     * administrator knows which store or which manager should move.
     *
     * @param array<string, mixed> $employer the row as it stands today
     * @param int|string                                                                  $empKind  the `employerKinds` code being moved to
     * @param array{role: int, needsParent: bool, asksForLocation: bool, ownsStore: bool} $shape
     */
    private function employerKindChangeBlocker(int $id, $employer, $empKind, array $shape): string
    {
        if (employerKindCode($employer ?: []) === (int) $empKind) {
            return '';
        }

        // Becoming a one-location kind while owning several locations.
        if ($shape['role'] === 2) {
            $stores = $this->custom->get_where_count('store', ['u_id' => $id]);

            if ($stores > 1) {
                return 'This employer has ' . $stores . ' stores, so it cannot become a single-store account. '
                    . 'Move or deactivate the extra stores first.';
            }
        }

        // Ceasing to be a group while managers still belong to it.
        if ($shape['role'] !== 1) {
            $managers = $this->custom->get_where_count('users', ['u_parent_id' => $id, 'u_usertype' => 1]);

            if ($managers > 0) {
                return $managers . ' manager account(s) belong to this corporate group, so it must stay an '
                    . 'Owner (Multi Store). Reassign them first.';
            }
        }

        return '';
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

        if (! userAllowsEmail($user, 'account-approved')) {
            log_message('info', 'Account-approved e-mail withheld: user ' . $user['u_id'] . ' opted out.');
        } elseif (send_email($user['u_email'], $subject, $message)) {
            log_message('info', 'Email sent successfully!');
        } else {
            log_message('error', 'Failed to send email.');
        }
    }

    /**
     * The locations an employer owns, maintained from the back office.
     *
     * Employers have managed their own stores since multi-store shipped, but an
     * administrator had no way to: adding a sub-outlet to a chain, or fixing an
     * address, meant opening the database. Every screen here is the employer's
     * own store form with an owner picker in front of it.
     *
     * `?owner=<u_id>` scopes the list, and is carried through add/edit/delete so
     * that saving comes back to the chain being worked on rather than to every
     * store in the system.
     */
    public function stores()
    {
        $this->setup();

        $module = $this->uri->segment(2);
        $action = $this->uri->segment(3);
        $id     = (int) $this->uri->segment(4);
        $table  = 'store';

        $owner  = (int) $this->input->get('owner');
        $backTo = 'sadmin/' . $module . ($owner ? '?owner=' . $owner : '');

        $this->data['pageinfo'] = ['title' => 'Store', 'link' => $this->data['link']];
        $this->data['owner']    = $owner;
        $this->data['backTo']   = $backTo;

        // A store belongs to an owner. A manager is never offered here: they do
        // not own a location, they run one of their group's - which is what the
        // Store dropdown on the employer form assigns. The accounts that
        // predate the kinds carry role 0 and do own their locations, so they
        // stay on the list; without them their existing stores could not be
        // opened without appearing to change hands.
        $this->data['owners'] = $this->custom->get_where_order('users', ['u_usertype' => 1, 'u_emp_role !=' => 2], 'u_comp_name', 'asc');

        // The three lists a store can hold as its shift defaults - the same
        // masters the shift form offers, so the two forms cannot disagree about
        // what is on offer.
        $this->data['software_skills']    = $this->custom->get_where('software_skills', ['ss_status' => 1]);
        $this->data['store_service']      = $this->custom->get_where('store_service', ['st_status' => 1]);
        $this->data['additional_details'] = $this->custom->get_where_order('additional_details', ['ad_status' => 1], 'ad_name', 'asc');

        switch ($action) {
            default:
                $where = $owner ? ['u_id' => $owner] : [];

                $this->data['stores']    = $this->custom->get_where_order($table, $where, 's_name', 'asc');
                $this->data['ownerRow']  = $owner ? $this->custom->get_where_row('users', ['u_id' => $owner]) : null;

                $this->load->admin_view($module . '/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_id', 'Employer', 'required');
                    $this->form_validation->set_rules('s_name', 'Store Name', 'required');

                    $rowData  = $this->storeRowFromPost();
                    $ownerRow = $this->custom->get_where_row('users', ['u_id' => $rowData['u_id'], 'u_usertype' => 1]);

                    if (! $ownerRow) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Choose the employer this store belongs to.</div>');
                    } elseif (($blocked = $this->storeOwnerBlocker($ownerRow)) !== '') {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">' . $blocked . '</div>');
                    } elseif (insertQry($table, $rowData)) {
                        ci_redirect('sadmin/' . $module . '?owner=' . $rowData['u_id']);
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);

                    // Adding from a chain's own list starts on that chain.
                    $this->data['u_id'] = $owner;
                }

                $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);

                $this->load->admin_view($module . '/add', $this->data);
                break;

            case 'edit':
                $store = $this->custom->get_where_row($table, ['s_id' => $id]);

                if (! $store) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid Store</div>');
                    ci_redirect($backTo, 'refresh');
                }

                // A store held by an account the list leaves out - a manager who
                // owned one before the employer form assigned them instead - is
                // still openable, showing who holds it. Without this the picker
                // would open on nothing and the first save would look like the
                // administrator moving the store, or refuse as an empty field.
                if (! in_array((int) $store['u_id'], array_map(static fn ($o) => (int) $o->u_id, $this->data['owners']), true)) {
                    $holder = $this->custom->get_where('users', ['u_id' => (int) $store['u_id']]);

                    if ($holder) {
                        array_unshift($this->data['owners'], $holder[0]);
                    }
                }

                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('u_id', 'Employer', 'required');
                    $this->form_validation->set_rules('s_name', 'Store Name', 'required');

                    $rowData  = $this->storeRowFromPost();
                    $ownerRow = $this->custom->get_where_row('users', ['u_id' => $rowData['u_id'], 'u_usertype' => 1]);

                    // Moving a store to a different employer is a real thing to
                    // want - a branch changes hands - but the receiving account
                    // still has to be one that may hold another location.
                    $moving  = (int) $rowData['u_id'] !== (int) $store['u_id'];
                    $blocked = ($ownerRow && $moving) ? $this->storeOwnerBlocker($ownerRow) : '';

                    if (! $ownerRow) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Choose the employer this store belongs to.</div>');
                    } elseif ($blocked !== '') {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">' . $blocked . '</div>');
                    } elseif (updateQry($table, $rowData, ['s_id' => $id])) {
                        ci_redirect('sadmin/' . $module . '?owner=' . $rowData['u_id'], 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table, ['s_id' => $id]);
                }

                $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);

                $this->load->admin_view($module . '/edit', $this->data);
                break;

            case 'delete':
                // A shift keeps pointing at the store it was posted against, and
                // that is where the booked applicant is expected to turn up.
                // Deleting it would leave those shifts falling back to the
                // owner's login address - a different building.
                $used = $this->custom->get_where_count('post_job', ['p_store_id' => $id]);

                if ($used > 0) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">This store is on ' . $used . ' shift(s), so it cannot be deleted. Deactivate it instead - it will stop being offered on new shifts.</div>');
                } else {
                    $this->custom->delete_where($table, ['s_id' => $id]);
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Store deleted.</div>');
                }

                ci_redirect($backTo, 'refresh');
                break;

            case 'changestatus':
                if ($id) {
                    $this->custom->toggleStatus($table, 's_status', 's_id', $id);
                }

                ci_redirect($backTo, 'refresh');
                break;
        }
    }

    /**
     * The store columns as posted from the back office.
     *
     * The employer's own form has the same method; this one takes the owner
     * from the picker instead of from the session, because an administrator is
     * editing somebody else's record.
     *
     * @return array<string, mixed>
     */
    private function storeRowFromPost(): array
    {
        return [
            'u_id'             => (int) $this->input->post('u_id'),
            's_name'           => strip_tags((string) $this->input->post('s_name')),
            's_number'         => strip_tags((string) $this->input->post('s_number')),
            's_province'       => (int) $this->input->post('s_province'),
            's_city'           => (int) $this->input->post('s_city'),
            's_address'        => strip_tags((string) $this->input->post('s_address')),
            's_location_label' => strip_tags((string) $this->input->post('s_location_label')),
            // Normalised and scheme-checked: both end up in an href.
            's_map_url'        => safeUrl($this->input->post('s_map_url')),
            's_pincode'        => strip_tags((string) $this->input->post('s_pincode')),
            // Digits only, PHONE_LENGTH of them - see normalisePhone().
            's_phone'          => normalisePhone($this->input->post('s_phone')),
            's_website'        => safeUrl($this->input->post('s_website')),
            // What a shift at this store starts with. Set unconditionally, the
            // same as on the shift form: an all-clear group posts nothing, and
            // clearing the last box has to clear the column.
            's_skills'             => implode(',', array_map('intval', (array) $this->input->post('s_skills'))),
            's_services'           => implode(',', array_map('intval', (array) $this->input->post('s_services'))),
            's_additional_details' => implode(',', array_map('intval', (array) $this->input->post('s_additional_details'))),
            's_status'         => $this->input->post('s_status') !== null ? (int) $this->input->post('s_status') : 1,
            'modified'         => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Why this employer may not be given another store, or '' if they may.
     *
     * A manager and an individual owner are both one location by definition -
     * their own store screen refuses to add a second, and the employer form
     * refuses to convert them while they hold several. The back office has to
     * agree, or an administrator could quietly create the state the rest of the
     * application is written to prevent.
     *
     * @param array<string, mixed> $ownerRow a `users` row
     */
    private function storeOwnerBlocker(array $ownerRow): string
    {
        if ((int) ($ownerRow['u_emp_role'] ?? 0) !== 2) {
            return '';
        }

        $held = $this->custom->get_where_count('store', ['u_id' => (int) $ownerRow['u_id']]);

        if ($held === 0) {
            return '';
        }

        return esc(employerKindName($ownerRow)) . ' accounts own a single location, and this one already has it. '
            . 'Make them an Owner (Multi Store) first if they are taking on another.';
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
                    // Same rules the public form applies - the e-mail is the
                    // login id, so it has to be a real address and unused.
                    $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|is_unique[users.u_userid]');
                    $this->form_validation->set_rules('u_phone', 'Mobile No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);
                    // The same name rule the public forms apply: letters, spaces
                    // and the punctuation real names contain. Without it the back
                    // office accepted digits and symbols the rest of the site
                    // rejects, on the very column those forms guard.
                    $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_message('is_unique', 'The %s is already taken');

                    $rowData = cleanArray($this->input->post());

                    // Without this the applicant is saved with no login id and
                    // can never sign in - see the employer form above.
                    $rowData['u_userid']   = $rowData['u_email'] ?? '';
                    $rowData['u_pass']     = $this->custom->hashPassword((string) $this->input->post('u_password'));
                    $rowData['created']    = date('Y-m-d H:i:s');
                    $rowData['modified']   = date('Y-m-d H:i:s');
                    $rowData['u_usertype'] = 2;
                    // A clear tick box posts nothing at all, so the answer is
                    // taken from whether the field arrived rather than from its
                    // value - otherwise "not done" would be indistinguishable
                    // from a field the form never had.
                    $rowData['u_agreement_done'] = $this->input->post('u_agreement_done') ? 1 : 0;
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
                    $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|is_unique[users.u_userid,u_id,' . (int) $id . ']');
                    $this->form_validation->set_rules('u_phone', 'Mobile No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);
                    // The same name rule the public forms apply: letters, spaces
                    // and the punctuation real names contain. Without it the back
                    // office accepted digits and symbols the rest of the site
                    // rejects, on the very column those forms guard.
                    $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
                    $this->form_validation->set_message('is_unique', 'The %s is already taken');

                    $rowData = cleanArray($this->input->post());

                    // Keep the login id on the address, the way the add form does.
                    $rowData['u_userid'] = $rowData['u_email'] ?? '';
                    $rowData['modified'] = date('Y-m-d H:i:s');
                    // Read from whether the box arrived, not its value: clearing
                    // it posts nothing, so leaving this out would make the tick
                    // impossible to undo.
                    $rowData['u_agreement_done'] = $this->input->post('u_agreement_done') ? 1 : 0;
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
        // Ordered by name, unlike the two above: this master is maintained by
        // hand and its ids come out in the order they happened to be added.
        $this->data['additional_details'] = $this->custom->get_where_order('additional_details', ['ad_status' => 1], 'ad_name', 'asc');

        switch ($action) {
            default:
                // Newest record first here, unlike the applicant-facing lists,
                // which run by shift date: the admin is looking at what has just
                // been posted rather than shopping for the next shift to work.
                // The date column still sorts chronologically when clicked.
                if ($this->input->get('filter') && $this->input->get('filter') === 'new') {
                    $jobs = $this->custom->get_where_order('post_job', ['p_approved' => 0], 'p_id', 'DESC');
                } else {
                    $jobs = $this->custom->get_data_order('post_job', 'p_id', 'DESC');
                }

                $this->data['jobs'] = $jobs;

                // Which shifts already have a booked applicant - they lose
                // their Edit and Delete buttons. One query for the whole set:
                // the list asked this per row, which was a COUNT per shift and
                // by far the most expensive thing on the screen.
                $booked = $this->custom->query(
                    'SELECT DISTINCT p_id FROM stu_saved_applied_jobs WHERE sj_is_approved = 1'
                );

                $this->data['bookedShiftIds'] = array_flip(array_column($booked ?: [], 'p_id'));

                $this->load->admin_view('postjobs/index', $this->data);
                break;

            case 'add':
                if ($this->input->post('savedata')) {
                    $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                    $rowData = cleanArray($this->input->post());

                    // The store is the only thing the form asks for, because a
                    // store belongs to one employer: naming it names them. The
                    // employer is read off the store here rather than posted,
                    // so the two can never arrive disagreeing.
                    $store  = $this->shiftStoreRow((int) $this->input->post('p_store_id'));
                    $u_data = $store ? $this->custom->get_where('users', ['u_id' => (int) $store->u_id]) : [];

                    if ($store && $u_data) {
                        $rowData['u_id'] = (int) $store->u_id;

                        // The shift's location is the store, falling back to
                        // the employer's login columns as before (B4).
                        $rowData['p_store_id'] = $store->s_id;
                        $rowData['p_province'] = $store->s_province ?: $u_data[0]->u_provice;
                        $rowData['p_city']     = $store->s_city ?: $u_data[0]->u_city;
                    }

                    $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
                    $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
                    // Nothing posted when every box is left clear, and the
                    // group is optional - so this has to be set either way,
                    // or the column would keep whatever it held before.
                    $rowData['p_additional_details'] = implode(',', (array) $this->input->post('p_additional_details'));
                    $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
                    $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);
                    // Neither box ticked is an answer, not an omission - it
                    // sends the shift-posted e-mail to the fallback address -
                    // so this is set whether or not anything was posted.
                    $rowData['p_email_to'] = implode(',', shiftEmailChoice($this->input->post('p_email_to')));

                    $rowData['created']  = date('Y-m-d H:i:s');
                    $rowData['modified'] = date('Y-m-d H:i:s');
                    $rowData['p_status'] = 1;

                    // The admin may hand the shift straight to somebody instead
                    // of waiting for applications. Checked against the same
                    // conditions the picker was built from, because the page may
                    // have been sitting open since before the account changed.
                    $applicantId = (int) $this->input->post('sj_applicant_id');
                    $applicant   = $applicantId > 0
                        ? $this->custom->get_where_row('users', ['u_id' => $applicantId, 'u_usertype' => 2, 'u_status' => 1])
                        : null;

                    // The shift is closed by `bookApplicant()` once the booking
                    // row is actually there, rather than here - see the note on
                    // ordering in that method.

                    // Neither belongs to post_job - they are the booking, which
                    // is written once the shift has an id.
                    unset($rowData['savedata'], $rowData['files'], $rowData['sj_applicant_id'], $rowData['sj_admin_comment']);

                    if (! $store || ! $u_data) {
                        // No store, so no employer to post the shift for - the
                        // dropdown is `required`, so this is a hand-edited form
                        // or a store deleted since the page was opened.
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Choose the store this shift is at.</div>');
                    } elseif ($applicantId > 0 && ! $applicant) {
                        // Chosen off a list that has since gone stale - the
                        // account is deactivated, or is not an applicant at all.
                        // Nothing is saved: a shift meant to be booked that came
                        // out unbooked is worse than one that was never created.
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">That applicant is no longer active, so the shift has not been saved.</div>');
                    } elseif (insertQry($table, $rowData, 'newjob')) {
                        $id = $this->db->insertID();

                        $uData['p_job_title'] = 'PAS-' . $id;

                        updateQry($table, $uData, ['p_id' => $id]);

                        if ($applicant) {
                            // Booking closes the shift, so it never goes live
                            // and there is nothing to announce - the booking
                            // e-mails that go instead are sent from here.
                            $this->bookApplicant((int) $id, $applicant, (string) $this->input->post('sj_admin_comment'));
                        } elseif ((int) ($rowData['p_approved'] ?? 0) === 1) {
                            // A shift saved straight to Live is live now, so it
                            // is announced now. Approving one later does the
                            // same from the edit branch; between them the
                            // e-mail follows the shift going live rather than
                            // which screen it happened on. Before this, a shift
                            // added as Live was never announced at all.
                            $this->sendShiftPostedEmail(
                                $rowData + ['p_id' => $id, 'p_job_title' => $uData['p_job_title']],
                                $u_data[0]
                            );
                        }

                        ci_redirect('sadmin/postjobs', 'refresh');
                    }

                    foreach ($rowData as $ky => $vl) {
                        $this->data[$ky] = $vl;
                    }
                } else {
                    getTableInfo($this->dbname, $table);

                    // A new shift tells both sides of the store unless the
                    // administrator says otherwise. The column's own default is
                    // neither, which is the right default for a row written by
                    // something other than this form, and the wrong one to open
                    // the form on - it would quietly send every new shift's
                    // announcement to the fallback address.
                    $this->data['p_email_to'] = implode(',', shiftEmailSides());
                }

                // Every store on the site, under the employer that owns it -
                // the only thing the form asks for.
                $this->data['shift_stores'] = $this->shiftStoreOptions();

                // Who the shift may be handed to straight away. Active accounts
                // only - a deactivated applicant is not working shifts.
                $this->data['applicants'] = $this->custom->get_where_order('users', ['u_usertype' => 2, 'u_status' => 1], 'u_lname, u_fname', 'asc');

                // Neither is a post_job column, so getTableInfo() knows nothing
                // about them and the view would have nothing to read.
                $this->data['sj_applicant_id']  = (string) $this->input->post('sj_applicant_id');
                $this->data['sj_admin_comment'] = (string) $this->input->post('sj_admin_comment');

                $this->load->admin_view('postjobs/add', $this->data);
                break;

            case 'edit':
                $shift_approved = $this->custom->get_where_row('post_job', ['p_id' => $id]) ?: [];

                // Who is on the shift, if anybody.
                $booking = $this->shiftBooking((int) $id);

                // A booked or closed shift used to be frozen outright. It is
                // now editable right up to the day it is worked, because the
                // booked applicant may drop out and somebody has to be able to
                // put another one on - see the booking card on the form. On the
                // day and afterwards it is history and stays frozen.
                $frozen = ($booking || (int) ($shift_approved['p_approved'] ?? 0) === 3)
                    && ! shiftIsUpcoming($shift_approved);

                if (! $frozen) {
                    if ($this->input->post('savedata')) {
                        $this->form_validation->set_rules('p_store_id', 'Store', 'required');

                        $rowData = cleanArray($this->input->post());

                        // Same as on add: the store is the only thing asked
                        // for, and the employer is read off it. Moving a shift
                        // to another chain's store moves the shift to that
                        // chain, which is the whole of what the form can do.
                        $store  = $this->shiftStoreRow((int) $this->input->post('p_store_id'));
                        $u_data = $store ? $this->custom->get_where('users', ['u_id' => (int) $store->u_id]) : [];

                        if ($store && $u_data) {
                            $rowData['u_id']       = (int) $store->u_id;
                            $rowData['p_store_id'] = $store->s_id;
                            $rowData['p_province'] = $store->s_province ?: $u_data[0]->u_provice;
                            $rowData['p_city']     = $store->s_city ?: $u_data[0]->u_city;
                        }

                        $rowData['p_skills']   = implode(',', (array) $this->input->post('p_skills'));
                        $rowData['p_services'] = implode(',', (array) $this->input->post('p_services'));
                        // Unticking the last box has to clear the column, which
                        // it would not if this were only set when something was
                        // posted - see the same line on add.
                        $rowData['p_additional_details'] = implode(',', (array) $this->input->post('p_additional_details'));
                        $rowData['p_jobinfo']  = $this->input->post('p_jobinfo');
                        $rowData['p_date_start'] = parseShiftDate($rowData['p_dates'] ?? null);
                        // Unticking both has to reach the column, same as on
                        // add - it is the choice that sends to the fallback.
                        $rowData['p_email_to'] = implode(',', shiftEmailChoice($this->input->post('p_email_to')));

                        $rowData['modified'] = date('Y-m-d H:i:s');
                        $rowData['p_status'] = 1;

                        // The booking, which the form may be changing: swapping
                        // one applicant for another, or taking the shift back
                        // off somebody so it goes on the board again. Checked
                        // the same way as on add - the page may have been open
                        // since before the account was deactivated.
                        $bookedId    = (int) ($booking['u_id'] ?? 0);
                        $applicantId = (int) $this->input->post('sj_applicant_id');
                        $applicant   = $applicantId > 0
                            ? $this->custom->get_where_row('users', ['u_id' => $applicantId, 'u_usertype' => 2, 'u_status' => 1])
                            : null;

                        // Not post_job columns - the booking lives in its own
                        // table and is written below.
                        unset($rowData['savedata'], $rowData['files'], $rowData['sj_applicant_id'], $rowData['sj_admin_comment']);

                        if (! $store || ! $u_data) {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Choose the store this shift is at.</div>');
                        } elseif ($applicantId > 0 && ! $applicant) {
                            // Chosen off a list that has since gone stale. As on
                            // add, nothing at all is saved rather than saving
                            // the shift and quietly losing the booking with it.
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">That applicant is no longer active, so nothing has been saved.</div>');
                        } elseif (updateQry($table, $rowData, ['p_id' => $id])) {
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
                                // Who this goes to is the form's "Send shift
                                // e-mail to" boxes, saved on the row above.
                                $this->sendShiftPostedEmail(
                                    $rowData + ['p_id' => $id, 'p_job_title' => $shift_approved['p_job_title']],
                                    $u_data[0]
                                );
                            }

                            // The booking itself, after the shift row and after
                            // the block above: that one rejects every
                            // application when the shift is made Inactive, and
                            // would undo a booking written before it.
                            $shiftClosed = (int) $rowData['p_approved'] === 3;

                            if ($booking && $applicantId !== $bookedId) {
                                // Swapped or taken off: the person who had the
                                // shift is told, whichever it was.
                                $this->cancelBooking($booking);
                            } elseif ($booking && (int) $rowData['p_approved'] === 2) {
                                // Same applicant, but the shift has just been
                                // made Inactive - the block above has already
                                // rejected their booking row, so they are no
                                // longer working it and have to hear so.
                                $this->cancelBooking($booking, false);
                            }

                            if ($applicant && $applicantId !== $bookedId) {
                                $this->bookApplicant((int) $id, $applicant, (string) $this->input->post('sj_admin_comment'));
                            } elseif ($booking && $applicantId === 0 && $shiftClosed) {
                                // Nobody on it now. It was closed because of the
                                // booking that has just gone, so it goes back on
                                // the board - unless the administrator picked a
                                // status themselves, which is left alone.
                                $this->db->table($table)
                                    ->where('p_id', $id)
                                    ->update(['p_approved' => 1, 'modified' => date('Y-m-d H:i:s')]);
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
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">This shift can no longer be modified - its date has arrived or gone by.</div>');
                    ci_redirect('sadmin/postjobs', 'refresh');
                }

                // The booking card, which is on this form for the same reason it
                // is on add - except that here it may already have somebody in
                // it. Only for a shift still ahead of us: putting an applicant
                // on one that has already been worked means nothing.
                $this->data['shift_upcoming'] = shiftIsUpcoming($shift_approved);
                $this->data['booking']        = $booking;

                if ($this->data['shift_upcoming']) {
                    $this->data['applicants'] = $this->custom->get_where_order('users', ['u_usertype' => 2, 'u_status' => 1], 'u_lname, u_fname', 'asc');

                    // The list is active accounts only, so a booked applicant
                    // since deactivated would not be in it - and a picker with
                    // no option for them reads as "nobody is booked", which on
                    // the next save is exactly what would happen. Put them in.
                    if ($booking && ! in_array((int) $booking['u_id'], array_map(static fn ($a) => (int) $a->u_id, $this->data['applicants']), true)) {
                        $bookedUser = $this->custom->get_where('users', ['u_id' => $booking['u_id']]);

                        if ($bookedUser) {
                            array_unshift($this->data['applicants'], $bookedUser[0]);
                        }
                    }

                    // Not post_job columns, so getTableInfo() knows nothing
                    // about them. The picker opens on whoever is booked, so
                    // leaving it alone leaves the booking alone.
                    $this->data['sj_applicant_id'] = $this->input->post('savedata')
                        ? (string) $this->input->post('sj_applicant_id')
                        : (string) ($booking['u_id'] ?? '');

                    $this->data['sj_admin_comment'] = (string) $this->input->post('sj_admin_comment');
                }

                // The store list, with this shift's own store in it even if it
                // has since been deactivated - the picker is what decides the
                // employer now, so a shift whose store is missing from it would
                // be handed to somebody else by the next save.
                $this->data['shift_stores'] = $this->shiftStoreOptions((int) ($this->data['p_store_id'] ?? 0));

                // Named on the form for a shift that predates stores, so
                // whoever is choosing one can tell whose shift they are moving.
                $owner = $this->custom->get_where_row('users', ['u_id' => (int) ($this->data['u_id'] ?? 0)]);

                $this->data['shift_owner_name'] = $owner
                    ? ($owner['u_comp_name'] !== '' ? $owner['u_comp_name'] : trim($owner['u_fname'] . ' ' . $owner['u_lname']))
                    : 'no employer on record';

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
                    $applicationslist = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates, pj.p_job_title, ap.u_fname AS applicant_fname, ap.u_lname AS applicant_lname from stu_saved_applied_jobs ssa join users u on u.u_id = ssa.agency_id join post_job pj on pj.p_id = ssa.p_id left join users ap on ap.u_id = ssa.u_id where 1 = 1 and ssa.sj_is_approved = 1 ');
                } else {
                    $applicationslist = $this->custom->query('select ssa.*, u.u_comp_name, pj.p_shift_time, pj.p_dates, pj.p_job_title, ap.u_fname AS applicant_fname, ap.u_lname AS applicant_lname from stu_saved_applied_jobs ssa join users u on u.u_id = ssa.agency_id join post_job pj on pj.p_id = ssa.p_id left join users ap on ap.u_id = ssa.u_id where 1 = 1 ');
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

                        $this->sendBookingEmails(
                            (int) $rowData['p_id'],
                            (int) $rowData['u_id'],
                            (string) $rowData['sj_admin_comment']
                        );

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
     * Send "your shift is live" to whoever the shift says to send it to.
     *
     * Called from the one moment it means anything: a shift becoming Live,
     * whether that is a new shift saved as Live or an existing one approved.
     * The applicant side of the site is not involved - the booking e-mails are
     * sent from `bookApplicant()` and are not affected by any of this.
     *
     * @param array<string, mixed>  $shift the `post_job` row, after saving
     * @param object|null           $owner the `users` row that owns the store
     */
    private function sendShiftPostedEmail(array $shift, ?object $owner): void
    {
        $storeId = (int) ($shift['p_store_id'] ?? 0);
        $manager = $storeId > 0 ? (storeManagers([$storeId])[$storeId] ?? null) : null;

        $audience = shiftPostedRecipients(
            $owner,
            $manager,
            $shift['p_email_to'] ?? '',
            (string) config('AppSettings')->shiftEmailFallback
        );

        if ($audience['missing'] !== []) {
            log_message('info', sprintf(
                'Shift-posted e-mail for %s could not reach: %s (no such account, no address, or opted out).',
                $shift['p_job_title'] ?? ('shift ' . ($shift['p_id'] ?? '?')),
                implode(', ', $audience['missing'])
            ));
        }

        if ($audience['fellBack']) {
            log_message('info', sprintf(
                'Shift-posted e-mail for %s went to the fallback address.',
                $shift['p_job_title'] ?? ('shift ' . ($shift['p_id'] ?? '?'))
            ));
        }

        // The greeting is the store's owner either way. The manager is sent the
        // same message rather than one addressed to them: this e-mail says a
        // shift is live, and it reads the same to both sides of a store.
        $subject = 'Your Shift Has Been Posted on ' . $this->data['settings'][0]->s_sitename . '!';
        $message = email_body('shift-posted', [
            'title'       => 'Your shift is now live',
            'name'        => trim(($owner->u_fname ?? '') . ' ' . ($owner->u_lname ?? '')),
            'shift_title' => $shift['p_job_title'] ?? '',
            'settings'    => $this->data['settings'],
        ]);

        foreach ($audience['to'] as $address) {
            if (send_email($address, $subject, $message)) {
                log_message('info', 'Shift-posted e-mail sent to ' . $address);
            } else {
                log_message('error', 'Shift-posted e-mail failed for ' . $address);
            }
        }
    }

    /**
     * The store a shift is being posted against, or null.
     *
     * Not filtered by status: the picker decides what may be chosen, and a
     * shift already standing against a store that has since been deactivated
     * must still be savable without being moved somewhere else. What matters
     * here is that the row exists, because its `u_id` becomes the shift's.
     */
    private function shiftStoreRow(int $storeId)
    {
        if ($storeId <= 0) {
            return null;
        }

        $rows = $this->custom->get_where('store', ['s_id' => $storeId]);

        return $rows[0] ?? null;
    }

    /**
     * Every store the shift forms may be posted against, in one list.
     *
     * The forms ask for a store and nothing else - a store belongs to one
     * employer, so choosing it chooses them - which means this list has to
     * carry the owner's name for the view to group by, and to tell two
     * branches of different chains that share a name apart.
     *
     * Active stores of active employers, plus `$keepStoreId` whatever its
     * state: that is the store an edit screen is already standing on, and a
     * picker with no option for it would hand the shift to whoever came first
     * on the next save.
     *
     * `managers` is whoever runs the store - the manager accounts pointed at it
     * by `u_store_id` - so the form can name the person the shift will actually
     * be arranged with, not only the company that owns the building. More than
     * one is possible and they are listed together; none is normal, and the
     * form simply says nothing.
     *
     * @return array<int, object>
     */
    private function shiftStoreOptions(int $keepStoreId = 0): array
    {
        return $this->custom->query(
            "SELECT s.s_id, s.s_name, s.s_number, s.u_id, u.u_comp_name,
                    (SELECT GROUP_CONCAT(TRIM(CONCAT(m.u_fname, ' ', m.u_lname))
                                         ORDER BY m.u_fname, m.u_lname SEPARATOR ', ')
                       FROM users m
                      WHERE m.u_store_id = s.s_id
                        AND m.u_usertype = 1
                        AND m.u_emp_role = 2
                        AND m.u_status   = 1) AS managers
               FROM store s
               JOIN users u ON u.u_id = s.u_id
              WHERE (s.s_status = 1 AND u.u_usertype = 1 AND u.u_status = 1)
                 OR s.s_id = ?
           ORDER BY u.u_comp_name ASC, s.s_name ASC",
            [$keepStoreId]
        ) ?: [];
    }

    /**
     * The approved booking on a shift, or null - the one applicant who is
     * actually working it.
     *
     * @return array<string, mixed>|null a `stu_saved_applied_jobs` row
     */
    private function shiftBooking(int $shiftId): ?array
    {
        if ($shiftId <= 0) {
            return null;
        }

        $row = $this->db->table('stu_saved_applied_jobs')
            ->where('p_id', $shiftId)
            ->where('sj_is_approved', 1)
            ->orderBy('sj_id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Take an applicant back off a shift they were booked on.
     *
     * Their row goes to rejected - the same code the applications screen uses
     * for everybody who did not get the shift - and they are told, because as
     * far as they know they are turning up to work it.
     *
     * @param array<string, mixed> $booking    a `stu_saved_applied_jobs` row
     * @param bool                 $rejectRow  false when the caller has already
     *                                         rejected the row, so only the
     *                                         e-mail is left to send
     */
    private function cancelBooking(array $booking, bool $rejectRow = true): void
    {
        if ($rejectRow) {
            // The comments on the row are left as they were: they are the
            // record of the booking that was made, and this is not a new one.
            $this->db->table('stu_saved_applied_jobs')
                ->where('sj_id', $booking['sj_id'])
                ->update(['sj_is_approved' => 2, 'modified' => date('Y-m-d H:i:s')]);
        }

        $this->sendCancellationEmail((int) $booking['p_id'], (int) $booking['u_id']);
    }

    /**
     * Tell an applicant the shift they were booked on is no longer theirs.
     *
     * Deliberately not one of the e-mails a user can opt out of: it is not
     * news about the service, it is the correction to a message we already
     * sent them saying they had the shift. Nobody may be opted out of that,
     * for the same reason as the password reset - see AppSettings::$emailTypes.
     */
    private function sendCancellationEmail(int $shiftId, int $applicantId): void
    {
        $applicant = $this->custom->get_where_row('users', ['u_id' => $applicantId]);
        $shift     = $this->custom->get_where_row('post_job', ['p_id' => $shiftId]);

        if (! $applicant || ! $shift) {
            log_message('error', 'Cancellation e-mail skipped: shift ' . $shiftId . ', applicant ' . $applicantId);

            return;
        }

        $employer = $this->custom->get_where_row('users', ['u_id' => $shift['u_id']]);

        $subject = 'Your shift booking has been cancelled : ' . $shift['p_job_title'];
        $message = email_body('booking-cancelled', [
            'title'    => 'Your shift booking has been cancelled',
            'name'     => $applicant['u_fname'] . ' ' . $applicant['u_lname'],
            'shift'    => $shift,
            'employer' => $employer,
            'store'    => shiftStore((object) $shift),
            'settings' => $this->data['settings'],
        ]);

        // The agency keeps a copy, as it does of the booking itself.
        if (send_email($applicant['u_email'], $subject, $message, getAgencyCopyEmail())) {
            log_message('info', 'Cancellation e-mail sent for shift ' . $shiftId . ' to applicant ' . $applicantId);
        } else {
            log_message('error', 'Cancellation e-mail failed for shift ' . $shiftId . ' to applicant ' . $applicantId);
        }
    }

    /**
     * Put an applicant on a shift, booked and confirmed.
     *
     * The same three things approving an application does - the booking row,
     * the shift closing behind it, and the pair of e-mails - for an applicant
     * the administrator placed on the shift themselves rather than one who
     * applied for it.
     *
     * @param array<string, mixed> $applicant a `users` row, already checked to
     *                                        be an active applicant
     */
    private function bookApplicant(int $shiftId, array $applicant, string $comment): void
    {
        $now   = date('Y-m-d H:i:s');
        $shift = $this->custom->get_where_row('post_job', ['p_id' => $shiftId]);

        if (! $shift) {
            return;
        }

        // This person may already have a row on this shift: on add they never
        // do, but the edit form books onto a shift that has been on the board
        // and may well have their application - or their earlier booking, if
        // they are being put back on after being taken off. Approving the row
        // they have, rather than writing a second one, keeps one row per
        // applicant per shift, which every count of "who is on this" assumes.
        $existing = $this->db->table('stu_saved_applied_jobs')
            ->where('p_id', $shiftId)
            ->where('u_id', (int) $applicant['u_id'])
            ->orderBy('sj_id', 'DESC')
            ->get()
            ->getRowArray();

        if ($existing) {
            $booked = $this->db->table('stu_saved_applied_jobs')
                ->where('sj_id', $existing['sj_id'])
                ->update([
                    'sj_status'        => 6,
                    'sj_is_approved'   => 1,
                    'sj_accept_date'   => $now,
                    'sj_admin_comment' => $comment,
                    'modified'         => $now,
                ]);
        } else {
            $booked = $this->custom->insert('stu_saved_applied_jobs', [
                'u_id'      => (int) $applicant['u_id'],
                'agency_id' => (int) $shift['u_id'],
                'p_id'      => $shiftId,
                // 6 is "accepted" - where an approved application ends up. Nobody
                // applied for this one, so it starts at the end.
                'sj_status'        => 6,
                'sj_is_approved'   => 1,
                'sj_applied_date'  => $now,
                'sj_accept_date'   => $now,
                'sj_admin_comment' => $comment,
                // Written out rather than left to the column defaults, which these
                // three do not have: they are NOT NULL with nothing to fall back
                // on, so a server running MySQL in strict mode - which the local
                // one is not, and a shared host usually is - refuses the whole
                // insert with "doesn't have a default value".
                'sj_applied_desc'      => '',
                'sj_resubmit_comments' => '',
                'sj_rejected_comments' => 0,
                'created'              => $now,
                'modified'             => $now,
            ]);
        }

        if (! $booked) {
            // The shift is saved and still open, which is the recoverable half:
            // the applicant can be booked from the Applications screen. Said
            // plainly, because the write above fails silently otherwise and
            // the page would report the shift as saved and nothing else.
            log_message('error', 'Booking from the shift form failed for shift ' . $shiftId . ', applicant ' . $applicant['u_id']);

            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">The shift was saved, but the applicant could not be booked onto it. The shift is still open - book them from the Applications screen.</div>');

            return;
        }

        // Everybody else who applied has lost it, the same as when a booking is
        // made from the applications screen. Nothing on add, where there are no
        // other applications to reject.
        $this->db->table('stu_saved_applied_jobs')
            ->where('p_id', $shiftId)
            ->where('u_id !=', (int) $applicant['u_id'])
            ->where('sj_is_approved !=', 2)
            ->update(['sj_is_approved' => 2, 'modified' => $now]);

        // Somebody is on it, so the shift is closed to everybody else - the
        // same thing the application screen does on approval. After the booking
        // row, never before it: a shift closed with nobody on it is invisible
        // to applicants and shows no booking to explain why.
        $this->db->table('post_job')
            ->where('p_id', $shiftId)
            ->update(['p_approved' => 3, 'modified' => $now]);

        $this->sendBookingEmails($shiftId, (int) $applicant['u_id'], $comment);
    }

    /**
     * The two halves of a booking notice - one to the applicant, one to the
     * employer - each with the agency's copy.
     *
     * Sent from approving an application and from booking an applicant on the
     * shift form. It sat inside applications/view; the second caller would have
     * been a second copy of it, so it lives here instead.
     *
     * Nothing is sent unless the booking row is really there and approved,
     * which is the guard the old code got from the join it read through.
     */
    private function sendBookingEmails(int $shiftId, int $applicantId, string $comment): void
    {
        $query = $this->db->table('stu_saved_applied_jobs s')
            ->select('u.*, s.agency_id')
            ->join('users u', 'u.u_id = s.u_id', 'inner')
            ->where('s.p_id', $shiftId)
            ->where('s.u_id', $applicantId)
            ->where('s.sj_is_approved', 1)
            ->get();

        if ($query->getNumRows() === 0) {
            return;
        }

        $user       = $query->getRow();
        $user_email = $user->u_email;
        $user_name  = $user->u_fname . ' ' . $user->u_lname;

        $shift_detail = $this->custom->get_where_row('post_job', ['p_id' => $shiftId]);

        $employer_detail = $this->custom->get_where_row('users', ['u_id' => $user->agency_id]);

        // The agency keeps a copy of both halves of the booking.
        $agency_copy = getAgencyCopyEmail();

        // Which building to turn up at. This used to be read off the employer's
        // login columns, which for a multi-store owner is their head office
        // rather than the branch the shift is at - so the applicant was sent to
        // the wrong address. `shiftStore()` returns the shift's own store,
        // falling back to those same columns only for a shift that has no store.
        $store_detail = shiftStore((object) $shift_detail);

        $applicant_email   = $user_email;
        $applicant_subject = 'Congratulations! You Have Been Approved for Shift ID : ' . $shift_detail['p_job_title'];
        $applicant_message = email_body('booking-applicant', [
            'title'            => 'You have been approved for a shift',
            'name'             => $user_name,
            'shift'            => $shift_detail,
            'employer'         => $employer_detail,
            'store'            => $store_detail,
            'approval_comment' => $comment,
            'settings'         => $this->data['settings'],
        ]);

        if (! userAllowsEmail($user, 'booking-applicant')) {
            // The agency's copy of this half still goes: the opt-out is the
            // applicant's, not the agency's.
            log_message('info', 'Booking e-mail withheld: applicant ' . $user->u_id . ' opted out.');

            if ($agency_copy) {
                send_email($agency_copy, $applicant_subject, $applicant_message);
            }
        } elseif (send_email($applicant_email, $applicant_subject, $applicant_message, $agency_copy)) {
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
            // A chain's head office needs telling which of their branches this
            // booking is for.
            'store'          => $store_detail,
            'settings'       => $this->data['settings'],
        ]);

        if (! userAllowsEmail($employer_detail, 'booking-employer')) {
            log_message('info', 'Booking e-mail withheld: employer ' . $employer_detail['u_id'] . ' opted out.');

            if ($agency_copy) {
                send_email($agency_copy, $employer_subject, $employer_message);
            }
        } elseif (send_email($employer_email, $employer_subject, $employer_message, $agency_copy)) {
            log_message('info', 'Email sent successfully!');
        } else {
            log_message('error', 'Failed to send email.');
        }
    }

    /**
     * A store's shift defaults, for the shift form to start from.
     *
     * Asked by `s_id`, the store the admin picked, which is the only thing that
     * form asks for: an employer is not a location, and their defaults belong
     * to each store rather than to the login. (It used to answer to a `u_id`
     * too, from when the form asked for the employer first and could fill in
     * for one that had a single store. That question is gone with the picker.)
     *
     * Returns what the store holds; the shift form decides what to do with it.
     * Nothing is written here - the shift keeps its own copy from the moment it
     * is saved.
     */
    public function ajax_getstoredefaults()
    {
        $this->setup();

        $storeId = (int) $this->input->post('s_id');
        $store   = $storeId > 0 ? $this->custom->get_where_row('store', ['s_id' => $storeId]) : null;

        $ids = static function ($list): array {
            // The columns hold "3,7,12"; '' has to come back as no ids at all
            // rather than as one empty one.
            return array_values(array_filter(array_map('intval', explode(',', (string) $list))));
        };

        return $this->response->setJSON([
            // 0 when the id matched no store at all.
            's_id'                 => $store ? (int) $store['s_id'] : 0,
            'p_skills'             => $store ? $ids($store['s_skills'] ?? '') : [],
            'p_services'           => $store ? $ids($store['s_services'] ?? '') : [],
            'p_additional_details' => $store ? $ids($store['s_additional_details'] ?? '') : [],
        ]);
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
