<?php

namespace Config;

use App\Filters\CsrfTokenInjector;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'csrftoken'     => CsrfTokenInjector::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            // 'toolbar',  // Debug Toolbar — disabled; re-enable to profile locally
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // Every POST, PUT, PATCH and DELETE must carry the session's token.
            // Reads are untouched, so nothing about browsing changes.
            //
            // The token gets into the page by the filter below - there is no
            // view to remember to edit - and into ajax calls as a header.
            //
            // The unsubscribe URL is the one exception, and has to be. RFC 8058
            // one-click means Gmail and Yahoo post to it straight from their own
            // Unsubscribe button: no page of ours was ever loaded, so there is
            // no session and no token to send, and refusing the post leaves
            // "report spam" as the working button beside it.
            //
            // What CSRF protects is an action that borrows the visitor's
            // signed-in identity. This endpoint has none to borrow: it is
            // authorised by the secret in the URL, it is reachable signed out,
            // and the worst a forged post can do is stop e-mail reaching
            // somebody whose unsubscribe link the forger already had - which is
            // the same thing they could do by following it themselves.
            'csrf' => ['except' => ['unsubscribe/*']],

            // 'honeypot',
            // 'invalidchars',
        ],
        'after' => [
            // Puts the token in the forms and hands it to jQuery. Must stay
            // paired with 'csrf' above: turning this off leaves every form on
            // the site failing the check.
            'csrftoken',

            // X-Frame-Options, X-Content-Type-Options and Referrer-Policy on
            // every response the application renders. The .htaccess sets the
            // same headers, which covers the static files Apache serves
            // without ever entering PHP; this covers the pages, including on a
            // host where mod_headers is not loaded.
            'secureheaders',

            // 'honeypot',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [];
}
