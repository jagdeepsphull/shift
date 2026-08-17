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

if (! function_exists('shiftDateOrderBy')) {
    /**
     * The ORDER BY expression every shift list sorts on: soonest shift first,
     * or the latest first with `$direction = 'DESC'`, which is how the admin's
     * shift list reads.
     *
     * A shift whose date could not be read is sorted last rather than ahead of
     * everything, which is where a plain `ASC` would put it - MySQL orders NULL
     * before any date. `php spark shifts:backfill-dates` lists those rows. The
     * `IS NULL` half is deliberately never reversed, so an unknown date stays at
     * the bottom whichever way round the dates themselves run.
     *
     * Pass it with escaping off, as it is an expression and not a column name:
     *   $this->custom->get_where_order('post_job', $where, shiftDateOrderBy(), '', false)
     *
     * @param string $alias     table alias used by the query, if any
     * @param string $direction ASC (soonest first) or DESC (latest first)
     */
    function shiftDateOrderBy($alias = '', $direction = 'ASC')
    {
        $column    = ($alias === '' ? '' : $alias . '.') . 'p_date_start';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        return $column . ' IS NULL, ' . $column . ' ' . $direction;
    }
}

if (! function_exists('shiftDateSortValue')) {
    /**
     * ISO value for a table cell's `data-order` attribute, so the browser sorts
     * shift dates chronologically rather than alphabetically. Rows with an
     * unreadable date sort last.
     *
     * "Last" depends on which way the table runs, and the browser has only the
     * one value to sort on, so a table whose date column runs DESC says so and
     * gets the fallback at the other end of the scale.
     *
     * @param string $direction the table's default sort, ASC or DESC
     */
    function shiftDateSortValue($row, $direction = 'ASC')
    {
        if (is_object($row) && ! empty($row->p_date_start)) {
            return substr((string) $row->p_date_start, 0, 10);
        }

        if (is_array($row) && ! empty($row['p_date_start'])) {
            return substr((string) $row['p_date_start'], 0, 10);
        }

        $raw    = is_object($row) ? ($row->p_dates ?? '') : (is_array($row) ? ($row['p_dates'] ?? '') : $row);
        $parsed = parseShiftDate($raw);

        return $parsed ?? (strtoupper($direction) === 'DESC' ? '0000-01-01' : '9999-12-31');
    }
}

if (! function_exists('shiftIsUpcoming')) {
    /**
     * Is the shift still ahead of today - that is, can it still be rearranged?
     *
     * This is what lets the admin edit a shift somebody is already booked on:
     * an applicant who says they cannot make it has to be swapped out, and the
     * only sane cut-off for that is the day the shift is worked. On the day
     * itself and after it the shift is history and nothing may be touched, so
     * "today" is deliberately not upcoming.
     *
     * A shift whose date cannot be read at all counts as not upcoming: the
     * frozen side is the safe side, and `php spark shifts:backfill-dates`
     * lists those rows so they can be given a date.
     *
     * @param array<string, mixed>|object $row a `post_job` row
     */
    function shiftIsUpcoming($row): bool
    {
        $date = is_object($row) ? ($row->p_date_start ?? null) : ($row['p_date_start'] ?? null);
        $date = $date === null ? '' : substr((string) $date, 0, 10);

        if ($date === '' || $date === '0000-00-00') {
            $raw  = is_object($row) ? ($row->p_dates ?? '') : ($row['p_dates'] ?? '');
            $date = (string) parseShiftDate($raw);
        }

        return $date !== '' && $date > date('Y-m-d');
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

if (! function_exists('phoneFields')) {
    /**
     * The POST/column names that hold a mobile number, anywhere on the site.
     *
     * One list, so a number is trimmed to PHONE_LENGTH digits the same way
     * whether it arrived from registration, a profile page, a store form or the
     * back office.
     *
     * @return string[]
     */
    function phoneFields(): array
    {
        return ['u_phone', 's_phone', 'mobile', 'u_a_cp_mobile'];
    }
}

if (! function_exists('normalisePhone')) {
    /**
     * A mobile number as typed, reduced to the bare digits that get stored.
     *
     * The forms already cap the field at PHONE_LENGTH and refuse anything but
     * digits, but a form field is only a suggestion - the value still arrives
     * over HTTP and can say anything. So brackets, spaces, dashes and a leading
     * country code are dropped here, and the result is cut to PHONE_LENGTH
     * digits. An empty box stays empty: the field is optional on the store
     * forms, and this must not turn a blank into a bogus number.
     */
    function normalisePhone($phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        // "+1 905 304 7303" and "1-905-304-7303" are the same ten-digit number
        // with the North American country code in front of it.
        if (strlen($digits) === PHONE_LENGTH + 1 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }

        return substr($digits, 0, PHONE_LENGTH);
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

        // Every form that writes a row through this funnel - registration, both
        // profile pages, and the back-office user forms - gets the same
        // digits-only, PHONE_LENGTH-long number, without each caller having to
        // remember to ask for it.
        foreach (phoneFields() as $field) {
            if (isset($cleanarray[$field]) && ! is_array($cleanarray[$field])) {
                $cleanarray[$field] = normalisePhone($cleanarray[$field]);
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

if (! function_exists('lookupRow')) {
    /**
     * One row from a lookup table, remembered for the rest of the request.
     *
     * The name helpers below are called once per row of a listing, and every
     * one of them was a query: an admin shift list of 300 rows asked the
     * province, city, employer and shift-for tables 300 times each, for a
     * handful of distinct answers. The cache is keyed by table and id, so a
     * page asks for each distinct id once however many rows repeat it - and a
     * page that shows a single record still runs the single query it always
     * did.
     *
     * Read-only lookups only. A row written during the same request would
     * still be served from here, which is why nothing that saves uses it.
     *
     * @param int|string           $id
     * @param array<string, mixed> $extra further where conditions
     *
     * @return object|null
     */
    function lookupRow(string $table, string $idColumn, $id, array $extra = [])
    {
        static $cache = [];

        $key = $table . '#' . $idColumn . '#' . $id . '#' . implode(',', array_map(
            static fn ($k, $v) => $k . '=' . $v,
            array_keys($extra),
            $extra
        ));

        if (! array_key_exists($key, $cache)) {
            $builder = ci_db()->table($table)->where($idColumn, $id);

            foreach ($extra as $column => $value) {
                $builder->where($column, $value);
            }

            $cache[$key] = $builder->get()->getRow();
        }

        return $cache[$key];
    }
}

if (! function_exists('getPharmacyName')) {
    function getPharmacyName($uid = 0)
    {
        // 1 => Pharmacy
        $row = lookupRow('users', 'u_id', $uid, ['u_usertype' => 1, 'u_status' => 1]);

        return $row ? $row->u_comp_name : '';
    }
}

if (! function_exists('getCityName')) {
    function getCityName($cid = 0)
    {
        $row = lookupRow('city', 'c_id', $cid, ['c_status' => 1]);

        return $row ? $row->c_name : '';
    }
}

if (! function_exists('shiftStore')) {
    /**
     * The store (location) a shift belongs to - name, number, address, phone.
     *
     * A shift from before the multi-store feature has `p_store_id` 0; those
     * fall back to the owner's login columns, which is where the store data
     * lived until then - so every historic shift keeps showing the address it
     * always showed.
     *
     * @param object $job a `post_job` row (needs p_store_id and u_id)
     * @return object|null null only when the owner row is gone too
     */
    function shiftStore($job)
    {
        if (! empty($job->p_store_id)) {
            $store = ci_db()->table('store')
                ->where('s_id', $job->p_store_id)
                ->get()
                ->getRow();

            if ($store) {
                return $store;
            }
        }

        $owner = ci_db()->table('users')
            ->where('u_id', $job->u_id)
            ->get()
            ->getRow();

        if (! $owner) {
            return null;
        }

        return (object) [
            's_id'       => 0,
            'u_id'       => $owner->u_id,
            's_name'     => $owner->u_comp_name,
            's_number'   => $owner->u_licence_no,
            's_province' => $owner->u_provice,
            's_city'     => $owner->u_city,
            's_address'  => $owner->u_address1,
            's_pincode'  => $owner->u_pincode,
            's_phone'    => $owner->u_phone,
            's_status'   => 1,
        ];
    }
}

if (! function_exists('getProvinceName')) {
    function getProvinceName($pid = 0)
    {
        $row = lookupRow('province', 'p_id', $pid, ['p_status' => 1]);

        return $row ? $row->p_name : '';
    }
}

if (! function_exists('getShiftForName')) {
    function getShiftForName($sid = 0)
    {
        $row = lookupRow('shift_for', 'sf_id', $sid, ['sf_status' => 1]);

        return $row ? $row->sf_name : '';
    }
}

if (! function_exists('employerKindCode')) {
    /**
     * Which of the `employerKinds` an employer row is, as the number the
     * database holds. 0 for an account from before the kinds existed.
     *
     * The role alone decides. It used not to: role 2 meant a manager or an
     * individual owner depending on `u_parent_id`, and that second kind is
     * gone.
     *
     * @param array|object $user a `users` row
     */
    function employerKindCode($user): int
    {
        $code = (int) ((object) $user)->u_emp_role;

        return isset(config('AppSettings')->employerKinds[$code]) ? $code : 0;
    }
}

if (! function_exists('employerKindBySlug')) {
    /**
     * The code a URL slug stands for, or 0 when it is not one of the kinds.
     *
     * Every screen that takes a kind from a URL or a query string goes through
     * here, so an unknown one reads as "no kind chosen" rather than as an
     * error.
     */
    function employerKindBySlug(?string $slug): int
    {
        foreach (config('AppSettings')->employerKinds as $code => $kind) {
            if ($kind['slug'] === (string) $slug) {
                return (int) $code;
            }
        }

        return 0;
    }
}

if (! function_exists('whatsappNumber')) {
    /**
     * A stored phone number as WhatsApp wants it: digits only, country code
     * included. Returns '' when the number cannot be one, so the caller can
     * leave the icon off rather than offer a chat that opens on nobody.
     *
     * WhatsApp resolves the number as typed - no country code means no chat -
     * and these are typed by hand, so the column holds all of
     * "9055363588", "289-952-3889", "(289) 442-2841" and "+1 (202) 636-8007".
     * Ten digits is the North American local form every province on this site
     * uses, so it takes a 1; anything already carrying its own code is left
     * alone.
     */
    function whatsappNumber(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        // A leading + means the caller already wrote the country code.
        $hasCountryCode = str_starts_with($phone, '+');

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return '';
        }

        // 00 stands in for the + - but only ahead of a real country code, and
        // those never start with 0. Without that check the 0000000000 the back
        // office accepts as a placeholder loses two digits to this branch.
        if (! $hasCountryCode && strlen($digits) >= 12 && preg_match('/^00[1-9]/', $digits)) {
            $digits         = substr($digits, 2);
            $hasCountryCode = true;
        }

        if ($hasCountryCode) {
            return $digits;
        }

        // Ten digits is the local form, in Canada and in India alike, and the
        // digits cannot say which - so the configured code decides.
        $countryCode = preg_replace('/\D+/', '', (string) (config('AppSettings')->phoneCountryCode ?? '1'));
        $countryCode = $countryCode !== '' ? $countryCode : '1';

        if (strlen($digits) === 10) {
            return $countryCode . $digits;
        }

        // The same number with the code already on the front, written without
        // the +: 1 905 536 3588, or 91 98765 43210.
        if (str_starts_with($digits, $countryCode) && strlen($digits) === strlen($countryCode) + 10) {
            return $digits;
        }

        // Too short to be a real number - an extension, or a placeholder like
        // the 0000000000 the back office accepts. Nothing worth linking.
        return strlen($digits) >= 11 ? $digits : '';
    }
}

if (! function_exists('whatsappPhoneLink')) {
    /**
     * A phone number written as a WhatsApp link: the number itself is the
     * link, with the handset icon in front of it and the WhatsApp mark after.
     *
     * web.whatsapp.com rather than wa.me: the back office is used at a desk,
     * and this opens the chat in the browser the admin is already signed in to.
     *
     * The number shown is the one on file, punctuation and all; only the one
     * inside the link is normalised. A number that cannot be messaged is still
     * printed - it is worth reading even when it cannot be dialled - but as
     * plain text, so nothing opens a chat with nobody.
     */
    function whatsappPhoneLink(?string $phone, string $title = 'Send a WhatsApp message'): string
    {
        $shown = trim((string) $phone);

        if ($shown === '') {
            return '';
        }

        $icon   = '<i class="fas fa-mobile-alt display-25 mr-1 text-secondary"></i>';
        $number = whatsappNumber($shown);

        if ($number === '') {
            return '<span class="text-muted">' . $icon . esc($shown) . '</span>';
        }

        return '<a href="https://web.whatsapp.com/send?phone=' . esc($number, 'attr') . '"'
            . ' target="_blank" rel="noopener noreferrer"'
            . ' title="' . esc($title, 'attr') . '">'
            . $icon . esc($shown)
            . '<i class="fab fa-whatsapp ml-2 text-success"></i></a>';
    }
}

if (! function_exists('safeUrl')) {
    /**
     * A web address as typed, made safe to put in an `href`, or '' if it is not
     * one at all.
     *
     * Everywhere these are collected the address is pasted by hand - a Google
     * Maps share link, a pharmacy's home page - so two things have to be true
     * before it reaches a page. It has to work when clicked, which a bare
     * "example.com" does not: with no scheme a browser reads it as a path on
     * this site. And the scheme has to be one that only navigates, or a stored
     * `javascript:` address would run as script for whoever clicked it.
     */
    function safeUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        // "www.example.com" and "example.com/x" are what people paste; assume
        // https rather than rejecting them.
        if (! preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        // A scheme alone is not an address - "https://" would pass the test above.
        if ((string) parse_url($url, PHP_URL_HOST) === '') {
            return '';
        }

        return $url;
    }
}

if (! function_exists('storeMapLink')) {
    /**
     * The map link to show for a store, falling back to a search for its
     * address when nobody has pasted one.
     *
     * A shift is worth nothing to an applicant who cannot find the building, so
     * a store with an address but no pasted link still gets something to tap.
     * The pasted link is always preferred: it points at the pin somebody chose,
     * which a search for a street address in a plaza will not reliably find.
     *
     * @param object|array $store a `store` row, or the fallback shiftStore() builds
     */
    function storeMapLink($store): string
    {
        $store = (object) $store;

        $pasted = safeUrl($store->s_map_url ?? '');

        if ($pasted !== '') {
            return $pasted;
        }

        $parts = array_filter([
            trim((string) ($store->s_address ?? '')),
            getCityName((int) ($store->s_city ?? 0)),
            getProvinceName((int) ($store->s_province ?? 0)),
            trim((string) ($store->s_pincode ?? '')),
        ], 'strlen');

        if ($parts === []) {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(implode(', ', $parts));
    }
}

if (! function_exists('pharmacyGroups')) {
    /**
     * The multi-store owners a single store can be attached to, A-Z.
     *
     * Only approved ones: attaching a store to a group nobody has checked yet
     * would let an unverified account collect other people's locations. Used by
     * public registration and by the back-office employer form, which have to
     * offer the same list.
     *
     * @return array<int, object> `users` rows
     */
    function pharmacyGroups(): array
    {
        return ci_db()->table('users')
            ->where('u_usertype', 1)
            ->where('u_emp_role', 1)
            ->where('u_status', 1)
            ->orderBy('u_comp_name', 'asc')
            ->get()
            ->getResult();
    }
}

if (! function_exists('storesForOwner')) {
    /**
     * The locations one employer login owns, A-Z.
     *
     * @param bool $activeOnly false includes deactivated ones, which the
     *                         employer's own listing shows so they can be seen
     *                         and turned back on
     * @return array<int, object> `store` rows
     */
    function storesForOwner(int $ownerId, bool $activeOnly = true): array
    {
        if ($ownerId <= 0) {
            return [];
        }

        $builder = ci_db()->table('store')->where('u_id', $ownerId);

        if ($activeOnly) {
            $builder->where('s_status', 1);
        }

        return $builder->orderBy('s_name', 'asc')->get()->getResult();
    }
}

if (! function_exists('storeManagerIds')) {
    /**
     * Which of these stores already have a manager on them, and who.
     *
     * One store, one manager. A branch has a single person running it, and a
     * second account pointed at the same `s_id` would give two logins the same
     * store's shifts, applications and address with nothing to tell them apart.
     * Registration refuses it and the store picker marks it, both from here.
     *
     * An account still waiting for the administrator counts. It has already
     * claimed the store - without that, two people could register for the same
     * branch the same afternoon and only the second would be stopped. Freeing a
     * store means removing the account holding it, which the back office does.
     *
     * @param array<int, int|string> $storeIds
     *
     * @return array<int, int> `store.s_id` => `users.u_id` of the manager on it
     */
    function storeManagerIds(array $storeIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $storeIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $rows = ci_db()->table('users')
            ->select('u_id, u_store_id')
            ->where('u_usertype', 1)
            ->where('u_emp_role', 2)
            ->whereIn('u_store_id', $ids)
            ->orderBy('u_id', 'asc')
            ->get()
            ->getResult();

        $taken = [];

        foreach ($rows as $row) {
            // The first one there is the answer. On a database that somehow
            // holds two for a store, it is taken either way.
            $taken[(int) $row->u_store_id] ??= (int) $row->u_id;
        }

        return $taken;
    }
}

if (! function_exists('employerStores')) {
    /**
     * The stores a logged-in employer may post shifts against and see listed.
     *
     * An owner's are the ones they added. A manager owns none - they run one of
     * their corporate group's, named by `u_store_id` - so ownership alone would
     * show them nothing and leave them unable to post the shifts the site
     * exists for. Both cases resolve here so the rule lives in one place.
     *
     * One branch, not the chain: a manager's listing and their shift form both
     * stop at the store they were assigned, whatever else the group owns.
     *
     * @param array|object $user a `users` row
     * @return array<int, object> `store` rows
     */
    function employerStores($user, bool $activeOnly = true): array
    {
        $user    = (object) $user;
        $storeId = (int) ($user->u_store_id ?? 0);

        if ($storeId > 0) {
            $store = ci_db()->table('store')->where('s_id', $storeId)->get()->getRow();

            if ($store === null || ($activeOnly && (int) $store->s_status !== 1)) {
                return [];
            }

            return [$store];
        }

        return storesForOwner((int) $user->u_id, $activeOnly);
    }
}

if (! function_exists('employerShiftScope')) {
    /**
     * The shifts one employer login may see and manage, as a WHERE fragment.
     *
     * A shift belongs to whoever posted it and to the people answerable for
     * that branch alongside them:
     *
     *   - an owner reaches their own shifts and every shift their managers
     *     posted, because a manager posts on the owner's behalf;
     *   - a manager reaches their own shifts and whatever stands against the
     *     branch they run, which is how a shift the owner posted for that store
     *     becomes theirs to handle;
     *   - neither reaches another employer's shifts, and a manager never
     *     reaches a sibling branch's.
     *
     * One store has one manager (registration refuses a second, see
     * `storeManagerIds()`), so the branch test cannot hand a shift to two of
     * them.
     *
     * The back office is not bound by this. An administrator manages every
     * shift on the site, which is what that screen is for; this is the rule for
     * the employer's own side.
     *
     * Returns the fragment and its binds, for `CustomModel::query()`:
     *
     *     [$scope, $binds] = employerShiftScope($this->userinfo[0]);
     *     $this->custom->query('SELECT * FROM post_job WHERE ' . $scope, $binds);
     *
     * @param array|object $user  a `users` row
     * @param string       $alias table alias used by the query, if any
     *
     * @return array{0: string, 1: array<int, int>}
     */
    function employerShiftScope($user, string $alias = ''): array
    {
        $user   = (object) $user;
        $prefix = $alias === '' ? '' : $alias . '.';
        $id     = (int) $user->u_id;

        if ((int) ($user->u_emp_role ?? 0) === 2) {
            $storeId = (int) ($user->u_store_id ?? 0);

            // A manager with no branch on their row has only what they posted.
            // Parenthesised like the others: every fragment this returns is a
            // single term, so a caller can AND it to anything without the
            // meaning changing under them.
            if ($storeId <= 0) {
                return ['(' . $prefix . 'u_id = ?)', [$id]];
            }

            return ['(' . $prefix . 'u_id = ? OR ' . $prefix . 'p_store_id = ?)', [$id, $storeId]];
        }

        return [
            '(' . $prefix . 'u_id = ? OR ' . $prefix . 'u_id IN ('
                . 'SELECT m.u_id FROM users m'
                . ' WHERE m.u_parent_id = ? AND m.u_usertype = 1 AND m.u_emp_role = 2))',
            [$id, $id],
        ];
    }
}

if (! function_exists('employerKindRole')) {
    /**
     * Turn a chosen employer kind into the columns that store it.
     *
     * Takes the `employerKinds` code, or the URL slug for it. Both kinds are
     * `u_usertype` 1 and differ by `u_emp_role`; a manager additionally answers
     * to a group (`u_parent_id`) and points at that group's store
     * (`u_store_id`). Registration and the back office both map the choice the
     * same way, so the rule lives here rather than in either of them.
     *
     * `picksStore` is what makes a manager different from an owner: they choose
     * one of their group's existing stores instead of describing a new one, so
     * a caller seeing it must ignore `asksForLocation` and `ownsStore` - there
     * is no address to type and no store to create. Both forms honour it.
     *
     * @param int|string $kind an `employerKinds` code, or its slug
     *
     * @return array{role: int, needsParent: bool, asksForLocation: bool, ownsStore: bool, picksStore: bool}
     */
    function employerKindRole($kind): array
    {
        // Given a slug rather than a code - a URL, or an older caller.
        $code = is_numeric($kind) ? (int) $kind : employerKindBySlug((string) $kind);

        // An owner is never asked for a location: their licence and address
        // belong to each store they add afterwards.
        if ($code === 1) {
            return ['role' => 1, 'needsParent' => false, 'asksForLocation' => false, 'ownsStore' => false, 'picksStore' => false];
        }

        // A manager runs one of their group's existing stores, so they name the
        // group and pick the store instead of describing one of their own.
        if ($code === 2) {
            return ['role' => 2, 'needsParent' => true, 'asksForLocation' => true, 'ownsStore' => true, 'picksStore' => true];
        }

        // Not one of the kinds - the shape accounts had before they existed,
        // which owns no store record.
        return ['role' => 0, 'needsParent' => false, 'asksForLocation' => true, 'ownsStore' => false, 'picksStore' => false];
    }
}

if (! function_exists('employerKindName')) {
    /**
     * Label for an employer row, for the back-office listing.
     *
     * @param array|object $user a `users` row
     */
    function employerKindName($user): string
    {
        $code  = employerKindCode($user);
        $kinds = config('AppSettings')->employerKinds;

        return $code !== 0 ? $kinds[$code]['short'] : 'Not set';
    }
}

if (! function_exists('storeSnapshotForManager')) {
    /**
     * The store columns copied onto a manager's own `users` row.
     *
     * A manager types none of these: they pick one of their corporate group's
     * stores, and it already has them. But a dozen screens, exports and e-mails
     * read them straight off the login rather than through the store - the
     * employer listing, the employer dropdown on the shift forms, the booking
     * e-mail, the profile page's required address - so the chosen store is
     * copied across. Without it a manager is a nameless, addressless row on
     * every one of those screens.
     *
     * A snapshot taken at the moment the store is chosen, not a join: renaming
     * the store later does not rewrite it. Re-saving the manager with that
     * store still chosen takes a fresh one, which is how a stale copy is put
     * right.
     *
     * Registration and the back-office employer form both apply this, so an
     * account created either way is the same record - that is the whole point
     * of it living here rather than in one of them.
     *
     * @param object $store a `store` row
     * @return array<string, mixed> columns to merge into the `users` row
     */
    function storeSnapshotForManager($store): array
    {
        return [
            'u_comp_name'  => $store->s_name,
            'u_licence_no' => $store->s_number,
            'u_l_provice'  => (int) $store->s_province,
            'u_provice'    => (int) $store->s_province,
            'u_city'       => (int) $store->s_city,
            'u_address1'   => $store->s_address,
            'u_pincode'    => $store->s_pincode,
            'u_website'    => $store->s_website ?? '',
        ];
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

if (! function_exists('getAgencyCopyEmail')) {
    /**
     * The address copied on booking e-mails. Editable at /sadmin/settings;
     * `AppSettings::$agencyCopyEmail` is the fallback until it is set, and a
     * blank setting means "do not copy anyone".
     */
    function getAgencyCopyEmail(): string
    {
        static $address = null;

        if ($address !== null) {
            return $address;
        }

        $address = config('AppSettings')->agencyCopyEmail;

        // The column arrives with a migration; until then the config value stands.
        if (ci_db()->fieldExists('s_agency_copy_email', 'settings')) {
            $row = ci_db()->table('settings')->where('s_id', 1)->get()->getRow();

            if ($row !== null) {
                $address = trim((string) $row->s_agency_copy_email);
            }
        }

        return $address;
    }
}

if (! function_exists('expire_past_shifts')) {
    /**
     * Mark shifts whose date has gone by as expired.
     *
     * Shared by the CLI command and its web-triggered twin so the two cannot
     * drift apart. Only Pending (0) and Live (1) shifts are touched: a Closed
     * shift (3) has been booked and keeps its status, and an already-expired
     * shift (4) is skipped - which is what stops the job rewriting every past
     * row on every run, as it used to.
     *
     * @return int rows changed
     */
    function expire_past_shifts(): int
    {
        $db = ci_db();

        $db->query(
            'UPDATE post_job
                SET p_approved = 4, p_status = 0, modified = ?
              WHERE p_approved IN (0, 1)
                AND p_date_start IS NOT NULL
                AND p_date_start < ?',
            [date('Y-m-d H:i:s'), date('Y-m-d')]
        );

        return $db->affectedRows();
    }
}

if (! function_exists('send_shift_reminders')) {
    /**
     * E-mail every applicant booked for a shift tomorrow.
     *
     * Shared by the `shifts:remind` command and its web-triggered twin. The
     * `sj_reminder_sent_at` stamp is what makes a second run in the same day a
     * no-op, so it is written whether or not the send itself succeeded - a
     * failed send is reported through the admin panel rather than retried here,
     * which would otherwise mail the applicant repeatedly.
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    function send_shift_reminders(?string $forDate = null): array
    {
        $db     = ci_db();
        $target = $forDate ?? date('Y-m-d', strtotime('+1 day'));
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        if (! $db->fieldExists('sj_reminder_sent_at', 'stu_saved_applied_jobs')) {
            $result['skipped'] = -1; // migration not run yet

            return $result;
        }

        $rows = $db->query(
            'SELECT ssa.sj_id, ssa.u_id, ssa.agency_id, ssa.p_id
               FROM stu_saved_applied_jobs ssa
               JOIN post_job pj ON pj.p_id = ssa.p_id
              WHERE ssa.sj_is_approved = 1
                AND ssa.sj_reminder_sent_at IS NULL
                AND pj.p_date_start = ?',
            [$target]
        )->getResult();

        foreach ($rows as $row) {
            $applicant = $db->table('users')->getWhere(['u_id' => $row->u_id])->getRowArray();
            $employer  = $db->table('users')->getWhere(['u_id' => $row->agency_id])->getRowArray();
            $shift     = $db->table('post_job')->getWhere(['p_id' => $row->p_id])->getRowArray();

            if ($applicant === null || $employer === null || $shift === null) {
                $result['skipped']++;

                continue;
            }

            // An opted-out applicant is stamped as handled, not retried:
            // leaving sj_reminder_sent_at NULL would put them back on this
            // list every night for as long as the opt-out stands.
            if (! userAllowsEmail($applicant, 'shift-reminder')) {
                $db->table('stu_saved_applied_jobs')
                    ->where('sj_id', $row->sj_id)
                    ->update(['sj_reminder_sent_at' => date('Y-m-d H:i:s')]);

                $result['skipped']++;

                continue;
            }

            // The branch the shift is at, not the employer's login address -
            // for a multi-store owner the latter is a head office, and this is
            // the message somebody reads the night before travelling to it.
            $store = shiftStore((object) $shift);

            $body = email_body('shift-reminder', [
                'title'    => 'Your shift is tomorrow',
                'name'     => $applicant['u_fname'] . ' ' . $applicant['u_lname'],
                'shift'    => $shift,
                'employer' => $employer,
                'store'    => $store,
            ]);

            $ok = send_email(
                $applicant['u_email'],
                'Reminder: your shift tomorrow at ' . (($store && $store->s_name !== '') ? $store->s_name : $employer['u_comp_name']),
                $body
            );

            $db->table('stu_saved_applied_jobs')
                ->where('sj_id', $row->sj_id)
                ->update(['sj_reminder_sent_at' => date('Y-m-d H:i:s')]);

            $ok ? $result['sent']++ : $result['failed']++;
        }

        return $result;
    }
}

if (! function_exists('email_body')) {
    /**
     * Render a message body from `app/Views/emails/`.
     *
     * Every e-mail body in this application is a template - none are built as
     * HTML strings in a controller. `$settings` is supplied here so no caller
     * has to remember to pass it; the layout needs it for the site name and the
     * support address.
     *
     * @param string $template name under app/Views/emails, without .php
     */
    function email_body(string $template, array $data = []): string
    {
        $data['settings'] ??= model('App\Models\CustomModel')->getSettings();

        return view('emails/' . $template, $data);
    }
}

if (! function_exists('userAllowsEmail')) {
    /**
     * May this user be sent this e-mail template?
     *
     * The one question every guarded send site asks, so the answer lives in
     * one place. `$user` is a `users` row (array or object) or a bare u_id -
     * the id form queries, so callers that already hold the row should pass
     * it. Works from the nightly cron too: nothing here touches a controller.
     *
     * A template that is not in `emailTypes` - reset-password, contact, test -
     * is always allowed: the list is what an administrator may switch off,
     * not a register of everything the application sends.
     *
     * @param array|object|int|string $user
     */
    function userAllowsEmail($user, string $template): bool
    {
        $code = 0;

        foreach ((array) config('AppSettings')->emailTypes as $typeCode => $type) {
            if ($type['template'] === $template) {
                $code = (int) $typeCode;
                break;
            }
        }

        if ($code === 0) {
            return true; // not an optional e-mail
        }

        if (is_numeric($user)) {
            $row  = ci_db()->table('users')->select('u_email_blocked')->where('u_id', (int) $user)->get()->getRow();
            $user = $row ?: [];
        }

        $user    = (object) $user;
        $blocked = array_map('intval', array_filter(explode(',', (string) ($user->u_email_blocked ?? '')), 'strlen'));

        return ! in_array($code, $blocked, true);
    }
}

if (! function_exists('emailTypesFor')) {
    /**
     * The optional e-mails this user's account can actually be sent.
     *
     * `emailTypes` is the whole list; a given account is only ever a recipient
     * of part of it. An applicant is never told "your shift is live" and never
     * gets the employer's half of a booking; an employer is never sent the
     * applicant's half or the day-before reminder they would not be working.
     * Manage Email offered every account all six, so half the boxes on any one
     * screen governed mail that could not arrive whichever way they were left.
     *
     * An administrator is a recipient of none of them - the messages here are
     * about registering, being approved, posting a shift and being booked on
     * one - so this returns an empty list for that account rather than a screen
     * of switches that do nothing. The agency's copy of a booking is a setting
     * (`getAgencyCopyEmail()`), not an account, so it is not governed here.
     *
     * @param array|object $user a `users` row
     * @return array<int, array{template: string, label: string, audience: string}>
     */
    function emailTypesFor($user): array
    {
        $userType = (int) (is_object($user) ? ($user->u_usertype ?? -1) : ($user['u_usertype'] ?? -1));

        $side = $userType === 1 ? 'employer' : ($userType === 2 ? 'applicant' : '');

        if ($side === '') {
            return [];
        }

        return array_filter(
            (array) config('AppSettings')->emailTypes,
            static fn ($type) => in_array($type['audience'] ?? 'both', ['both', $side], true)
        );
    }
}

if (! function_exists('send_email')) {
    /**
     * Send an HTML e-mail. Returns true on success, false (and a log entry) on
     * failure - same contract as the CI3 version, plus optional copies.
     *
     * @param string|string[]|null $cc  visible to the recipient
     * @param string|string[]|null $bcc hidden from the recipient
     */
    function send_email($to, $subject, $message, $cc = null, $bcc = null)
    {
        $settings = config('AppSettings');

        $email = service('email');

        // Protocol, host and credentials come from app/Config/Email.php, which
        // reads them from .env. Nothing here may override the protocol: this
        // used to force bare mail(), which fails SPF/DKIM for the sending
        // domain and quietly lands booking mail in spam.
        $email->initialize([
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'newline'  => "\r\n",
            'CRLF'     => "\r\n",
        ]);

        $email->setFrom($settings->mailFromEmail, $settings->mailFromName);
        $email->setTo($to);

        // A blank address switches the copy off; one that is already a
        // recipient is dropped rather than delivered twice.
        $split = static function ($value): array {
            $list = is_array($value) ? $value : explode(',', (string) $value);

            return array_values(array_filter(array_map('trim', $list), 'strlen'));
        };

        $recipients = array_map('strtolower', $split($to));

        $copies = static function ($value) use ($split, $recipients): array {
            return array_values(array_filter(
                $split($value),
                static fn ($address) => ! in_array(strtolower($address), $recipients, true)
            ));
        };

        if (($ccList = $copies($cc)) !== []) {
            $email->setCC($ccList);
        }

        if (($bccList = $copies($bcc)) !== []) {
            $email->setBCC($bccList);
        }

        $email->setSubject($subject);
        $email->setMessage($message);

        // A multipart message with a text alternative scores better with spam
        // filters than HTML alone, and is what a text-only client falls back to.
        $email->setAltMessage(trim(html_entity_decode(strip_tags((string) $message), ENT_QUOTES, 'UTF-8')));

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
