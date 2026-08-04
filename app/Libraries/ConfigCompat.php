<?php

namespace App\Libraries;

/**
 * CodeIgniter 3 `$this->config` facade.
 *
 * `item()` reads the application lookup lists from Config\AppSettings, and
 * falls back to Config\App for the framework values CI3 kept in the same file
 * (`base_url`, `charset`, ...).
 */
class ConfigCompat
{
    /** CI3 name => Config\App property. */
    protected const APP_MAP = [
        'base_url'           => 'baseURL',
        'index_page'         => 'indexPage',
        'uri_protocol'       => 'uriProtocol',
        'charset'            => 'charset',
        'language'           => 'defaultLocale',
        'permitted_uri_chars' => 'permittedURIChars',
        'proxy_ips'          => 'proxyIPs',
    ];

    /**
     * @return mixed|null
     */
    public function item(string $item, string $index = '')
    {
        $settings = config('AppSettings');

        if (property_exists($settings, $item)) {
            return $settings->{$item};
        }

        if (isset(self::APP_MAP[$item])) {
            return config('App')->{self::APP_MAP[$item]};
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return $this->item($key);
    }

    public function site_url(string $uri = ''): string
    {
        return site_url($uri);
    }

    public function base_url(string $uri = ''): string
    {
        return base_url($uri);
    }

    /**
     * CI3 `$this->config->set_item()` - only meaningful for the runtime values
     * this application never changed, so it is a no-op.
     *
     * @param mixed $value
     */
    public function set_item(string $item, $value): void
    {
        // No-op: configuration is immutable in CI4.
    }
}
