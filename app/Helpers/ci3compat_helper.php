<?php

/**
 * CodeIgniter 3 compatibility helpers.
 *
 * The PickAShift application was migrated from CodeIgniter 3. These functions
 * provide the handful of CI3 idioms the application code relies on
 * (`$this->custom`, `$this->config->item()`, `$this->uri->segment()`,
 * `redirect()` that halts, `validation_errors()`), implemented on top of the
 * CodeIgniter 4 services so the ported code reads the same as before.
 */

use App\Controllers\BaseController;
use App\Models\CustomModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\ValidationInterface;
use Config\Database;

if (! function_exists('get_instance')) {
    /**
     * CI3 `get_instance()`: the controller handling the current request.
     *
     * Returns null on CLI/spark runs where no controller was instantiated.
     */
    function get_instance(): ?BaseController
    {
        return BaseController::instance();
    }
}

if (! function_exists('custom')) {
    /**
     * Shared instance of the generic data model (CI3: `$this->custom`).
     */
    function custom(): CustomModel
    {
        static $model = null;

        if ($model === null) {
            $model = new CustomModel();
        }

        return $model;
    }
}

if (! function_exists('ci_db')) {
    /**
     * Shared database connection (CI3: `$this->db`).
     */
    function ci_db(): BaseConnection
    {
        return Database::connect();
    }
}

if (! function_exists('config_item')) {
    /**
     * CI3 `$this->config->item()` for the application lookup lists.
     *
     * @return mixed|null
     */
    function config_item(string $key)
    {
        $settings = config('AppSettings');

        return $settings->{$key} ?? null;
    }
}

if (! function_exists('ci_request')) {
    function ci_request(): IncomingRequest
    {
        return service('request');
    }
}

if (! function_exists('uri_segment')) {
    /**
     * CI3 `$this->uri->segment($n)`: 1-based path segment of the current URL,
     * relative to the application root. Returns `$default` when absent.
     *
     * @return mixed
     */
    function uri_segment(int $n, $default = '')
    {
        static $segments = null;

        if ($segments === null) {
            // uri_string() is already relative to baseURL and strips index.php.
            $path = trim(uri_string(), '/');

            $segments = $path === '' ? [] : explode('/', $path);
        }

        return $segments[$n - 1] ?? $default;
    }
}

if (! function_exists('ci_redirect')) {
    /**
     * CI3 `redirect()`: send a Location header and stop the script.
     *
     * CodeIgniter 4's own `redirect()` returns a response object that the
     * controller must return; the ported code calls it mid-method and relies on
     * execution stopping, so this keeps the original semantics.
     */
    function ci_redirect(string $uri = '', string $method = 'auto', int $code = 302): void
    {
        if (! preg_match('#^(\w+:)?//#i', $uri)) {
            $uri = site_url($uri);
        }

        if ($method === 'refresh') {
            header('Refresh:0;url=' . $uri);
        } else {
            header('Location: ' . $uri, true, $code);
        }

        exit;
    }
}

if (! function_exists('ci_validation')) {
    /**
     * Shared validation service (CI3: `$this->form_validation`).
     */
    function ci_validation(): ValidationInterface
    {
        static $validation = null;

        if ($validation === null) {
            $validation = service('validation');
        }

        return $validation;
    }
}

if (! function_exists('validation_errors')) {
    /**
     * CI3 `validation_errors()`: all current validation errors as HTML.
     *
     * NOTE: this intentionally shadows CodeIgniter 4's form-helper function of
     * the same name, which returns an array. The views ported from CI3 echo the
     * result directly, so the CI3 string form is what they need. This file is
     * listed first in Config\Autoload::$helpers to make sure it wins.
     */
    function validation_errors(string $prefix = '<p>', string $suffix = '</p>'): string
    {
        $errors = ci_validation()->getErrors();

        if ($errors === []) {
            return '';
        }

        $out = '';

        foreach ($errors as $error) {
            $out .= $prefix . $error . $suffix . "\n";
        }

        return $out;
    }
}

if (! function_exists('set_value')) {
    /**
     * CI3 `set_value()`: re-populate a form field after a failed submit.
     *
     * @return mixed
     */
    function set_value(string $field, $default = '')
    {
        $value = service('request')->getPost($field);

        return $value ?? $default;
    }
}

if (! function_exists('email_failure_notice')) {
    /**
     * Markup warning that one or more e-mails could not be sent during the
     * previous action. Returns an empty string when everything went out.
     *
     * Sending is fire-and-forget: without this the agency has no way of knowing
     * a notification never left the server.
     */
    function email_failure_notice(): string
    {
        $failures = session()->getTempdata('email_failures');

        if (empty($failures)) {
            return '';
        }

        session()->removeTempdata('email_failures');

        $lines = '';

        foreach ($failures as $failure) {
            $lines .= '<li>' . esc($failure['to']) . ' &mdash; ' . esc($failure['subject']) . '</li>';
        }

        return '<div class="alert alert-warning">'
            . '<strong>Some e-mail could not be sent.</strong> The action itself was saved, but these messages did not leave the server:'
            . '<ul class="mb-0">' . $lines . '</ul>'
            . '</div>';
    }
}

if (! function_exists('flash_message')) {
    /**
     * Convenience wrapper used by the views: `<?= flash_message('error_msg') ?>`.
     */
    function flash_message(string $key = 'error_msg'): string
    {
        return (string) (session()->getFlashdata($key) ?? '');
    }
}
