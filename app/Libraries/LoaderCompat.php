<?php

namespace App\Libraries;

/**
 * CodeIgniter 3 `$this->load` facade.
 *
 * The PickAShift application had a `MY_Loader` with one helper per layout
 * (front / admin / applicant / employer / e-mail). Those methods are reproduced
 * here on top of CodeIgniter 4's `view()` so the controllers keep calling
 * `$this->load->front_view('index', $this->data, 1)` unchanged.
 */
class LoaderCompat
{
    /**
     * Render a view. Echoes it, or returns it when `$return` is true
     * (CI3 signature).
     *
     * @return string|null
     */
    public function view(string $name, array $data = [], bool $return = false)
    {
        $output = view($name, $data);

        if ($return) {
            return $output;
        }

        echo $output;

        return null;
    }

    /**
     * CI3 MY_Loader::template()
     */
    public function template(string $template_name, array $vars = [], bool $return = false)
    {
        if ($return) {
            return view('templates/header', $vars)
                . view($template_name, $vars)
                . view('templates/footer', $vars);
        }

        echo view('templates/header', $vars);
        echo view($template_name, $vars);
        echo view('templates/footer', $vars);

        return null;
    }

    /**
     * Admin layout. `$login == 1` renders the bare page (login screen).
     */
    public function admin_view(string $template_name, array $data = [], $login = ''): void
    {
        if ((string) $login === '1') {
            echo view('admin/' . $template_name, $data);

            return;
        }

        echo view('admin/header', $data);
        echo view('admin/' . $template_name, $data);
        echo view('admin/footer', $data);
    }

    /**
     * E-mail body. Returns the rendered template (CI3 echoed it, and the two
     * callers assigned the result, so returning it is what they expected).
     */
    public function email_view(string $template_name, array $data = [], $type = ''): string
    {
        return view('emails/' . $template_name, $data);
    }

    /**
     * Public site layout. `$type == 1` uses the home header, otherwise the
     * inner-page header.
     */
    public function front_view(string $template_name, array $data = [], $type = ''): void
    {
        $header = ((string) $type === '1') ? 'front/header_home' : 'front/header_inner';

        echo view($header, $data);
        echo view('front/' . $template_name, $data);
        echo view('front/footer', $data);
    }

    public function applicant_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('applicant/' . $template_name, $data);

            return;
        }

        echo view('applicant/header_home', $data);
        echo view('applicant/' . $template_name, $data);
        echo view('applicant/footer', $data);
    }

    public function applicant_inner_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('applicant/' . $template_name, $data);

            return;
        }

        echo view('applicant/header_inner', $data);
        echo view('applicant/' . $template_name, $data);
        echo view('applicant/footer', $data);
    }

    public function owner_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('employer/' . $template_name, $data);

            return;
        }

        echo view('employer/header_home', $data);
        echo view('employer/' . $template_name, $data);
        echo view('employer/footer', $data);
    }

    public function owner_inner_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('employer/' . $template_name, $data);

            return;
        }

        echo view('employer/header_inner', $data);
        echo view('employer/' . $template_name, $data);
        echo view('employer/footer', $data);
    }

    public function foregin_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('foregin_employer/' . $template_name, $data);

            return;
        }

        echo view('foregin_employer/header_home', $data);
        echo view('foregin_employer/' . $template_name, $data);
        echo view('foregin_employer/footer', $data);
    }

    public function foregin_inner_view(string $template_name, array $data = [], $type = ''): void
    {
        if ((string) $type === '1') {
            echo view('foregin_employer/' . $template_name, $data);

            return;
        }

        echo view('foregin_employer/header_inner', $data);
        echo view('foregin_employer/' . $template_name, $data);
        echo view('foregin_employer/footer', $data);
    }

    /**
     * CI3 `$this->load->helper()` - CI4 loads helpers with the same name.
     *
     * @param array|string $helpers
     */
    public function helper($helpers = []): void
    {
        helper($helpers);
    }

    /**
     * CI3 `$this->load->model()` - returns the CI4 model instance.
     */
    public function model(string $name, string $objectName = '', $connection = false)
    {
        return model($name);
    }

    /**
     * CI3 `$this->load->library()`. The libraries this application loaded
     * (`email`, `upload`, `session`, `form_validation`) are always available in
     * CI4, so this is a no-op kept for call-site compatibility.
     *
     * @param array|string $library
     */
    public function library($library = '', $params = null, ?string $objectName = null): void
    {
        // Intentionally empty - CI4 resolves these through services.
    }

    /**
     * CI3 `$this->load->database()`.
     */
    public function database($params = '', bool $return = false, ?bool $queryBuilder = null)
    {
        return $return ? db_connect() : null;
    }
}
