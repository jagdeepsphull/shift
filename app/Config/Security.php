<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     *
     * 'session' rather than 'cookie': the token is held server-side, so it is
     * never readable by script on the page and never travels except in the form
     * or header that is being checked. Every screen behind a login already has
     * a session; the public forms - registration, contact, forgot password -
     * get one the moment they are drawn.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     *
     * On: the value in the page is masked differently every time it is printed,
     * so the token itself cannot be read back out of a compressed response
     * (BREACH). All of them unmask to the one token the session holds.
     */
    public bool $tokenRandomize = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'csrf_token';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Defaults to two hours (in seconds).
     */
    public int $expires = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission.
     *
     * Off, deliberately. The token is injected into every form and handed to
     * jQuery once per page load (App\Filters\CsrfTokenInjector). Rotating it on
     * each submission would invalidate the copy every other form and every
     * queued ajax call on that page is still holding - a second tab, or the
     * store dropdown fetched after a save, would fail for no reason the user
     * could act on. The token is still per-session and dies with it.
     */
    public bool $regenerate = false;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = (ENVIRONMENT === 'production');
}
