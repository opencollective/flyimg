<?php

use Core\Resolver\ControllerResolver;
use Core\Service\CoreManager;
use Core\Service\ImageProcessor;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

$loader = require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();

/**
 * Define Constants && Load parameters files
 */
define('UPLOAD_WEB_DIR', 'uploads/');
define('UPLOAD_DIR', __DIR__.'/web/'.UPLOAD_WEB_DIR);
define('TMP_DIR', __DIR__.'/var/tmp/');
define('ROOT_DIR', __DIR__);

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!is_dir(TMP_DIR)) {
    mkdir(TMP_DIR, 0777, true);
}

$app['params'] = Yaml::parse(file_get_contents(__DIR__.'/config/parameters.yml'));

/**
 * Routes
 */
$app['routes'] = $app->extend(
    'routes',
    function (RouteCollection $routes) {
        $loader = new YamlFileLoader(new FileLocator(__DIR__.'/config'));
        $collection = $loader->load('routes.yml');
        $routes->addCollection($collection);

        return $routes;
    }
);

/** Register Storage provider */
$app->register(new \Core\Provider\StorageProvider());

/** Monolog Service*/
$app->register(
    new Silex\Provider\MonologServiceProvider(),
    array(
        'monolog.name' => 'flyimg',
        'monolog.level' => Logger::ERROR,
        'monolog.logfile' => 'php://stderr',
    )
);

/** Controller Resolver */
$app['resolver'] = function ($app) {
    return new ControllerResolver($app, $app['logger']);
};

/** Image processor Service */
$app['image.processor'] = function ($app) {
    return new ImageProcessor($app['flysystems']['upload_dir']);
};

/** Core Manager Service */
$app['core.manager'] = function ($app) {
    return new CoreManager($app['image.processor'], $app['params']);
};

/** debug conf */
$app['debug'] = $app['params']['debug'];
return $app;
