<?php

namespace App\Controllers;

/**
 * Public site: home, job search, signup/login, contact, resources.
 *
 * Ported from CI3 `application/controllers/Front.php`.
 */
class Front extends BaseController
{
    /**
     * Shared set-up that CI3 performed in the controller constructor.
     *
     * In CI4 the services are only wired up once `initController()` has run,
     * so this is called at the top of every action instead.
     */
    protected function setup(): void
    {
        $this->data['settings'] = $this->custom->getSettings();

        // What "Select User Type" offers, and what each choice means in the
        // database. The form is drawn from the same list register() validates
        // against, so an option can never exist that the save would refuse.
        $this->data['registerTypes'] = (array) $this->config->item('registerTypes');

        // User login status
        $this->isUserLoggedIn         = $this->session->userdata('isUserLoggedIn');
        $this->data['isUserLoggedIn'] = $this->isUserLoggedIn;

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '1') {
                $this->data['myaccountLink'] = base_url('employer/all_jobs');
                $this->data['logoutLink']    = base_url('employer/logout');
            } else {
                $this->data['myaccountLink'] = base_url('applicant/applied_jobs');
                $this->data['logoutLink']    = base_url('applicant/logout');
            }
        }

        $this->data['lang'] = $this->session->userdata('site_lang');

        $this->data['shift_for'] = $this->custom->get_where_order('shift_for', ['sf_status' => 1], 'sf_name', 'asc');

        $this->data['usertype']      = $this->config->item('usertype');
        $this->data['usersubtype']   = $this->config->item('usersubtype');
        $this->data['posttype']      = $this->config->item('posttype');
        $this->data['qualification'] = $this->config->item('qualification');
    }

    public function index()
    {
        $this->setup();

        // Soonest shift first - ordered on the real date column, not the text one.
        $this->data['jobs'] = $this->custom->get_where_order(
            'post_job',
            ['p_status' => 1, 'p_approved' => 1],
            shiftDateOrderBy(),
            '',
            false
        );

        $this->data['agencylist'] = $this->custom->get_where('users', ['u_usertype' => 1, 'u_status' => 1]);

        // The carousel under "What Makes Us Stand Out", oldest first so the
        // running order matches the back-office list.
        $this->data['testimonials'] = $this->custom->get_where_order('testimonial', ['t_status' => 1], 't_id', 'asc');

        $this->load->front_view('index', $this->data, 1);
    }

    public function switchLang($language = '')
    {
        $this->setup();

        $language = ($language !== '') ? $language : 'english';
        $this->session->set_userdata('site_lang', $language);

        ci_redirect($this->request->getServer('HTTP_REFERER') ?? base_url());
    }

    /**
     * The multi-store owners a registering manager can join, A-Z.
     *
     * Only approved ones: joining a group that has not been verified yet
     * would attach a store to an account nobody has checked.
     *
     * Every approved group is listed, including one that has added no store
     * yet. Leaving those out would empty the dropdown of a site whose groups
     * are still being set up, and the manager would be told nothing at all;
     * this way they pick a group and the store list explains where it stands.
     */
    private function pharmacyGroups(): array
    {
        // Shared with the back-office employer form, which offers the same list.
        return pharmacyGroups();
    }

    public function signup()
    {
        $this->setup();

        $this->data['province']        = $this->custom->get_where('province', ['p_status' => 1]);
        $this->data['pharmacy_groups'] = $this->pharmacyGroups();
        $this->session->set_userdata('site_lang', 'english');

        $this->load->front_view('signup', $this->data);
    }

    public function forgot_password()
    {
        $this->setup();

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '1') {
                ci_redirect('employer/all_jobs', 'refresh');
            } elseif ($this->session->userdata('userType') == '2') {
                ci_redirect('applicant/applied_jobs', 'refresh');
            }
        }

        // If login request submitted
        if ($this->input->post('forgotSubmit')) {
            $this->form_validation->set_rules('username', 'User ID', 'required');

            if ($this->form_validation->run() === true) {
                $email = $this->input->post('username');

                $user = $this->custom->get_user_by_email($email);

                if ($user) {
                    $token  = bin2hex(random_bytes(20));
                    $expiry = date('Y-m-d H:i:s', strtotime('+10 min'));

                    // Save token and expiry
                    $this->custom->update_reset_token($email, $token, $expiry);

                    $reset_link = base_url("front/reset_password/{$token}");

                    $body = email_body('reset-password', [
                        'title'           => 'Reset your password',
                        'reset_link'      => $reset_link,
                        'expires_minutes' => 10,
                        'settings'        => $this->data['settings'],
                    ]);

                    if (send_email($email, 'Reset Password', $body)) {
                        log_message('info', 'Email sent successfully!');
                    } else {
                        log_message('error', 'Failed to send email.');
                    }

                    $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Password reset link sent to your email.</div>');
                    ci_redirect('front/forgot_password');
                } else {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">User not found</div>');
                }
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">' . validation_errors() . '</div>');
            }
        }

        $this->load->front_view('forgot', $this->data);
    }

    public function reset_password($token = '')
    {
        $this->setup();

        $user = $this->custom->validate_reset_token($token);

        if ($this->input->post('resetSubmit')) {
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run() === true) {
                $token        = $this->input->post('token');
                $new_password = $this->input->post('password');

                $user = $this->custom->validate_reset_token($token);

                if ($user) {
                    $hashed_password = $this->custom->hashPassword($new_password);

                    $this->db->table('users')
                        ->where('u_userid', $user->u_userid)
                        ->update(['u_pass' => $hashed_password, 'reset_token' => null, 'token_expiry' => null]);

                    $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Password updated successfully.</div>');
                    ci_redirect('front/login');
                } else {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid or expired token.</div>');
                    ci_redirect('front/forgot_password');
                }
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">' . validation_errors() . '</div>');
            }
        }

        if ($user) {
            $this->data['token'] = $token;
            $this->load->front_view('resetpassword', $this->data);
        } else {
            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid or expired token.</div>');
            ci_redirect('front/forgot_password');
        }
    }

    public function resources()
    {
        $this->setup();

        // Names the banner heading, the browser tab and the breadcrumb. Without
        // it all three were blank and the page announced itself with a second
        // heading in the body instead.
        $this->data['pageTitle'] = 'Resources';

        $this->data['headermenu_parent_only'] = $this->custom->query("select * from headermenu where m_parentid = 0 AND (m_link != '')  AND m_status = 1 order by m_name asc; ");
        $this->data['headermenu_parent']      = $this->custom->query("select * from headermenu where m_parentid = 0 AND (m_link IS NULL OR m_link = '')  AND  m_status = 1 order by m_name asc; ");

        $this->session->set_userdata('site_lang', 'english');

        $this->load->front_view('resources', $this->data);
    }

    public function job_detail()
    {
        $this->setup();

        $this->data['pageTitle'] = 'Job Details';

        $id  = $this->uri->segment(3);
        $uid = $this->session->userdata('userId');
        $this->session->set_userdata('site_lang', 'english');

        $this->data['id'] = $id;

        // `u_website` comes along so the page can fall back to the employer's
        // site when the store has none of its own.
        $this->data['jobdetail'] = $this->custom->query(
            'Select pj.*,pr.p_name, cit.c_name ,u.u_comp_name,u.u_company_logo,u.u_licence_no,u.u_website '
            . 'from post_job pj, province pr, city cit, users u '
            . 'where pj.p_province = pr.p_id and p_city = cit.c_id and pj.u_id = u.u_id and pj.p_id = ?',
            [$id]
        );

        $this->data['appliedjob'] = $this->custom->get_where('stu_saved_applied_jobs', ['p_id' => $id, 'u_id' => $uid]);
        // "Related shifts" in the sidebar - soonest first, as elsewhere. The
        // shift being read is left out, and the list is capped: it used to
        // print every approved shift on the site down the side of the page.
        $this->data['relatedjobs'] = $this->custom->query(
            'Select pj.*,pr.p_name, cit.c_name ,u.u_comp_name,u.u_company_logo,u.u_licence_no '
            . 'from post_job pj, province pr, city cit, users u '
            . 'where pj.p_province= pr.p_id and p_city=cit.c_id and  pj.u_id=u.u_id and pj.p_approved=1 '
            . 'and pj.p_id != ? '
            . 'ORDER BY ' . shiftDateOrderBy('pj') . ' LIMIT 6',
            [$id]
        );

        $this->data['applied'] = 0;
        if (count($this->data['appliedjob']) > 0) {
            $this->data['applied'] = 1;
        }

        // The store (location) the shift is at - for a pre-B4 shift this
        // falls back to the owner's login columns, so the address shown never
        // changes retrospectively.
        $this->data['shift_store'] = ! empty($this->data['jobdetail'])
            ? shiftStore($this->data['jobdetail'][0])
            : null;

        // The store's phone is only for the applicant actually booked on this
        // shift - never for the public page.
        $this->data['is_booked_viewer'] = false;

        foreach ($this->data['appliedjob'] as $application) {
            if ($application->sj_is_approved == 1) {
                $this->data['is_booked_viewer'] = true;
                break;
            }
        }

        $jobId = $this->data['jobdetail'][0]->p_id ?? $id;

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '2') {
                $this->data['applylink'] = base_url('applicant/apply_job/' . $jobId);
                $this->data['savelink']  = base_url('applicant/save_job/' . $jobId);
            } else {
                $this->data['applylink'] = base_url('employer/all_jobs');
                $this->data['savelink']  = base_url('employer/all_jobs');
            }
        } else {
            $this->data['applylink'] = base_url('applicant/apply_job/' . $jobId);
            $this->data['savelink']  = base_url('applicant/save_job/' . $jobId);
        }

        $this->data['captcha'] = getCaptcha();

        $this->load->front_view('job_detail', $this->data);
    }

    public function search_result()
    {
        $this->setup();

        $searchkey                 = $this->input->get('searchkey');
        $this->data['searchkey']   = $searchkey;

        $country                   = $this->input->get('search_country');
        $this->data['country']     = $country;

        $category                  = $this->input->get('search_category');
        $this->data['category']    = $category;

        // Three query-string values that used to be pasted straight into the
        // SQL below - the one place in the application that concatenated a
        // request value into a statement rather than binding it.
        //
        // It was not exploitable as it stands: the query names `country` and
        // `users.u_a_comp_name`, neither of which survived the CI3 schema, so
        // the statement fails before any injected clause could matter and this
        // page has been a 500 for as long as that has been true. Bound anyway,
        // because the reason it is safe today is an accident of the schema, and
        // whoever revives this page should not have to notice that. The shape
        // of the query is unchanged.
        //
        // Nothing on the site links here; auto-routing publishes it regardless.
        $searchqry = '';
        $binds     = [];

        if (! empty($searchkey)) {
            $searchqry .= ' and pj.p_job_title like ? ';
            $binds[] = '%' . $searchkey . '%';
        }

        if (! empty($country)) {
            $searchqry .= ' and pj.p_country = ? ';
            $binds[] = $country;
        }

        if (! empty($category)) {
            $searchqry .= ' and pj.js_id = ? ';
            $binds[] = $category;
        }

        $this->data['searchlist'] = $this->custom->query("Select pj.*,c.cname,u.u_a_comp_name,u.u_a_company_logo,u.u_a_ra_id from post_job pj, country c, users u where pj.p_country=c.id and pj.u_id=u.u_id {$searchqry} ", $binds);

        $this->data['countries']  = $this->custom->get_data('country');
        $this->data['categories'] = $this->custom->get_data('jobspecialization');

        $total_post_job = $this->custom->query('SELECT js_id,p_id,count(js_id) as tot FROM `post_job` group by js_id;');

        $postarr = [];

        if ($total_post_job) {
            foreach ($total_post_job as $pj) {
                $postarr[$pj->js_id] = $pj->tot;
            }
        }

        $this->data['postarr'] = $postarr;

        $this->load->front_view('search_result', $this->data);
    }

    public function apply_job()
    {
        $this->setup();

        $id    = $this->uri->segment(3);
        $uid   = $this->session->userdata('userId');
        $this->session->set_userdata('site_lang', 'english');
        $table = 'stu_saved_applied_jobs';

        if ($this->input->post('applyjob')) {
            $jobdet = $this->custom->get_where('post_job', ['p_id' => $id]);

            $this->form_validation->set_rules('sj_applied_desc', 'Your Self', 'required');

            $rowData = cleanArray($this->input->post());

            $rowData['p_id']            = $id;
            $rowData['u_id']            = $uid;
            $rowData['agency_id']       = $jobdet[0]->u_id;
            $rowData['sj_status']       = 1;
            $rowData['sj_applied_date'] = date('Y-m-d H:i:s');
            unset($rowData['applyjob'], $rowData['_wysihtml5_mode']);

            if (insertQry($table, $rowData)) {
                ci_redirect('front/apply_job/' . $id);
            } else {
                foreach ($rowData as $ky => $vl) {
                    $this->data[$ky] = $vl;
                }
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

        $this->load->front_view('apply_job', $this->data);
    }

    public function save_job()
    {
        $this->setup();

        $id    = $this->uri->segment(3);
        $uid   = $this->session->userdata('userId');
        $this->session->set_userdata('site_lang', 'english');
        $table = 'stu_saved_applied_jobs';

        $rowData = [
            'p_id'          => $id,
            'u_id'          => $uid,
            'sj_status'     => 0,
            'sj_saved_date' => date('Y-m-d H:i:s'),
        ];

        if (insertQry($table, $rowData)) {
            ci_redirect('front/job_detail/' . $id);
        }
    }

    public function login()
    {
        $this->setup();

        $this->data['pageTitle'] = 'Join Us';

        $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);
        $this->isUserLoggedIn   = $this->session->userdata('isUserLoggedIn');

        if (! empty($this->isUserLoggedIn)) {
            if ($this->session->userdata('userType') == '1') {
                ci_redirect('employer/all_jobs', 'refresh');
            } elseif ($this->session->userdata('userType') == '2') {
                ci_redirect('applicant/applied_jobs', 'refresh');
            }
        }

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
                    // Look the account up by its login id, then check the
                    // password. It used to be matched inside the query as an MD5
                    // digest, which no modern hash allows.
                    $checkLogin = $this->custom->findUserForLogin((string) $this->input->post('username'));

                    // An account that has just been guessed at eight times over
                    // stops answering passwords for a quarter of an hour. The
                    // check comes before the password so that a locked account
                    // cannot be used as an oracle for which guess was right.
                    $lockedFor = $checkLogin ? $this->custom->loginLockRemaining($checkLogin) : 0;

                    if ($lockedFor > 0) {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Too many failed sign-in attempts. Try again in ' . (int) ceil($lockedFor / 60) . ' minute(s), or reset your password.</div>');
                    } elseif ($checkLogin && $this->custom->passwordMatches((string) $this->input->post('password'), $checkLogin)) {
                        // The right password ends the run of failures, whether
                        // or not the account is active enough to be let in.
                        $this->custom->clearLoginAttempts($checkLogin['u_id']);

                        if ($checkLogin['u_status'] == 1) {
                            // A new session id the moment the account is
                            // recognised. Without it the signed-in session
                            // carries the id the browser arrived with, and
                            // anybody who set that id beforehand - a link with
                            // a session in it, a shared machine - is signed in
                            // as this user too. `last_url` is read after this
                            // because regenerating keeps the data.
                            $this->session->sess_regenerate(true);

                            $this->session->set_userdata('isUserLoggedIn', true);
                            $this->session->set_userdata('userId', $checkLogin['u_id']);
                            $this->session->set_userdata('userType', $checkLogin['u_usertype']);

                            if ($checkLogin['u_usertype'] == 1) {
                                ci_redirect('employer/all_jobs', 'refresh');
                            }

                            if ($checkLogin['u_usertype'] == 2) {
                                // Return the applicant to the page they came from, if any.
                                $last_url = $this->session->userdata('last_url');

                                $this->session->unset_userdata('last_url');

                                ci_redirect($last_url ?: 'applicant/applied_jobs', 'refresh');
                            }
                        } else {
                            $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Your account is not active, contact administrator.</div>');
                        }
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

        $this->session->set_userdata('site_lang', 'english');

        $this->data['pharmacy_groups'] = $this->pharmacyGroups();

        $this->load->front_view('signup', $this->data);
    }

    public function register()
    {
        $this->setup();

        $this->data['province']        = $this->custom->get_where('province', ['p_status' => 1]);
        $this->data['pharmacy_groups'] = $this->pharmacyGroups();

        $userData = [];

        if ($this->input->post('signupSubmit')) {
            $user_captcha   = $this->input->post('captcha');
            $stored_captcha = $this->session->userdata('captcha_code');

            $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|callback_email_check|is_unique[users.u_userid]');
            $this->form_validation->set_rules('password', 'password', 'required');
            $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
            $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
            // The form marks this required and the browser refuses anything but
            // PHONE_LENGTH digits; this is the same rule where it counts.
            $this->form_validation->set_rules('u_phone', 'Mobile No.', ['required', 'regex_match[' . PHONE_PATTERN . ']'], ['regex_match' => 'The {field} must be ' . PHONE_LENGTH . ' digits, numbers only.']);

            $this->form_validation->set_message('is_unique', 'The %s is already taken');
            $this->form_validation->set_rules('conf_password', 'confirm password', 'required|matches[password]');

            // One dropdown, three kinds of account, and what each one means in
            // the database is `registerTypes` in config - the same list the
            // dropdown is drawn from, so the two cannot offer different things.
            $regTypes = (array) $this->config->item('registerTypes');

            $this->form_validation->set_rules('reg_type', 'User Type', 'required|in_list[' . implode(',', array_keys($regTypes)) . ']');

            $regType = (int) $this->input->post('reg_type');
            $chosen  = $regTypes[$regType] ?? null;

            // An employer kind carries an `empRole`; an applicant does not.
            $isOwner = $chosen !== null && $chosen['empRole'] !== null;

            // How the choice maps onto the columns lives in one place, shared
            // with the back-office employer form, so the two cannot drift.
            $shape = employerKindRole($isOwner ? $chosen['empRole'] : 0);

            $userType = $chosen !== null ? (int) $chosen['userType'] : 2;
            $empRole  = $isOwner ? $shape['role'] : 0;

            // A manager runs a store for somebody, so the group is required.
            if ($shape['needsParent']) {
                $this->form_validation->set_rules('u_parent_id', 'Corporate Group', 'required');
            }

            // A manager runs one of the group's stores, so they say which
            // rather than describing one of their own.
            $picksStore = $shape['picksStore'];

            if ($picksStore) {
                $this->form_validation->set_rules('u_store_id', 'Store', 'required');
            }

            // An owner is asked for the name their account is known by - the
            // group's for an owner, the store's for one location, the same
            // column either way (see the label the script in front/footer.php
            // swaps in). It has to be theirs alone, or every screen that lists
            // employers shows two companies under one name; the back-office
            // employer form refuses a taken name from the same helper. A
            // manager is asked for no name at all - the store they picked
            // already has one - so neither rule applies to them.
            if ($isOwner && ! $picksStore) {
                $compNameLabel = $shape['role'] === 1 ? 'Corporate Group Name' : 'Store Name';

                $this->form_validation->set_rules('u_comp_name', $compNameLabel, [
                    'required',
                    employerNameRule($compNameLabel),
                ]);
            }

            // A multi-store owner is never asked for a location: their licence
            // and address belong to each store they add later. Neither is a
            // manager - the store they picked already has one.
            $asksForLocation = $shape['asksForLocation'] && ! $picksStore;

            // Only a manager belongs to a group, and the id is checked against
            // the list rather than trusted, so a posted one cannot attach the
            // account to an arbitrary user.
            $parentId = 0;

            if ($shape['needsParent'] && $this->input->post('u_parent_id')) {
                $parent = $this->custom->get_where('users', [
                    'u_id'       => (int) $this->input->post('u_parent_id'),
                    'u_usertype' => 1,
                    'u_emp_role' => 1,
                    'u_status'   => 1,
                ]);

                $parentId = $parent ? (int) $parent[0]->u_id : 0;
            }

            // The store is checked against the group that was just proved,
            // never against the posted one: that is what stops a hand-edited
            // form attaching a manager to another group's location. A group of
            // 0 can never be used as an ownership key, because the lookup is
            // skipped entirely.
            $store   = null;
            $storeId = 0;

            if ($picksStore && $parentId > 0 && $this->input->post('u_store_id')) {
                $stores = $this->custom->get_where('store', [
                    's_id'     => (int) $this->input->post('u_store_id'),
                    'u_id'     => $parentId,
                    's_status' => 1,
                ]);

                $store   = $stores[0] ?? null;
                $storeId = $store ? (int) $store->s_id : 0;
            }

            $userData = [
                'u_userid'     => strip_tags((string) $this->input->post('u_email')),
                'u_pass'       => $this->custom->hashPassword((string) $this->input->post('password')),
                // Digits only, PHONE_LENGTH of them - see normalisePhone(). The
                // store row below copies this, so both land the same.
                'u_phone'      => normalisePhone($this->input->post('u_phone')),
                'u_email'      => strip_tags((string) $this->input->post('u_email')),
                'u_comp_name'  => $isOwner ? strip_tags((string) $this->input->post('u_comp_name')) : '',
                // Optional, and only meaningful for an employer. Normalised and
                // scheme-checked here rather than stored as typed, because it
                // is rendered as a link.
                'u_website'    => $isOwner ? safeUrl($this->input->post('u_website')) : '',

                'u_fname'      => strip_tags((string) $this->input->post('u_fname')),
                'u_lname'      => strip_tags((string) $this->input->post('u_lname')),
                // The province and city columns are integers, so an unasked
                // one is 0 rather than an empty string.
                'u_l_provice'  => $asksForLocation ? (int) $this->input->post('u_l_provice') : 0,
                'u_licence_no' => $asksForLocation ? strip_tags((string) $this->input->post('u_licence_no')) : '',

                'u_provice'    => $asksForLocation ? (int) $this->input->post('u_provice') : 0,
                'u_city'       => $asksForLocation ? (int) $this->input->post('u_city') : 0,
                'u_address1'   => $asksForLocation ? strip_tags((string) $this->input->post('u_address1')) : '',
                'u_pincode'    => $asksForLocation ? strip_tags((string) $this->input->post('u_pincode')) : '',

                'u_usertype'   => $userType,
                'u_usersubtype' => $isOwner ? 0 : $this->input->post('u_usersubtype'),
                'u_emp_role'   => $empRole,
                'u_parent_id'  => $parentId,
                'u_store_id'   => $storeId,

                'u_ipaddress'  => $this->input->ip_address(),

                // Public sign-up never recorded this, so anyone who registered
                // themselves was invisible to "new applicants this month".
                'created'      => date('Y-m-d H:i:s'),
            ];

            // A manager types none of the store columns, but a dozen screens,
            // exports and e-mails read them straight off the login rather than
            // through the store. The chosen store is copied onto the account -
            // see `storeSnapshotForManager()`, which the back-office employer
            // form applies too so that both produce the same record.
            if ($store !== null) {
                $userData = array_merge($userData, storeSnapshotForManager($store));
            }

            if ($this->form_validation->run() === true) {
                if ($user_captcha != $stored_captcha) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid CAPTCHA. Please try again.</div>');
                } elseif ($regType === 'manager' && $parentId === 0) {
                    // `required` only proves something was posted; this catches
                    // an id that is not one of the offered corporate groups.
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose the corporate group you belong to.</div>');
                } elseif ($picksStore && $storeId === 0) {
                    // One message for all four ways this can fail: nothing
                    // chosen, a store of another group, a deactivated one, or a
                    // group that has no stores at all.
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Please choose one of your corporate group\'s stores.</div>');
                } elseif ($picksStore && storeManagerIds([$storeId]) !== []) {
                    // One store, one manager. The picker shows a taken store
                    // greyed out, so what reaches here is a page that was open
                    // before somebody else registered, a hand-edited form, or
                    // two people claiming the same branch minutes apart.
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">This store already has a manager registered. Please choose another store, or contact your corporate group if this is not right.</div>');
                } else {
                    $insert = $this->custom->insert('users', $userData);

                    if ($insert) {
                        // A single-location account's own store, built from the
                        // store fields on the form. Only an individual owner
                        // now: a multi-store owner is asked for no address and
                        // adds theirs afterwards from Employer > My Stores, and
                        // a manager joins one the group already added rather
                        // than bringing a second row for the same address.
                        if ($isOwner && $shape['ownsStore'] && ! $picksStore) {
                            $this->custom->insert('store', [
                                'u_id'       => $insert,
                                's_name'     => $userData['u_comp_name'] !== '' ? $userData['u_comp_name'] : trim($userData['u_fname'] . ' ' . $userData['u_lname']),
                                's_number'   => $userData['u_licence_no'],
                                's_province' => (int) $userData['u_provice'],
                                's_city'     => (int) $userData['u_city'],
                                's_address'  => $userData['u_address1'],
                                's_pincode'  => $userData['u_pincode'],
                                's_phone'    => $userData['u_phone'],
                                's_status'   => 1,
                            ]);
                        }

                        $email = $userData['u_email'];

                        $this->data['name'] = $userData['u_fname'] . ' ' . $userData['u_lname'];

                        $subject      = 'Welcome to ' . $this->data['settings'][0]->s_sitename . '! Your Account is Pending';
                        $message_user = email_body('welcome', [
                            'title'    => 'Welcome to ' . $this->data['settings'][0]->s_sitename . '!',
                            'name'     => $this->data['name'],
                            'settings' => $this->data['settings'],
                        ]);

                        // Guarded like every optional e-mail, though at
                        // registration the block list is necessarily empty -
                        // the guard is here so every send site reads the same.
                        if (! userAllowsEmail($insert, 'welcome')) {
                            log_message('info', 'Welcome e-mail withheld: user opted out.');
                        } elseif (send_email($email, $subject, $message_user)) {
                            log_message('info', 'Email sent successfully!');
                        } else {
                            log_message('error', 'Failed to send email.');
                        }

                        $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Registration successful. Account will be active after verification.</div>');
                        ci_redirect('front/login', 'refresh');
                    } else {
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Some problems occured, please try again.</div>');
                    }
                }
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">' . validation_errors() . '</div>');
            }

            unset($userData['password']);

            foreach ($userData as $ky => $vl) {
                $this->data[$ky] = $vl;
            }

            // So the user-type dropdown comes back on the choice that was made,
            // and the form redraws the right set of fields with it.
            $this->data['reg_type'] = $regType;
        }

        $this->data['show_registration'] = true;

        $this->load->front_view('signup', $this->data);
    }

    public function logout()
    {
        $this->setup();

        $this->session->unset_userdata('isUserLoggedIn');
        $this->session->unset_userdata('userId');
        $this->session->sess_destroy();

        ci_redirect('front/login');
    }

    /**
     * Existing e-mail check during validation (CI3 `callback_email_check`).
     */
    public function email_check($str)
    {
        $con = [
            'returnType' => 'count',
            'conditions' => [
                'u_email' => $str,
            ],
        ];

        $checkEmail = $this->custom->getRows('users', $con);

        if ($checkEmail > 0) {
            $this->form_validation->set_message('email_check', 'The given email already exists.');

            return false;
        }

        return true;
    }

    public function verify_request()
    {
        $this->setup();

        if ($this->input->post('submitverify')) {
            $this->form_validation->set_rules('v_name', 'Student Name', 'required');
            $this->form_validation->set_rules('v_contactno', 'Student Contact No.', 'required');

            $rowData = cleanArray($this->input->post());

            $rowData['modified'] = date('Y-m-d H:i:s');
            unset($rowData['submitverify'], $rowData['base']);

            if (insertQry('verify', $rowData)) {
                $v_id = $this->db->insertID();

                /* ra license upload */
                $upload_param = [
                    'filename' => 'v_file',
                    'path'     => 'verify',
                    'types'    => 'jpeg|jpg|png|pdf|doc|docx',
                    'size'     => '1024',
                    'width'    => '0',
                    'height'   => '0',
                    'table'    => 'verify',
                    'field'    => 'v_file',
                    'pkfield'  => 'v_id',
                    'pkval'    => $v_id,
                ];

                $filestaus = fileupload($upload_param);

                if ($filestaus['error'] == 1) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">File format not supported.</div>');
                } else {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-success">File has been uploaded.</div>');
                } ?>
                <script>
                    var base = "<?php echo base_url(); ?>";
                    alert("Your request has been submitted. Team will contact you soon.");
                    window.location.replace(base);
                </script>
                <?php
            }
        }
    }

    public function ajax_verify_request()
    {
        $this->setup();

        $stu_name      = $this->input->post('stu_name');
        $stu_contactno = $this->input->post('stu_contactno');
        $created       = date('Y-m-d H:i:s');
        $v_status      = 0;

        $verify = $this->custom->get_where('verify', ['v_contactno' => $stu_contactno]);

        if (count($verify) === 0) {
            $inarr = [
                'v_name'      => $stu_name,
                'v_contactno' => $stu_contactno,
                'v_status'    => $v_status,
                'created'     => $created,
                'modified'    => $created,
            ];

            $insert = $this->custom->insert('verify', $inarr);

            echo $insert ? 1 : 0;
        } else {
            echo 2;
        }
    }

    public function ajax_save_review()
    {
        $this->setup();

        $stu_review = $this->input->post('stu_review');
        $pjid       = $this->input->post('pjid');
        $created    = date('Y-m-d H:i:s');
        $r_status   = 1;

        if (! empty($this->session->userdata('userId'))) {
            $uid = $this->session->userdata('userId');

            $review = $this->custom->get_where('reviews', ['u_id' => $uid, 'pj_id' => $pjid]);

            if (count($review) === 0) {
                $inarr = [
                    'r_review' => $stu_review,
                    'pj_id'    => $pjid,
                    'u_id'     => $uid,
                    'r_status' => $r_status,
                    'created'  => $created,
                    'modified' => $created,
                ];

                $insert = $this->custom->insert('reviews', $inarr);

                echo $insert ? 1 : 0;
            } else {
                echo 2;
            }
        } else {
            echo 3;
        }
    }

    public function ajax_reviews()
    {
        $this->setup();

        $pid = $this->input->post('pid');

        $html = '';

        $reviews = $this->custom->get_where('reviews', ['pj_id' => $pid, 'r_status' => 1]);

        if (count($reviews) > 0) {
            $i = 0;

            foreach ($reviews as $review) {
                if ($i === 0) {
                    $html .= $pid . '[s]<tr><td style="">' . $review->r_review . '</td></tr>';
                } else {
                    $html .= '<tr><td>' . $review->r_review . '</td></tr>';
                }
                $i++;
            }
        } else {
            $html .= $pid . '[s]<tr><td>There are no Reviews</td></tr>';
        }

        echo $html;
    }

    public function ajax_getintouch()
    {
        $this->setup();

        $name    = $this->input->post('name');
        $email   = $this->input->post('email');
        $msg     = $this->input->post('msg');
        $pj_id   = $this->input->post('pj_id');
        $captcha = $this->input->post('captcha');
        $created = date('Y-m-d H:i:s');

        // First, delete old captchas
        $expiration = time() - 7200; // Two hour limit
        $this->db->table('captcha')->where('captcha_time <', $expiration)->delete();

        // Then see if a captcha exists:
        $sql   = 'SELECT COUNT(*) AS count FROM captcha WHERE word = ? AND ip_address = ? AND captcha_time > ?';
        $binds = [$captcha, $this->input->ip_address(), $expiration];
        $row   = $this->db->query($sql, $binds)->getRow();

        if ($row->count == 0) {
            echo 3;

            return;
        }

        $verify = $this->custom->get_where('getintouch', ['g_email' => $email]);

        if (count($verify) === 0) {
            $inarr = [
                'g_name'    => $name,
                'g_email'   => $email,
                'g_message' => $msg,
                'pj_id'     => $pj_id,
                'g_status'  => 0,
                'created'   => $created,
                'modified'  => $created,
            ];

            $insert = $this->custom->insert('getintouch', $inarr);

            echo $insert ? 1 : 0;
        } else {
            echo 2;
        }
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
     * The stores of one corporate group, for the manager registration picker.
     *
     * The posted group is checked against the same predicate that built the
     * dropdown rather than trusted, so this cannot be used to read the stores
     * of an account that is not an approved multi-store owner. It proves
     * nothing about the eventual save - register() re-checks the pair - it only
     * keeps the endpoint from being a directory of everybody's locations.
     */
    public function ajax_getstorelist()
    {
        $this->setup();

        $group = $this->custom->get_where('users', [
            'u_id'       => (int) $this->input->post('groupid'),
            'u_usertype' => 1,
            'u_emp_role' => 1,
            'u_status'   => 1,
        ]);

        $stores = $group ? storesForOwner((int) $group[0]->u_id) : [];

        if ($stores === []) {
            echo '<option value="">-- This group has no stores yet --</option>';

            return;
        }

        $chosen     = (int) $this->input->post('storeid');
        $store_data = '<option value="">-- Select Store --</option>';

        // One store, one manager. A branch that already has one is listed but
        // cannot be picked, so somebody looking for their own store sees that
        // it is there and spoken for rather than wondering why it is missing.
        // register() refuses the id as well - this is the courtesy, not the
        // guard.
        //
        // The back office is exempt, and shares this endpoint: an administrator
        // is the one who sorts out who runs what, so they have to be able to
        // open a manager on the very store they hold - and to move one onto a
        // branch whose manager they are about to remove.
        $taken = $this->session->userdata('isAdminUserLoggedIn')
            ? []
            : storeManagerIds(array_column($stores, 's_id'));

        // Same label as every other store picker: the name, and the store
        // number after it when there is one, which is what tells two branches
        // of the same chain apart. Escaped, unlike the city list above - a
        // store name is typed by an employer, a province name is not.
        foreach ($stores as $store) {
            $label = $store->s_name . ($store->s_number !== '' ? ' (' . $store->s_number . ')' : '');
            $held  = isset($taken[(int) $store->s_id]);

            if ($held) {
                $label .= ' - already has a manager';
            }

            $store_data .= '<option value="' . (int) $store->s_id . '"'
                . ($held ? ' disabled' : '')
                . ((! $held && $chosen == $store->s_id) ? ' selected' : '') . '>' . esc($label) . '</option>';
        }

        echo $store_data;
    }

    public function account()
    {
        $this->setup();

        $this->load->admin_view('dashboard', $this->data);
    }

    public function terms_conditions()
    {
        $this->setup();

        $this->session->set_userdata('site_lang', 'english');

        $this->load->front_view('terms_conditions', $this->data);
    }

    public function privacy_policy()
    {
        $this->setup();

        $this->session->set_userdata('site_lang', 'english');

        $this->load->front_view('privacy_policy', $this->data);
    }

    /**
     * The Unsubscribe link on every e-mail the site sends.
     *
     * Reached from a mailbox, so it signs nobody in and asks for no password:
     * the token in the URL is the whole of the authorisation, which is the only
     * thing somebody holding the e-mail is guaranteed to have. It says which
     * address it is about before acting, so a link forwarded to the wrong
     * person is visibly the wrong account rather than a silent opt-out.
     *
     * GET only ever shows the question. Mail clients, link scanners and
     * corporate mail gateways fetch every URL in a message before a human sees
     * it, and an opt-out that happens on GET is one those prefetches perform on
     * the recipient's behalf - people vanish off the list having clicked
     * nothing. The POST behind the button is what writes.
     *
     * A POST with no button is the one-click unsubscribe from RFC 8058: the
     * List-Unsubscribe-Post header on these e-mails tells Gmail and Yahoo they
     * may post here directly from their own Unsubscribe button, with no form
     * and no session. That is deliberately honoured, because the alternative
     * button next to it in those clients is "report spam".
     */
    public function unsubscribe($token = '')
    {
        $this->setup();

        $this->data['pageTitle'] = 'Email preferences';
        $this->data['token']     = (string) $token;

        $user = $this->unsubscribeAccount((string) $token);

        if ($user === null) {
            // Says no more than that this link does not work. Which tokens
            // exist is not something a stranger with a guess should learn.
            $this->data['state'] = 'invalid';

            $this->load->front_view('unsubscribe', $this->data);

            return;
        }

        $this->data['account'] = $user->u_email;

        if (strtolower($this->request->getMethod()) === 'post') {
            // date() and not NOW(): this database's clock and this server's
            // disagree, and every other stamp in the application is PHP's.
            $resubscribing = $this->input->post('resubscribeSubmit') !== null;

            $this->custom->updateData(
                'users',
                [
                    'u_unsubscribed_at' => $resubscribing ? null : date('Y-m-d H:i:s'),
                    'modified'          => date('Y-m-d H:i:s'),
                ],
                ['u_id' => (int) $user->u_id]
            );

            log_message('info', sprintf(
                'Unsubscribe: %s %s (u_id %d).',
                $user->u_email,
                $resubscribing ? 're-subscribed' : 'opted out of all optional e-mail',
                (int) $user->u_id
            ));

            $this->data['state'] = $resubscribing ? 'resubscribed' : 'done';

            $this->load->front_view('unsubscribe', $this->data);

            return;
        }

        // Somebody who is already off the list gets the way back rather than a
        // button that repeats what they did.
        $this->data['state'] = userHasUnsubscribed($user) ? 'already' : 'confirm';

        $this->load->front_view('unsubscribe', $this->data);
    }

    /**
     * The account an unsubscribe token belongs to, or null.
     *
     * A blank token matches the rows that have not been given one yet, so it is
     * refused before the query rather than opting out whichever account the
     * database returned first.
     */
    private function unsubscribeAccount(string $token): ?object
    {
        $token = trim($token);

        if ($token === '' || ! unsubscribeReady()) {
            return null;
        }

        return ci_db()->table('users')
            ->select('u_id, u_email, u_fname, u_lname, u_unsubscribed_at')
            ->where('u_unsub_token', $token)
            ->get(1)
            ->getRow();
    }

    public function contact()
    {
        $this->setup();

        // Names the banner heading and the browser tab. Without it both were
        // blank and the page announced itself with a second heading in the
        // body instead.
        $this->data['pageTitle'] = 'Contact Us';

        $this->session->set_userdata('site_lang', 'english');

        if ($this->input->post('contactsub')) {
            // Get form data
            $name    = $this->input->post('name');
            $email   = $this->input->post('email');
            $subject = $this->input->post('msg_subject');
            $message = $this->input->post('message');

            // Prepare data for the email template
            $data = [
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject,
                'message' => $message,
            ];

            $admin_email = $this->data['settings'][0]->s_email;
            $subject     = 'New Contact Us Message';

            // The template is a whole document; the `<p>` that used to wrap it
            // put a <!DOCTYPE> inside a paragraph, which clients render as text.
            $body = email_body('contact', $data + ['settings' => $this->data['settings']]);

            if (send_email($admin_email, $subject, $body)) {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-success">Your message has been sent successfully!</div>');
                log_message('info', 'Email sent successfully!');
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Failed to send your message.</div>');
                log_message('error', 'Failed to send email.');
            }

            ci_redirect('contact', 'auto', 303);
        }

        $this->load->front_view('contact', $this->data);
    }

    /*
     * The `test_email()` / `test_emails()` actions were removed on 4 Aug 2026.
     * Auto-routing made them publicly reachable at /front/test_email, where any
     * visitor could make the site send a real e-mail to a hard-coded address.
     * Use `php spark email:test <address>` instead - it is not reachable from
     * the web.
     */

    /**
     * The verification-code image used by every login/registration form.
     */
    public function test_cap()
    {
        $this->setup();

        getNCaptcha();
    }
}
