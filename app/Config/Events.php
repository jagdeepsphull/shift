<?php

namespace Config;

use App\Libraries\Iptracker;
use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;
use Throwable;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        $value = ini_get('zlib.output_compression');

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});

/*
 * --------------------------------------------------------------------
 * Ported from the CodeIgniter 3 hooks / autoloaded libraries
 * --------------------------------------------------------------------
 * - LanguageLoader hook: pick the locale the visitor chose (`site_lang`).
 * - Iptracker library:   log the page view into the `visit_log` table.
 */
Events::on('post_controller_constructor', static function (): void {
    if (is_cli()) {
        return;
    }

    $siteLang = session()->get('site_lang');

    if (is_string($siteLang) && $siteLang !== '') {
        service('request')->setLocale($siteLang);
    }

    try {
        (new Iptracker())->save_site_visit();
    } catch (Throwable $e) {
        // Never let visit logging break a page request.
        log_message('error', 'Iptracker: ' . $e->getMessage());
    }
});
