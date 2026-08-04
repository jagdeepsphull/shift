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

        $this->data['shift_for'] = $this->custom->get_where('shift_for', ['sf_status' => 1]);

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
            'p_date_start',
            'asc'
        );

        $this->data['agencylist'] = $this->custom->get_where('users', ['u_usertype' => 1, 'u_status' => 1]);

        $this->load->front_view('index', $this->data, 1);
    }

    public function switchLang($language = '')
    {
        $this->setup();

        $language = ($language !== '') ? $language : 'english';
        $this->session->set_userdata('site_lang', $language);

        ci_redirect($this->request->getServer('HTTP_REFERER') ?? base_url());
    }

    public function signup()
    {
        $this->setup();

        $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);
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

                    if (send_email($email, 'Reset Password', '<p>' . $reset_link . '</p>')) {
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

        $this->data['jobdetail'] = $this->custom->query(
            'Select pj.*,pr.p_name, cit.c_name ,u.u_comp_name,u.u_company_logo,u.u_licence_no '
            . 'from post_job pj, province pr, city cit, users u '
            . 'where pj.p_province = pr.p_id and p_city = cit.c_id and pj.u_id = u.u_id and pj.p_id = ?',
            [$id]
        );

        $this->data['appliedjob'] = $this->custom->get_where('stu_saved_applied_jobs', ['p_id' => $id, 'u_id' => $uid]);
        $this->data['relatedjobs'] = $this->custom->query('Select pj.*,pr.p_name, cit.c_name ,u.u_comp_name,u.u_company_logo,u.u_licence_no from post_job pj, province pr, city cit, users u where pj.p_province= pr.p_id and p_city=cit.c_id and  pj.u_id=u.u_id and pj.p_approved=1 ');

        $this->data['applied'] = 0;
        if (count($this->data['appliedjob']) > 0) {
            $this->data['applied'] = 1;
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

        $searchqry = '';

        if (! empty($searchkey)) {
            $searchqry .= " and pj.p_job_title like '%" . $searchkey . "%' ";
        }

        if (! empty($country)) {
            $searchqry .= " and pj.p_country = '" . $country . "' ";
        }

        if (! empty($category)) {
            $searchqry .= " and pj.js_id = '" . $category . "' ";
        }

        $this->data['searchlist'] = $this->custom->query("Select pj.*,c.cname,u.u_a_comp_name,u.u_a_company_logo,u.u_a_ra_id from post_job pj, country c, users u where pj.p_country=c.id and pj.u_id=u.u_id {$searchqry} ");

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

                    // The password is checked before the status, so an inactive
                    // account cannot be discovered by the message alone.
                    if ($checkLogin && $this->custom->passwordMatches((string) $this->input->post('password'), $checkLogin)) {
                        if ($checkLogin['u_status'] == 1) {
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
                        $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Wrong Username or password, please try again.</div>');
                    }
                }
            } else {
                $this->session->set_flashdata('error_msg', '<div class="alert alert-warning">Please fill all the mandatory fields.</div>');
            }
        }

        $this->session->set_userdata('site_lang', 'english');

        $this->load->front_view('signup', $this->data);
    }

    public function register()
    {
        $this->setup();

        $this->data['province'] = $this->custom->get_where('province', ['p_status' => 1]);

        $userData = [];

        if ($this->input->post('signupSubmit')) {
            $user_captcha   = $this->input->post('captcha');
            $stored_captcha = $this->session->userdata('captcha_code');

            $this->form_validation->set_rules('u_email', 'Email', 'required|valid_email|callback_email_check|is_unique[users.u_userid]');
            $this->form_validation->set_rules('password', 'password', 'required');
            $this->form_validation->set_rules('u_fname', 'First Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);
            $this->form_validation->set_rules('u_lname', 'Last Name', ['required', 'regex_match[' . NAME_PATTERN . ']']);

            $this->form_validation->set_message('is_unique', 'The %s is already taken');
            $this->form_validation->set_rules('conf_password', 'confirm password', 'required|matches[password]');

            $userData = [
                'u_userid'     => strip_tags((string) $this->input->post('u_email')),
                'u_pass'       => $this->custom->hashPassword((string) $this->input->post('password')),
                'u_phone'      => strip_tags((string) $this->input->post('u_phone')),
                'u_email'      => strip_tags((string) $this->input->post('u_email')),
                'u_comp_name'  => ($this->input->post('u_usertype') == 1) ? strip_tags((string) $this->input->post('u_comp_name')) : '',

                'u_fname'      => strip_tags((string) $this->input->post('u_fname')),
                'u_lname'      => strip_tags((string) $this->input->post('u_lname')),
                'u_l_provice'  => strip_tags((string) $this->input->post('u_l_provice')),
                'u_licence_no' => strip_tags((string) $this->input->post('u_licence_no')),

                'u_provice'    => strip_tags((string) $this->input->post('u_provice')),
                'u_city'       => strip_tags((string) $this->input->post('u_city')),
                'u_address1'   => strip_tags((string) $this->input->post('u_address1')),
                'u_pincode'    => strip_tags((string) $this->input->post('u_pincode')),

                'u_usertype'   => $this->input->post('u_usertype'),
                'u_usersubtype' => $this->input->post('u_usersubtype'),

                'u_ipaddress'  => $this->input->ip_address(),
            ];

            if ($this->form_validation->run() === true) {
                if ($user_captcha != $stored_captcha) {
                    $this->session->set_flashdata('error_msg', '<div class="alert alert-danger">Invalid CAPTCHA. Please try again.</div>');
                } else {
                    $insert = $this->custom->insert('users', $userData);

                    if ($insert) {
                        $email = $userData['u_email'];

                        $this->data['name'] = $userData['u_fname'] . ' ' . $userData['u_lname'];

                        $subject      = 'Welcome to ' . $this->data['settings'][0]->s_sitename . '! Your Account is Pending';
                        $message_user = '<div class="container">
										<div class="header">
											<h1>Welcome to ' . $this->data['settings'][0]->s_sitename . '!</h1>
										</div>
										<div class="content">
											<h1>Hello, ' . $this->data['name'] . '!</h1>
											<p>Your account is currently under review and will be activated upon approval. Once approved, you will receive a confirmation email with further details.</p>
											<p>In the meantime, if you have any questions, feel free to reach out to our support team</p>
											<p>We appreciate your patience and look forward to having you as part of our community!</p>

										</div>
										<div class="footer">
											<p>If you have any questions, feel free to <a href="mailto: ' . $this->data['settings'][0]->s_email . '">contact us</a>". We\'re here to help!</p>
											<p>&copy; ' . date('Y') . $this->data['settings'][0]->s_sitename . '. All rights reserved.</p>
										</div>
									</div>';

                        if (send_email($email, $subject, '<p>' . $message_user . '</p>')) {
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

    public function contact()
    {
        $this->setup();

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
            $email_body  = $this->load->view('emails/contact', $data, true);

            if (send_email($admin_email, $subject, '<p>' . $email_body . '</p>')) {
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
