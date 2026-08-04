<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * CodeIgniter 3 `$this->input` facade over the CI4 request object.
 */
class InputCompat
{
    protected IncomingRequest $request;

    public function __construct()
    {
        $this->request = service('request');
    }

    /**
     * CI3 `$this->input->post()`. With no index the whole POST array is
     * returned (an empty array when nothing was posted).
     *
     * @return mixed
     */
    public function post($index = null, bool $xssClean = false)
    {
        if ($index === null) {
            return $this->request->getPost() ?? [];
        }

        return $this->request->getPost($index);
    }

    /**
     * @return mixed
     */
    public function get($index = null, bool $xssClean = false)
    {
        if ($index === null) {
            return $this->request->getGet() ?? [];
        }

        return $this->request->getGet($index);
    }

    /**
     * @return mixed
     */
    public function post_get($index = null, bool $xssClean = false)
    {
        return $this->post($index) ?? $this->get($index);
    }

    /**
     * @return mixed
     */
    public function get_post($index = null, bool $xssClean = false)
    {
        return $this->get($index) ?? $this->post($index);
    }

    /**
     * @return mixed
     */
    public function server($index = null, bool $xssClean = false)
    {
        if ($index === null) {
            return $this->request->getServer() ?? [];
        }

        return $this->request->getServer($index);
    }

    /**
     * @return mixed
     */
    public function cookie($index = null, bool $xssClean = false)
    {
        if ($index === null) {
            return $this->request->getCookie() ?? [];
        }

        return $this->request->getCookie($index);
    }

    public function ip_address(): string
    {
        return $this->request->getIPAddress();
    }

    public function user_agent(): string
    {
        return (string) $this->request->getUserAgent();
    }

    public function method(bool $upper = false): string
    {
        $method = $this->request->getMethod();

        return $upper ? strtoupper($method) : strtolower($method);
    }

    public function is_ajax_request(): bool
    {
        return $this->request->isAJAX();
    }
}
