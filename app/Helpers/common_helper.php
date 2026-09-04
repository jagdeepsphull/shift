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

if (! function_exists('moreShiftRows')) {
    /**
     * The extra date-and-hours rows from "Add More" on the admin's Add Shift
     * form, paired up by position: the first date with the first hours, and so
     * on. Each pair is one more shift.
     *
     * Paired here rather than trusted to arrive paired: the dates and the hours
     * are posted as two separate arrays, and a hand-edited form can send three
     * of one and two of the other. The odd one out comes back as a row with one
     * half blank - which the caller refuses - rather than being quietly dropped.
     *
     * @param mixed $dates what was posted as `p_more_dates[]`
     * @param mixed $times what was posted as `p_more_shift_time[]`
     *
     * @return array<int, array{date: string, time: string}> as typed, trimmed
     */
    function moreShiftRows($dates, $times): array
    {
        $dates = array_values((array) $dates);
        $times = array_values((array) $times);
        $rows  = [];

        for ($i = 0, $n = max(count($dates), count($times)); $i < $n; $i++) {
            $rows[] = [
                'date' => trim((string) ($dates[$i] ?? '')),
                'time' => trim((string) ($times[$i] ?? '')),
            ];
        }

        return $rows;
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

if (! function_exists('shiftDatePassed')) {
    /**
     * Has the shift's date already gone by?
     *
     * The public site asks this rather than trusting `p_approved`: a shift is
     * off the front end the moment its date passes, whether or not the nightly
     * expiry job has run since. Before this, a site whose cron was never wired
     * up - which is the default, `/cron/expire_jobs` is 404 without a key -
     * listed last month's shifts on the home page indefinitely.
     *
     * Today is not passed. A shift is readable on the morning of the day it is
     * worked, which is the line `expire_past_shifts()` draws too.
     *
     * Read off `p_date_start` alone, exactly as the SQL twin below does, so the
     * list and the shift page never disagree about one row. A shift whose date
     * could not be parsed into that column counts as not passed - it is left
     * showing rather than silently dropped, and `php spark shifts:backfill-dates`
     * lists those rows so they can be given a date.
     *
     * @param array<string, mixed>|object $row a `post_job` row
     */
    function shiftDatePassed($row): bool
    {
        $date = is_object($row) ? ($row->p_date_start ?? null) : ($row['p_date_start'] ?? null);
        $date = $date === null ? '' : substr((string) $date, 0, 10);

        if ($date === '' || $date === '0000-00-00') {
            return false;
        }

        return $date < date('Y-m-d');
    }
}

if (! function_exists('shiftNotPassedSql')) {
    /**
     * `shiftDatePassed()` as a WHERE fragment, for the public shift queries.
     *
     * The day comes from PHP and is bound in rather than written as CURDATE():
     * the database server's clock and PHP's are hours apart on at least one
     * machine here, and a page that mixes the two would hide a shift on one
     * side of midnight that it shows on the other. `expire_past_shifts()` binds
     * the same value for the same reason.
     *
     * @param string $alias table alias to qualify the column with, if any
     *
     * @return array{0: string, 1: list<string>} the SQL fragment and its binds
     */
    function shiftNotPassedSql(string $alias = ''): array
    {
        $column = ($alias === '' ? '' : $alias . '.') . 'p_date_start';

        return ['(' . $column . ' IS NULL OR ' . $column . ' >= ?)', [date('Y-m-d')]];
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

if (! function_exists('setRateRule')) {
    /**
     * Put the hourly-rate rules on a field, on whichever form is asking.
     *
     * Four screens collect a rate - the back office adds and edits a shift, and
     * an employer does the same on their own form - and until this existed not
     * one of them checked it. The `min`, `max` and `step` on the input were the
     * whole of the rule, so anything that reached the controller without a
     * browser in front of it was written as posted: ".334", "3.4.3.4", a rate
     * of 5000, or a third decimal that MySQL then rounded off to something
     * nobody had typed.
     *
     * One function rather than four copies of the rule string, so a rate means
     * the same thing on every screen that asks for one.
     *
     * @param string $field the POST field, e.g. `p_hourly_rate`
     * @param string $label how the field is named back in an error message
     */
    function setRateRule(string $field, string $label): void
    {
        $decimals = RATE_DECIMALS === 1 ? 'one decimal place' : RATE_DECIMALS . ' decimal places';

        get_instance()->form_validation->set_rules(
            $field,
            $label,
            [
                'required',
                // Shape first, so a rate that is not a number at all is told
                // what a rate looks like rather than that it is too small.
                'regex_match[' . RATE_PATTERN . ']',
                'greater_than_equal_to[' . RATE_MIN . ']',
                'less_than_equal_to[' . RATE_MAX . ']',
            ],
            [
                'regex_match'           => 'The {field} must be an amount in dollars, with at most ' . $decimals
                    . ' after a single decimal point - 42, 42.5 or 42.50.',
                'greater_than_equal_to' => 'The {field} must be at least CAD$ ' . RATE_MIN . '.',
                'less_than_equal_to'    => 'The {field} cannot be more than CAD$ ' . RATE_MAX . '.',
            ]
        );
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

        // Two extensions, and both have to be one of the allowed ones.
        //
        // `getClientExtension()` is the tail of the name the browser sent, which
        // the person uploading chooses; `guessExtension()` is derived from what
        // the bytes actually are. Checking only the first let `shell.php.jpg`
        // through as a jpg, and checking only the second turns away a perfectly
        // good .docx whose type is guessed as .zip. Both must agree with the
        // list, except that the office and pdf formats are matched on the
        // client extension alone - their guessed types are containers shared
        // with other formats.
        $extension = strtolower($file->getClientExtension() ?: $file->getExtension());

        if (! in_array($extension, $allowed, true)) {
            return ['error' => 1, 'status' => ['error' => 'The filetype you are attempting to upload is not allowed.']];
        }

        if (in_array($extension, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            // An image has to be one. `getimagesize()` reads the header rather
            // than the name, so a PHP script called `logo.jpg` fails here.
            if (@getimagesize($file->getTempName()) === false) {
                return ['error' => 1, 'status' => ['error' => 'That file is not a readable image.']];
            }
        } else {
            $guessed = strtolower((string) $file->guessExtension());

            // jpe/jpg and the office formats have more than one accepted
            // spelling; a guess that lands on a different one of the pair is
            // still the same file type.
            $sameFamily = [
                'jpg'  => ['jpeg', 'jpe'],
                'jpeg' => ['jpg', 'jpe'],
                'doc'  => ['docx', 'zip', 'bin'],
                'docx' => ['doc', 'zip', 'bin'],
            ];

            $acceptable = array_merge([$extension], $sameFamily[$extension] ?? []);

            if ($guessed !== '' && ! in_array($guessed, $acceptable, true)) {
                return ['error' => 1, 'status' => ['error' => 'That file is not the type its name says it is.']];
            }
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

        // The stored name is built here rather than taken from the browser.
        // `getClientName()` is attacker-controlled: a multipart filename of
        // `../../index.php` would have been passed to move() unchanged, which
        // writes wherever it points. Letters, digits, dash, dot and underscore
        // survive; everything else - slashes included - becomes an underscore,
        // and the extension is the validated one rather than whatever the name
        // ended in.
        $stem = pathinfo((string) $file->getClientName(), PATHINFO_FILENAME);
        $stem = preg_replace('/[^A-Za-z0-9_-]+/', '_', $stem) ?? '';
        $stem = trim(substr($stem, 0, 60), '_-');

        if ($stem === '') {
            $stem = 'file';
        }

        $new_name = time() . '_' . bin2hex(random_bytes(4)) . '_' . $stem . '.' . $extension;

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

if (! function_exists('agreementDoneBadge')) {
    /**
     * The Agreement Done cell for the back-office listings.
     *
     * Applicants, employers, each employer kind and stores all show it, and
     * they show it with the same two words: the office reads down these lists
     * looking for the accounts still to chase, and four wordings would make
     * that four separate readings. Yes/No rather than a tick, because the
     * exports take the cell's text and a tick leaves an empty spreadsheet
     * column - the same reason the Status column spells Active out.
     *
     * @param int|string|null $done the row's `u_agreement_done`
     */
    function agreementDoneBadge($done): string
    {
        return (int) $done === 1
            ? '<span class="badge badge-success">Yes</span>'
            : '<span class="badge badge-secondary">No</span>';
    }
}

if (! function_exists('userAgreementDone')) {
    /**
     * Whether the signed agreement is on file for an account, by id.
     *
     * For the store list, whose rows are locations rather than logins - the
     * agreement belongs to the employer who holds them, so every store of one
     * chain reports the one answer.
     *
     * Unlike getPharmacyName() this asks nothing of the account's status: a
     * pending employer who has already signed should say so, since that is
     * usually the pair the office is looking for.
     *
     * @param int|string $uid
     */
    function userAgreementDone($uid = 0): int
    {
        $row = lookupRow('users', 'u_id', $uid);

        return $row ? (int) ($row->u_agreement_done ?? 0) : 0;
    }
}

if (! function_exists('getCityName')) {
    function getCityName($cid = 0)
    {
        $row = lookupRow('city', 'c_id', $cid, ['c_status' => 1]);

        return $row ? $row->c_name : '';
    }
}

if (! function_exists('getStoreName')) {
    /**
     * The name of a store (location), by its `store.s_id`.
     *
     * A deactivated branch answers as well as an active one - a shift that was
     * raised for it still has to say which branch it was raised for.
     */
    function getStoreName($sid = 0)
    {
        $row = lookupRow('store', 's_id', $sid);

        return $row ? $row->s_name : '';
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
        // Through lookupRow rather than straight at the table, so a listing
        // that calls this once per shift asks for the same store once: the
        // back-office shift list shows an address on every row, and most of
        // those rows are the same handful of branches.
        if (! empty($job->p_store_id)) {
            $store = lookupRow('store', 's_id', $job->p_store_id);

            if ($store) {
                return $store;
            }
        }

        $owner = lookupRow('users', 'u_id', $job->u_id);

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

if (! function_exists('storeAddressLines')) {
    /**
     * A store's address as the lines a listing stacks in one cell - street,
     * then town, then province with postcode.
     *
     * Stacked rather than run together on one line: the column is read down
     * the page looking for a branch, and three short lines are quicker to
     * scan than one long one that wraps where the column happens to end.
     * Only the parts actually filled, or a store with no town or postcode
     * leaves blank lines behind it.
     *
     * Three back-office lists show this same cell - shifts, applications and
     * the dashboard's new-applications panel - so they read it from here
     * rather than each building it and drifting apart.
     *
     * @param object|null $store a `store` row, or shiftStore()'s stand-in
     *
     * @return list<string>
     */
    function storeAddressLines($store): array
    {
        if (! $store) {
            return [];
        }

        return array_values(array_filter([
            trim((string) $store->s_address),
            trim((string) getCityName($store->s_city)),
            trim((string) implode(' ', array_filter([
                trim((string) getProvinceName($store->s_province)),
                trim((string) $store->s_pincode),
            ], static fn ($part) => $part !== ''))),
        ], static fn ($part) => $part !== ''));
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
            return '<span class="text-muted text-nowrap">' . $icon . esc($shown) . '</span>';
        }

        // `text-nowrap` because the listings put this in a column narrow enough
        // to break it, and the mark left on a line of its own reads as a stray
        // green tick rather than as part of the number above it.
        return '<a class="text-nowrap" href="https://web.whatsapp.com/send?phone=' . esc($number, 'attr') . '"'
            . ' target="_blank" rel="noopener noreferrer"'
            . ' title="' . esc($title, 'attr') . '">'
            . $icon . esc($shown)
            . '<i class="fab fa-whatsapp ml-2 text-success"></i></a>';
    }
}

if (! function_exists('whatsappMarkSvg')) {
    /**
     * The WhatsApp glyph, drawn rather than pulled out of an icon font.
     *
     * The portal behind a login loads line-icons, which has no WhatsApp mark,
     * and not Font Awesome, which does - so the two places in that area that
     * need one share this rather than keeping a copy of the path each.
     */
    function whatsappMarkSvg(): string
    {
        return '<svg viewBox="0 0 32 32" role="img" focusable="false" aria-hidden="true">'
            . '<path fill="currentColor" d="M16.03 4A11.9 11.9 0 0 0 4.1 15.9c0 2.1.55 4.15 1.6 5.96L4 28l6.32-1.65a11.87 11.87 0 0 0 5.7 1.45h.01A11.9 11.9 0 0 0 27.95 15.9 11.9 11.9 0 0 0 16.03 4Zm0 21.79h-.01a9.9 9.9 0 0 1-5.03-1.38l-.36-.21-3.75.98 1-3.65-.24-.38a9.86 9.86 0 0 1-1.51-5.25 9.9 9.9 0 1 1 9.9 9.89Zm5.43-7.41c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.07 2.87 1.22 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.42-.07-.13-.27-.2-.57-.35Z"/>'
            . '</svg>';
    }
}

if (! function_exists('portalWhatsappPhoneLink')) {
    /**
     * `whatsappPhoneLink()` for the screens behind a login rather than the back
     * office: the same rules about which numbers can be messaged, in the icons
     * that area actually has.
     *
     * The two cannot be one function because the two areas load different icon
     * fonts. The admin has Font Awesome, which carries both a handset and a
     * WhatsApp mark; the portal has line-icons, which has the handset and no
     * WhatsApp mark at all - so that one is drawn, by whatsappMarkSvg(). What
     * decides whether there is a link at all is whatsappNumber(), which both
     * call, so the two can never disagree about a number.
     *
     * Presentation is `.ps-wa-link` in partials/portal_theme.php.
     */
    function portalWhatsappPhoneLink(?string $phone, string $title = 'Send a WhatsApp message'): string
    {
        $shown = trim((string) $phone);

        if ($shown === '') {
            return '';
        }

        $icon   = '<i class="lni lni-phone-handset" aria-hidden="true"></i>';
        $number = whatsappNumber($shown);

        // Worth reading even when it cannot be messaged - a number too short to
        // be real, or the 0000000000 the back office accepts - so it is printed
        // rather than dropped, and printed as text so nothing opens a chat with
        // nobody.
        if ($number === '') {
            return '<span class="ps-wa-link is-plain">' . $icon . esc($shown) . '</span>';
        }

        return '<a class="ps-wa-link" href="https://web.whatsapp.com/send?phone=' . esc($number, 'attr') . '"'
            . ' target="_blank" rel="noopener noreferrer"'
            . ' title="' . esc($title, 'attr') . '">'
            . $icon . '<span class="ps-wa-number">' . esc($shown) . '</span>'
            . '<span class="ps-wa-mark">' . whatsappMarkSvg() . '</span></a>';
    }
}

if (! function_exists('landlinePhoneLink')) {
    /**
     * A phone number written as a dialling link and nothing else: a handset
     * icon in front of it, and no WhatsApp mark after.
     *
     * For the numbers that are not somebody's mobile. A store's line is the
     * counter phone; WhatsApp would resolve those digits as readily as any
     * other and offer a chat that nobody is ever going to open, so the mark is
     * left off rather than shown and hoped for.
     *
     * As with `whatsappPhoneLink()`, the number shown is the one on file,
     * punctuation and all - only the one inside the link is reduced to digits -
     * and a number with no digits in it at all is printed as plain text.
     */
    function landlinePhoneLink(?string $phone, string $title = 'Call this number'): string
    {
        $shown = trim((string) $phone);

        if ($shown === '') {
            return '';
        }

        $icon   = '<i class="fas fa-phone display-25 mr-1 text-secondary"></i>';
        $dialed = preg_replace('/[^0-9+]/', '', $shown);

        if (preg_replace('/\D+/', '', $dialed) === '') {
            return '<span class="text-muted">' . $icon . esc($shown) . '</span>';
        }

        return '<a href="tel:' . esc($dialed, 'attr') . '"'
            . ' title="' . esc($title, 'attr') . '">'
            . $icon . esc($shown) . '</a>';
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

if (! function_exists('mapSearchLink')) {
    /**
     * A Google Maps search for a place written as text.
     *
     * Every map link on the site is built here, so one made from a town reads
     * and behaves the same as one made from a street address.
     */
    function mapSearchLink(string $place): string
    {
        // A trailing comma from a place built out of parts, some of them empty.
        $place = trim(trim($place), ',');

        return $place === ''
            ? ''
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($place);
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

        return mapSearchLink(implode(', ', $parts));
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

if (! function_exists('employerNameTaken')) {
    /**
     * Is this employer name already on another account?
     *
     * `u_comp_name` is the corporate group's name for an owner and the store's
     * name for a single location - one column either way, and the label every
     * screen shows an employer by: the employer listing, the employer dropdown
     * on both shift forms, the store picker and the booking e-mails. Two
     * accounts sharing it leaves all of them naming two different companies
     * identically, so registration and the back-office employer form both
     * refuse a name that is taken, from this one lookup.
     *
     * An account still waiting for the administrator counts, the same way
     * `storeManagers()` treats a pending manager as holding their store: it has
     * claimed the name, and freeing it means removing the account.
     *
     * A manager does not. The name on their row is a copy of the store they
     * run - `storeSnapshotForManager()` puts it there - not a name they chose,
     * so it belongs to their owner's group and counting it would refuse an
     * owner the very name their own branch is listed under.
     *
     * Compared without case or surrounding spaces, so "Acme Pharmacy" cannot be
     * registered again as " acme pharmacy ".
     *
     * @param int $ignoreUserId the account being edited, so re-saving it is not
     *                          rejected as a duplicate of itself
     */
    function employerNameTaken(?string $name, int $ignoreUserId = 0): bool
    {
        $name = trim((string) $name);

        // Nothing typed is a job for `required`, not for this - and a blank
        // must never read as a duplicate of another blank row.
        if ($name === '') {
            return false;
        }

        $builder = ci_db()->table('users')
            ->where('u_usertype', 1)
            ->where('u_emp_role !=', 2)
            ->where('LOWER(TRIM(u_comp_name))', strtolower($name));

        if ($ignoreUserId > 0) {
            $builder->where('u_id !=', $ignoreUserId);
        }

        return $builder->countAllResults() > 0;
    }
}

if (! function_exists('employerNameRule')) {
    /**
     * The "not somebody else's name already" rule, for `set_rules()`.
     *
     * A closure rather than `is_unique[users.u_comp_name]` for two reasons: the
     * comparison has to ignore case and spacing, and the back-office edit form
     * has to let an account keep the name it already has. Registration and both
     * back-office forms take it from here so they cannot drift apart.
     *
     * @param string $label   what the form calls the field - "Corporate Group
     *                        Name" for an owner, "Store Name" for one location
     * @param int    $ignoreUserId the account being edited, if any
     */
    function employerNameRule(string $label, int $ignoreUserId = 0): callable
    {
        return static function ($value, $data, ?string &$error) use ($label, $ignoreUserId): bool {
            if (! employerNameTaken(is_string($value) ? $value : null, $ignoreUserId)) {
                return true;
            }

            $error = 'This ' . $label . ' is already registered to another account. Please enter a different one.';

            return false;
        };
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

if (! function_exists('storeManagers')) {
    /**
     * The manager account on each of these stores, with who they are.
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
     * `u_status` comes back with the row so a caller that shows the person to an
     * owner can say the account is not approved yet, rather than naming somebody
     * who cannot log in.
     *
     * @param array<int, int|string> $storeIds
     *
     * @return array<int, object> `store.s_id` => the `users` row of its manager
     */
    function storeManagers(array $storeIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $storeIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        // The opt-out columns are on this select because the row goes straight
        // to shiftPostedRecipients(), which asks whether this manager may be
        // written to. A row without `u_email_blocked` answers "nothing is
        // blocked" rather than admitting it does not know - which is how a
        // manager who switched "your shift is live" off in Manage Email carried
        // on being sent it. The unsubscribe columns are guarded because this
        // query runs on screens that must still work before the migration.
        $columns = 'u_id, u_store_id, u_fname, u_lname, u_email, u_phone, u_status, u_email_blocked';

        if (unsubscribeReady()) {
            $columns .= ', u_unsubscribed_at, u_unsub_token';
        }

        $rows = ci_db()->table('users')
            ->select($columns)
            ->where('u_usertype', 1)
            ->where('u_emp_role', 2)
            ->whereIn('u_store_id', $ids)
            ->orderBy('u_id', 'asc')
            ->get()
            ->getResult();

        $managers = [];

        foreach ($rows as $row) {
            // The first one there is the answer. On a database that somehow
            // holds two for a store, it is taken either way.
            $managers[(int) $row->u_store_id] ??= $row;
        }

        return $managers;
    }
}

if (! function_exists('storeManagerIds')) {
    /**
     * Which of these stores already have a manager on them, and who.
     *
     * The same lookup as `storeManagers()`, for callers that only need to know
     * whether a store is spoken for.
     *
     * @param array<int, int|string> $storeIds
     *
     * @return array<int, int> `store.s_id` => `users.u_id` of the manager on it
     */
    function storeManagerIds(array $storeIds): array
    {
        return array_map(
            static fn (object $manager): int => (int) $manager->u_id,
            storeManagers($storeIds)
        );
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
     * drift apart. Only Pending (0) and Open (1) shifts are touched: a Booked
     * shift (3) has somebody on it and keeps its status, and an already-expired
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

if (! function_exists('unsubscribeReady')) {
    /**
     * Has the unsubscribe migration been run?
     *
     * Every function below asks this first, so a deploy that uploads the code
     * before `php spark migrate` sends e-mail without an unsubscribe link
     * rather than fataling on a missing column halfway through a booking. The
     * answer is looked up once per request: `fieldExists()` describes the
     * table, and this is on the path of every send.
     */
    function unsubscribeReady(): bool
    {
        static $ready = null;

        return $ready ??= ci_db()->fieldExists('u_unsubscribed_at', 'users');
    }
}

if (! function_exists('userHasUnsubscribed')) {
    /**
     * Has this user opted out of everything from their inbox?
     *
     * `$user` is a `users` row (array or object) or a bare u_id. A row that was
     * selected without the column - an older query, or one written before the
     * migration - is looked up by id rather than assumed to be subscribed:
     * guessing wrong here means mailing somebody who asked us not to.
     *
     * @param array|object|int|string $user
     */
    function userHasUnsubscribed($user): bool
    {
        if (is_numeric($user)) {
            if (! unsubscribeReady()) {
                return false;
            }

            $row = ci_db()->table('users')->select('u_unsubscribed_at')->where('u_id', (int) $user)->get()->getRow();

            return $row !== null && (string) $row->u_unsubscribed_at !== '';
        }

        $user = (object) $user;

        // A row that carries the column answers by itself. The schema probe
        // guards the queries below and nothing else on purpose: deciding
        // whether to honour an opt-out by asking the database what shape it is
        // makes the answer "no" on every connection where that check cannot be
        // made - which is fail-open, on the one question that must not be.
        if (property_exists($user, 'u_unsubscribed_at')) {
            return (string) $user->u_unsubscribed_at !== '';
        }

        return isset($user->u_id) ? userHasUnsubscribed((int) $user->u_id) : false;
    }
}

if (! function_exists('unsubscribeToken')) {
    /**
     * The secret in this user's unsubscribe link, made on first use.
     *
     * Stable for the life of the account, so the link in an e-mail sent a year
     * ago still works - somebody digging out an old message to get off the list
     * is exactly who this is for. Re-subscribing does not rotate it either:
     * that would strand every link already in their mailbox, and the token
     * grants nothing but the choice they already had.
     *
     * Returns '' when the row has no id to write against, which is what tells
     * the caller to render no link at all.
     *
     * @param array|object $user a `users` row
     */
    function unsubscribeToken($user): string
    {
        $user  = (object) $user;
        $id    = (int) ($user->u_id ?? 0);
        $token = trim((string) ($user->u_unsub_token ?? ''));

        // Already on the row - the ordinary case once the backfill has run, and
        // no reason to touch the database to confirm it.
        if ($token !== '') {
            return $token;
        }

        if ($id === 0 || ! unsubscribeReady()) {
            return '';
        }

        // Not on the row - either a partial select or a user who predates the
        // backfill. Read it back before minting a new one, so two e-mails sent
        // in the same second do not leave the first one's link dead.
        $row   = ci_db()->table('users')->select('u_unsub_token')->where('u_id', $id)->get()->getRow();
        $token = trim((string) ($row->u_unsub_token ?? ''));

        if ($token === '') {
            $token = bin2hex(random_bytes(16));
            ci_db()->table('users')->where('u_id', $id)->update(['u_unsub_token' => $token]);
        }

        return $token;
    }
}

if (! function_exists('unsubscribeUrl')) {
    /**
     * Where the Unsubscribe link in an e-mail points.
     *
     * A landing page, not the opt-out itself: mail clients and security
     * scanners fetch the links in a message before anybody reads it, and a URL
     * that unsubscribes on GET opts people out who never clicked anything. The
     * page asks, and the POST behind its button is what acts.
     *
     * @param array|object $user a `users` row
     */
    function unsubscribeUrl($user): string
    {
        $token = unsubscribeToken($user);

        return $token === '' ? '' : base_url('unsubscribe/' . $token);
    }
}

if (! function_exists('userByEmail')) {
    /**
     * The account an outgoing address belongs to, or null.
     *
     * How `send_email()` works out whose unsubscribe link to put in a message.
     * It goes by address because that is all a send site is guaranteed to pass
     * - the alternative is threading a `users` row through all thirteen of
     * them, and the one that forgets is an e-mail with no way out of the list.
     *
     * A shared address returns the first account on it. That is already the
     * site's answer everywhere else - two accounts on one mailbox cannot be
     * told apart by anything in an e-mail - and the link still opts out an
     * account that reads that inbox.
     */
    function userByEmail(string $address): ?object
    {
        $address = trim($address);

        if ($address === '' || ! unsubscribeReady()) {
            return null;
        }

        return ci_db()->table('users')
            ->select('u_id, u_email, u_unsub_token, u_unsubscribed_at')
            ->where('u_email', $address)
            ->orderBy('u_id', 'asc')
            ->get(1)
            ->getRow();
    }
}

if (! function_exists('apply_unsubscribe_link')) {
    /**
     * Fill in - or cut out - the layout's unsubscribe block.
     *
     * `app/Views/emails/layout.php` leaves the block in every message it
     * renders, marked off by comments, with `{{unsubscribe_url}}` where the
     * address goes. It cannot fill it in itself: a template is rendered once
     * and the shift-posted e-mail sends that one body to both the owner and the
     * manager, who need different links.
     *
     * An empty `$url` removes the block rather than leaving a dead link. That
     * is the right outcome for the messages that are not to an account at all -
     * the contact form landing on the administrator, the agency's copy of a
     * booking, `php spark email:test` - none of which are a subscription
     * anybody can be removed from.
     */
    function apply_unsubscribe_link(string $message, string $url): string
    {
        if ($url === '') {
            return (string) preg_replace('/<!--\[unsubscribe\]-->.*?<!--\[\/unsubscribe\]-->/s', '', $message);
        }

        // esc() in its HTML context, not 'attr'. The attribute escaper encodes
        // every non-alphanumeric byte, which turns the `:` and `/` of a perfectly
        // ordinary URL into `&#x3A;&#x2F;&#x2F;` - something a browser decodes
        // but plenty of mail clients show, or refuse to make a link of at all.
        // This URL is built here from base_url() and a hex token, so the only
        // characters that need handling are the ones that would end the
        // attribute, which is exactly what this escapes.
        $message = str_replace('{{unsubscribe_url}}', esc($url), $message);

        return str_replace(['<!--[unsubscribe]-->', '<!--[/unsubscribe]-->'], '', $message);
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
     * That same list is the reach of the Unsubscribe link, and for the same
     * reason. Somebody who has opted out of everything is still sent the
     * password reset they just asked for and the notice that a booking they
     * were counting on is cancelled - the two messages whose absence hurts the
     * person the opt-out was meant to protect. Both are already documented as
     * always-sent in Config\AppSettings; unsubscribing does not change which
     * e-mails exist, only which of the optional ones are sent.
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
            $columns = 'u_id, u_email_blocked' . (unsubscribeReady() ? ', u_unsubscribed_at' : '');
            $row     = ci_db()->table('users')->select($columns)->where('u_id', (int) $user)->get()->getRow();
            $user    = $row ?: [];
        }

        // The recipient's own opt-out outranks anything set on their behalf:
        // the per-type boxes on Manage Email, and the Owner and Manager ticks
        // on the shift form. Those ticks are an administrator saying who at the
        // store to tell, which is not a permission the store's owner gave - so
        // a ticked side that has unsubscribed drops out here, comes back as
        // `missing` from shiftPostedRecipients(), and the shift is still
        // announced to the fallback address.
        if (userHasUnsubscribed($user)) {
            return false;
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

        // The Unsubscribe link goes in here, not at the call sites. This is the
        // only place that knows the one address the message is about to go to,
        // and the shift-posted send hands the same rendered body to both the
        // owner and the manager - who need different links. A message going to
        // more than one address at once gets no link rather than one that opts
        // out whichever of them the lookup happened to find first.
        $recipient = count($recipients) === 1 ? userByEmail($recipients[0]) : null;
        $unsubUrl  = $recipient !== null ? unsubscribeUrl($recipient) : '';
        $message   = apply_unsubscribe_link((string) $message, $unsubUrl);

        if ($unsubUrl !== '') {
            // RFC 8058. Gmail and Yahoo both want a one-click unsubscribe on
            // bulk mail, and the header is what puts the client's own
            // Unsubscribe button at the top of the message - next to the button
            // that reports it as spam, which is the other way off the list and
            // the one that costs the domain its reputation.
            $email->setHeader('List-Unsubscribe', '<' . $unsubUrl . '>');
            $email->setHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        $email->setMessage($message);

        // A multipart message with a text alternative scores better with spam
        // filters than HTML alone, and is what a text-only client falls back to.
        $alt = trim(html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8'));

        if ($unsubUrl !== '') {
            // strip_tags keeps the word "Unsubscribe" and throws away the href,
            // so without this the text half of the message offers a way out
            // that is not a link to anywhere.
            $alt .= "\n\nUnsubscribe: " . $unsubUrl;
        }

        $email->setAltMessage($alt);

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

if (! function_exists('shiftEmailChoice')) {
    /**
     * The two sides a shift's "your shift is live" e-mail can be sent to.
     *
     * The words the shift form posts and `post_job.p_email_to` stores. They are
     * here rather than in AppSettings because nothing may ever add a third: the
     * form is asking about a store, and a store has an owner and a manager.
     *
     * @return array<int, string>
     */
    function shiftEmailSides(): array
    {
        return ['owner', 'manager'];
    }

    /**
     * Read a posted or stored choice back as a clean list.
     *
     * Accepts what the form posts (an array of ticked boxes) and what the
     * column holds (a comma separated string), so the form, the save and the
     * send site all read it the same way. Anything not one of the two sides is
     * dropped - the column is small and this is the only thing that writes it,
     * but a hand-made post is not going to decide who gets mail.
     *
     * @param mixed $choice
     *
     * @return array<int, string>
     */
    function shiftEmailChoice($choice): array
    {
        $parts = is_array($choice) ? $choice : explode(',', (string) $choice);

        $parts = array_map(
            static fn ($part): string => strtolower(trim((string) $part)),
            $parts
        );

        // array_values so the result is a list: it is stored with implode and
        // compared with in_array, and neither wants the original keys.
        return array_values(array_intersect(shiftEmailSides(), $parts));
    }
}

if (! function_exists('shiftPostedRecipients')) {
    /**
     * Who is actually sent "your shift is live", and why.
     *
     * The shift names a side - the store's owner, its manager, both or neither
     * - and this turns that into addresses. Kept pure and out of the
     * controller: it is the one piece of the send that has to be right, and the
     * cases that go wrong are the quiet ones (a store with no manager, a
     * recipient who opted out) rather than anything a screen would show.
     *
     * A side that was ticked but cannot be reached is not silently dropped: it
     * comes back in `missing`, so the caller can log which of them it was.
     *
     * The configured address is on every one of these e-mails, whoever else is:
     * it is the site's own record that a shift went live, and the shift form
     * shows it as a recipient that cannot be unticked. When it is the only one
     * left - nobody ticked, or nobody reachable - `fellBack` says so, which is
     * what the caller logs. The e-mail always goes somewhere; a shift going
     * live unannounced is the one outcome to avoid.
     *
     * @param object|null $owner   the `users` row that owns the store
     * @param object|null $manager the `users` row running it, if any
     *
     * @return array{to: array<int, string>, missing: array<int, string>, fellBack: bool}
     */
    function shiftPostedRecipients(?object $owner, ?object $manager, $choice, string $fallback): array
    {
        $wanted  = shiftEmailChoice($choice);
        $to      = [];
        $missing = [];

        foreach ([['owner', $owner], ['manager', $manager]] as [$side, $user]) {
            if (! in_array($side, $wanted, true)) {
                continue;
            }

            $email = trim((string) ($user->u_email ?? ''));

            if ($user === null || $email === '') {
                // Nobody on that side of the store, which is normal for a
                // manager and means the tick cannot be honoured.
                $missing[] = $side;

                continue;
            }

            if (! userAllowsEmail($user, 'shift-posted')) {
                $missing[] = $side;

                continue;
            }

            $to[] = $email;
        }

        // Both sides of a store can be the same login on a small chain.
        $to = array_values(array_unique($to));

        // Nobody reachable on the store's side is still worth saying in the
        // log, even though the address below means something is always sent.
        $fellBack = $to === [];
        $fallback = trim($fallback);

        // Last, so the store's own people stay at the head of the list, and
        // only once when the configured address is also a store's login.
        if ($fallback !== '' && ! in_array($fallback, $to, true)) {
            $to[] = $fallback;
        }

        return ['to' => $to, 'missing' => $missing, 'fellBack' => $fellBack];
    }
}
