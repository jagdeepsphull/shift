<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Ported from CI3 `application/config/routes.php`.
 *
 * Legacy auto-routing is enabled (see Config\Routing and Config\Feature), so
 * every `Controller/method/segment/segment` URL that worked under CodeIgniter 3
 * keeps working unchanged. The routes below are the explicit overrides that the
 * CI3 routes file declared.
 */

// $route['default_controller'] = 'front';
$routes->match(['get', 'post'], '/', 'Front::index');

// $route['resources'] = 'front/resources';
$routes->match(['get', 'post'], 'resources', 'Front::resources');
$routes->match(['get', 'post'], 'resources/(:any)', 'Front::resources/$1');

// $route['contact'] = 'front/contact';
$routes->match(['get', 'post'], 'contact', 'Front::contact');

// $route['terms'] = 'front/terms_conditions';
$routes->match(['get', 'post'], 'terms', 'Front::terms_conditions');

// $route['policy'] = 'front/privacy_policy';
$routes->match(['get', 'post'], 'policy', 'Front::privacy_policy');

// The Unsubscribe link on the bottom of every e-mail the site sends. Named
// rather than left to auto-routing because it is printed in mail that outlives
// any refactor: `/front/unsubscribe/...` would break the moment the method
// moved, and the messages carrying it cannot be reissued.
//
// GET shows the question, POST performs it. Both land here; Front::unsubscribe
// tells them apart.
$routes->match(['get', 'post'], 'unsubscribe/(:segment)', 'Front::unsubscribe/$1');

// Legacy stand-alone cron script (cronjob1.php) - closes shifts whose date passed.
$routes->get('cronjob1.php', 'Cron::expire_jobs');
