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

// Legacy stand-alone cron script (cronjob1.php) - closes shifts whose date passed.
$routes->get('cronjob1.php', 'Cron::expire_jobs');
