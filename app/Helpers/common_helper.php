<?php

/**
 * Application helper, ported from CI3 `application/helpers/common_helper.php`.
 *
 * The functions keep their original names and signatures; only the internals
 * were moved from the CI3 loader/libraries to CodeIgniter 4 services.
 */

use CodeIgniter\Files\File;

if (! function_exists('dateFormat')) {
    function dateFormat($givenDate = null, $format = 'd M Y')
    {
        if (empty($givenDate)) {
            return '';
        }

        $timestamp = strtotime($givenDate);

        return $timestamp === false ? '' : date($format, $timestamp);
    }
}

if (! function_exists('dateformat')) {
    /** Lower-case alias used by a couple of the admin e-mail templates. */
    function dateformat($givenDate = null, $format = 'd M Y')
    {
        return dateFormat($givenDate, $format);
    }
}

if (! function_exists('parseShiftDate')) {
    /**
     * Turn the shift date as typed (`dd-mm-yyyy` from the date picker) into a
     * value for the sortable `post_job.p_date_start` column.
     *
     * @return string|null Y-m-d, or null when the text cannot be read as a date
     */
    function parseShiftDate($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // The date picker's format, which strtotime would read as m-d-Y.
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3])
                ? $m[3] . '-' . $m[2] . '-' . $m[1]
                : null;
        }

        if (! preg_match('/(19|20)\d{2}/', $value)) {
            return null; // no year, so the date cannot be established
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}

if (! function_exists('shiftDateSortValue')) {
    /**
     * ISO value for a table cell's `data-order` attribute, so the browser sorts
     * shift dates chronologically rather than alphabetically. Rows with an
     * unreadable date sort last.
     */
    function shiftDateSortValue($row)
    {
        if (is_object($row) && ! empty($row->p_date_start)) {
            return substr((string) $row->p_date_start, 0, 10);
        }

        if (is_array($row) && ! empty($row['p_date_start'])) {
            return substr((string) $row['p_date_start'], 0, 10);
        }

        $raw    = is_object($row) ? ($row->p_dates ?? '') : (is_array($row) ? ($row['p_dates'] ?? '') : $row);
        $parsed = parseShiftDate($raw);

        return $parsed ?? '9999-12-31';
    }
}

if (! function_exists('asset_url')) {
    function asset_url($path = '')
    {
        return base_url(ASSETS_URL . ltrim($path, '/'));
    }
}

if (! function_exists('pr')) {
    function pr($givenArray = null)
    {
        echo '<pre>';
        print_r($givenArray);
        echo '</pre>';
    }
}

if (! function_exists('prx')) {
    function prx($givenArray = null)
    {
        echo '<pre>';
        print_r($givenArray);
        echo '</pre>';

        exit;
    }
}

if (! function_exists('qry')) {
    /** Dump the last query and stop - the CI3 debugging aid. */
    function qry()
    {
        echo (string) ci_db()->getLastQuery();

        exit;
    }
}

if (! function_exists('cleanArray')) {
    function cleanArray($arraydata)
    {
        $cleanarray = [];

        foreach ((array) $arraydata as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $ky => $val) {
                    $cleanarray[$key][$ky] = strip_tags((string) $val);
                }
            } else {
                $cleanarray[$key] = strip_tags((string) $value);
            }
        }

        return $cleanarray;
    }
}

if (! function_exists('insertQryNoValidation')) {
    function insertQryNoValidation($table, $rowData, $msg = '')
    {
        $ci = get_instance();

        $insert = $ci->custom->insert($table, $rowData);

        if ($insert) {
            $ci->session->set_flashdata('error_msg', $msg === 'newjob' ? INSERTJOB : INSERT);

            return true;
        }

        $ci->session->set_flashdata('error_msg', WRONG);

        return false;
    }
}

if (! function_exists('insertQry')) {
    function insertQry($table, $rowData, $msg = '')
    {
        $ci = get_instance();

        if ($ci->form_validation->run() === true) {
            $insert = $ci->custom->insert($table, $rowData);

            if ($insert) {
                $ci->session->set_flashdata('error_msg', $msg === 'newjob' ? INSERTJOB : INSERT);

                return true;
            }

            $ci->session->set_flashdata('error_msg', WRONG);

            return false;
        }

        return false;
    }
}

if (! function_exists('insertQry_N')) {
    function insertQry_N($table, $rowData)
    {
        $ci = get_instance();

        if ($ci->form_validation->run() === true) {
            $insert = $ci->custom->insert_data($table, $rowData);

            if ($insert) {
                $ci->session->set_flashdata('error_msg', INSERT);

                return true;
            }

            $ci->session->set_flashdata('error_msg', WRONG);

            return false;
        }

        return false;
    }
}

if (! function_exists('updateQry')) {
    function updateQry($table, $rowData, $where)
    {
        $ci = get_instance();

        if ($ci->form_validation->run() === true) {
            $update = $ci->custom->updateData($table, $rowData, $where);

            if ($update) {
                $ci->session->set_flashdata('error_msg', UPDATE);

                return true;
            }

            $ci->session->set_flashdata('error_msg', WRONG);

            return false;
        }

        $ci->session->set_flashdata('error_msg', EMPTY_FORM);

        return false;
    }
}

if (! function_exists('getTableInfo')) {
    /**
     * Seed `$this->data` with one entry per column of `$table`, filled from the
     * matching row when `$where` is given. Used by every "add/edit" screen so
     * the view can echo `$column_name` directly.
     */
    function getTableInfo($db, $table, $where = [])
    {
        $ci = get_instance();

        $tableinfo = ci_db()->query(
            'SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?',
            [$db, $table]
        );

        $rescol = $tableinfo->getResult();

        if ($where) {
            $tblrow = $ci->custom->get_where($table, $where);

            foreach ($rescol as $rcol) {
                $fieldname = $rcol->COLUMN_NAME;

                $ci->data[$fieldname] = (isset($tblrow[0]->{$fieldname}) && $tblrow[0]->{$fieldname} !== '')
                    ? $tblrow[0]->{$fieldname}
                    : '';
            }
        } else {
            foreach ($rescol as $rcol) {
                $ci->data[$rcol->COLUMN_NAME] = '';
            }
        }
    }
}

if (! function_exists('getNCaptcha')) {
    /**
     * Render the 6-digit verification image and remember the code in the
     * session (`captcha_code`). Served by `front/test_cap`.
     */
    function getNCaptcha()
    {
        $ci = get_instance();

        $code = (string) random_int(100000, 999999);

        if ($ci !== null) {
            $ci->session->set_userdata('captcha_code', $code);
        } else {
            session()->set('captcha_code', $code);
        }

        $padding_x = 2;
        $padding_y = 2;

        $image_width  = 150 + (2 * $padding_x);
        $image_height = 50 + (2 * $padding_y);

        $im = imagecreatetruecolor($image_width, $image_height);

        $bg         = imagecolorallocate($im, 85, 172, 228);  // blue background
        $fg         = imagecolorallocate($im, 255, 255, 255); // white text
        $line_color = imagecolorallocate($im, 220, 220, 220);

        imagefilledrectangle($im, 0, 0, $image_width, $image_height, $bg);

        // Noise
        for ($i = 0; $i < 10; $i++) {
            imageline($im, random_int(0, 150), random_int(0, 50), random_int(0, 150), random_int(0, 50), $line_color);
        }

        $font_path = FCPATH . 'assets/fonts/texb.ttf';
        $font_size = 24;

        if (is_file($font_path) && function_exists('imagettftext')) {
            $bbox = imagettfbbox($font_size, 0, $font_path, $code);

            $text_width  = $bbox[2] - $bbox[0];
            $text_height = $bbox[1] - $bbox[7];

            $x_position = (int) (($image_width - $text_width) / 2);
            $y_position = (int) ((($image_height - $text_height) / 2) + $text_height);

            imagettftext($im, $font_size, 0, $x_position, $y_position, $fg, $font_path, $code);
        } else {
            // GD without FreeType: fall back to the built-in font.
            imagestring($im, 5, 40, 18, $code, $fg);
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: image/png');
        imagepng($im);
        imagedestroy($im);

        exit;
    }
}

if (! function_exists('getCaptcha')) {
    /**
     * CI3 captcha-helper based image, kept for the job-detail "get in touch"
     * form. Returns the `<img>` markup and records the word in the `captcha`
     * table, as the CI3 version did.
     */
    function getCaptcha()
    {
        $vals = [
            'img_path'    => FCPATH . 'captcha/',
            'img_url'     => base_url('captcha/'),
            'font_path'   => FCPATH . 'assets/fonts/texb.ttf',
            'img_width'   => 150,
            'img_height'  => 30,
            'expiration'  => 7200,
            'word_length' => 4,
            'font_size'   => 16,
            'img_id'      => 'Imageid',
            'pool'        => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'colors'      => [
                'background' => [255, 255, 255],
                'border'     => [255, 255, 255],
                'text'       => [0, 0, 0],
                'grid'       => [255, 40, 40],
            ],
        ];

        $cap = create_captcha($vals);

        if ($cap === false) {
            return '';
        }

        ci_db()->table('captcha')->insert([
            'captcha_time' => $cap['time'],
            'ip_address'   => service('request')->getIPAddress(),
            'word'         => $cap['word'],
        ]);

        return $cap['image'];
    }
}

if (! function_exists('fileupload')) {
    /**
     * Move an uploaded file into `uploads/<path>/` and store its new name in
     * the given table column.
     *
     * @param array $upload_param filename, path, types, size, width, height,
     *                            table, field, pkfield, pkval
     *
     * @return array{error: int, status: mixed}
     */
    function fileupload($upload_param = [])
    {
        $ci      = get_instance();
        $request = service('request');

        $filestatus = [];

        $field = $upload_param['filename'] ?? '';
        $file  = $request->getFile($field);

        if ($file === null || ! $file->isValid()) {
            return [
                'error'  => 1,
                'status' => ['error' => $file === null ? 'No file was selected.' : $file->getErrorString()],
            ];
        }

        $allowed = isset($upload_param['types']) && $upload_param['types'] !== ''
            ? explode('|', strtolower($upload_param['types']))
            : ['gif', 'jpg', 'png'];

        $maxSizeKb = (int) ($upload_param['size'] ?? 1024);
        $maxWidth  = (int) ($upload_param['width'] ?? 0);
        $maxHeight = (int) ($upload_param['height'] ?? 0);

        $extension = strtolower($file->getClientExtension() ?: $file->getExtension());

        if (! in_array($extension, $allowed, true)) {
            return ['error' => 1, 'status' => ['error' => 'The filetype you are attempting to upload is not allowed.']];
        }

        if ($maxSizeKb > 0 && $file->getSizeByUnit('kb') > $maxSizeKb) {
            return ['error' => 1, 'status' => ['error' => 'The file you are attempting to upload is larger than the permitted size.']];
        }

        if (($maxWidth > 0 || $maxHeight > 0) && in_array($extension, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            $dimensions = @getimagesize($file->getTempName());

            if ($dimensions !== false) {
                if ($maxWidth > 0 && $dimensions[0] > $maxWidth) {
                    return ['error' => 1, 'status' => ['error' => 'The image you are attempting to upload is too wide.']];
                }
                if ($maxHeight > 0 && $dimensions[1] > $maxHeight) {
                    return ['error' => 1, 'status' => ['error' => 'The image you are attempting to upload is too tall.']];
                }
            }
        }

        $path = FCPATH . 'uploads/' . (isset($upload_param['path']) ? trim($upload_param['path'], '/') . '/' : '');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $new_name = time() . '_' . $file->getClientName();

        try {
            $file->move($path, $new_name, true);
        } catch (Throwable $e) {
            return ['error' => 1, 'status' => ['error' => $e->getMessage()]];
        }

        $moved = new File($path . $new_name);

        $filestatus['error']  = 0;
        $filestatus['status'] = [
            'upload_data' => [
                'file_name' => $moved->getBasename(),
                'file_path' => $path,
                'full_path' => $moved->getRealPath(),
                'file_size' => $moved->getSizeByUnit('kb'),
            ],
        ];

        if (! empty($upload_param['table']) && ! empty($upload_param['field'])) {
            $ci->custom->updateData(
                $upload_param['table'],
                [$upload_param['field'] => $moved->getBasename()],
                [$upload_param['pkfield'] => $upload_param['pkval']]
            );
        }

        return $filestatus;
    }
}

if (! function_exists('getPharmacyName')) {
    function getPharmacyName($uid = 0)
    {
        $row = ci_db()->table('users')
            ->where('u_id', $uid)
            ->where('u_usertype', 1) // 1 => Pharmacy
            ->where('u_status', 1)
            ->get()
            ->getRow();

        return $row ? $row->u_comp_name : '';
    }
}

if (! function_exists('getCityName')) {
    function getCityName($cid = 0)
    {
        $row = ci_db()->table('city')
            ->where('c_id', $cid)
            ->where('c_status', 1)
            ->get()
            ->getRow();

        return $row ? $row->c_name : '';
    }
}

if (! function_exists('getProvinceName')) {
    function getProvinceName($pid = 0)
    {
        $row = ci_db()->table('province')
            ->where('p_id', $pid)
            ->where('p_status', 1)
            ->get()
            ->getRow();

        return $row ? $row->p_name : '';
    }
}

if (! function_exists('getShiftForName')) {
    function getShiftForName($sid = 0)
    {
        $row = ci_db()->table('shift_for')
            ->where('sf_id', $sid)
            ->where('sf_status', 1)
            ->get()
            ->getRow();

        return $row ? $row->sf_name : '';
    }
}

if (! function_exists('getSoftwareSkills')) {
    /**
     * @param string $id_list comma separated `software_skills.ss_id` list
     */
    function getSoftwareSkills($id_list = 0)
    {
        $id_array = array_filter(array_map('trim', explode(',', (string) $id_list)), 'strlen');

        if ($id_array === []) {
            return '';
        }

        $query = ci_db()->table('software_skills')
            ->whereIn('ss_id', $id_array)
            ->where('ss_status', 1)
            ->get();

        $skills = [];

        foreach ($query->getResult() as $row) {
            $skills = array_merge($skills, explode(',', $row->ss_name));
        }

        return implode(', ', array_map('trim', array_unique($skills)));
    }
}

if (! function_exists('getStoreServices')) {
    /**
     * @param string $id_list comma separated `store_service.st_id` list
     */
    function getStoreServices($id_list = 0)
    {
        $id_array = array_filter(array_map('trim', explode(',', (string) $id_list)), 'strlen');

        if ($id_array === []) {
            return '';
        }

        $query = ci_db()->table('store_service')
            ->whereIn('st_id', $id_array)
            ->where('st_status', 1)
            ->get();

        $skills = [];

        foreach ($query->getResult() as $row) {
            $skills = array_merge($skills, explode(',', $row->st_service_name));
        }

        return implode(', ', array_map('trim', array_unique($skills)));
    }
}

if (! function_exists('send_email')) {
    /**
     * Send an HTML e-mail. Returns true on success, false (and a log entry) on
     * failure - same contract as the CI3 version.
     */
    function send_email($to, $subject, $message)
    {
        $settings = config('AppSettings');

        $email = service('email');

        $email->initialize([
            'protocol' => 'mail', // as in CI3; switch to 'smtp' in app/Config/Email.php if needed
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'wordWrap' => true,
            'newline'  => "\r\n",
            'CRLF'     => "\r\n",
        ]);

        $email->setFrom($settings->mailFromEmail, $settings->mailFromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);

        if ($email->send(false)) {
            return true;
        }

        log_message('error', $email->printDebugger(['headers']));

        // A silent failure looks exactly like a success to whoever pressed the
        // button. Record it so the page that follows can say so.
        $failures   = session()->getTempdata('email_failures') ?? [];
        $failures[] = ['to' => is_array($to) ? implode(', ', $to) : (string) $to, 'subject' => (string) $subject];
        session()->setTempdata('email_failures', $failures, 300);

        return false;
    }
}

if (! function_exists('getRandomString')) {
    function getRandomString($n = 3)
    {
        $characters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}

if (! function_exists('testHello')) {
    function testHello($sid = 0)
    {
        echo 'hello';
    }
}
