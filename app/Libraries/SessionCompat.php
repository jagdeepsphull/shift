<?php

namespace App\Libraries;

use CodeIgniter\Session\Session;

/**
 * CodeIgniter 3 `$this->session` facade over the CI4 session service.
 *
 * CI4 property access (`$session->userId`) and the CI4 method names still work
 * through `__get()` / `__call()`, so both styles can be mixed while the code
 * base is being modernised.
 */
class SessionCompat
{
    protected Session $session;

    public function __construct()
    {
        $this->session = session();
    }

    /**
     * @return mixed
     */
    public function userdata($key = null)
    {
        if ($key === null) {
            return $this->session->get();
        }

        return $this->session->get($key);
    }

    /**
     * CI3 allowed `set_userdata('key', 'value')` and `set_userdata([...])`.
     *
     * @param array|string $data
     * @param mixed        $value
     */
    public function set_userdata($data, $value = null): void
    {
        if (is_array($data)) {
            $this->session->set($data);

            return;
        }

        $this->session->set($data, $value);
    }

    /**
     * @param array|string $key
     */
    public function unset_userdata($key): void
    {
        $this->session->remove($key);
    }

    /**
     * @return mixed
     */
    public function flashdata($key = null)
    {
        if ($key === null) {
            return $this->session->getFlashdata();
        }

        return $this->session->getFlashdata($key);
    }

    /**
     * @param array|string $data
     * @param mixed        $value
     */
    public function set_flashdata($data, $value = null): void
    {
        if (is_array($data)) {
            $this->session->setFlashdata($data);

            return;
        }

        $this->session->setFlashdata($data, $value);
    }

    /**
     * @param array|string $data
     * @param mixed        $value
     */
    public function set_tempdata($data, $value = null, int $ttl = 300): void
    {
        $this->session->setTempdata($data, $value, $ttl);
    }

    /**
     * @return mixed
     */
    public function tempdata($key = null)
    {
        return $this->session->getTempdata($key);
    }

    public function has_userdata(string $key): bool
    {
        return $this->session->has($key);
    }

    public function sess_destroy(): void
    {
        $this->session->destroy();
    }

    public function sess_regenerate(bool $destroy = false): void
    {
        $this->session->regenerate($destroy);
    }

    /**
     * Read session values as properties, as CI4 allows.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->session->{$key};
    }

    public function __isset(string $key): bool
    {
        return $this->session->has($key);
    }

    /**
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * Fall through to the CI4 session for anything not listed above.
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->session->{$method}(...$arguments);
    }
}
