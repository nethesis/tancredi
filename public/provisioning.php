<?php

/*
 * Copyright (C) 2020 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

require_once '../vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Psr7\Stream;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'config' => function() {
        global $config;
        return $config;
    },
    'logger' => function($c) {
        return \Tancredi\LoggerFactory::createLogger('provisioning', $c);
    },
    'storage' => function($c) {
        return new \Tancredi\Entity\FileStorage($c->get('logger'), $c->get('config'));
    }
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register the client IP address for logging
$upstreamProxies = array_map('trim', explode(',', isset($config['upstream_proxies']) ? $config['upstream_proxies'] : ''));
$app->add(new \RKA\Middleware\IpAddress( ! empty($upstreamProxies), $upstreamProxies));

// Add request/response logging middleware
$app->add(new \Tancredi\LoggingMiddleware($container->get('logger')));

$app->get('/check/ping', function(Request $request, Response $response, array $args) use ($app) {
    $response->getBody()->write(json_encode(filemtime('/etc/tancredi.conf')));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/{token}/{filetype:backgrounds|firmware|ringtones|screensavers}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $container = $app->getContainer();
    $logger = $container->get('logger');

    $filename = $args['filename'];
    $token = $args['token'];
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $logger->info('Invalid token request from {address} {ua}: {uri}', ['uri' => (string) $request->getUri(), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        $response = $response->withStatus(404);
        return $response;
    }

    $realfile = realpath($config['rw_dir'] . $args['filetype'] . '/' . $filename);
    if( ! $realfile || dirname($realfile) != ($config['rw_dir'] . $args['filetype'])) {
        // File not found
        return $response->withStatus(404);
    }

    $response = $response->withHeader('Content-Type', 'application/octet-stream');

    if(isset($config['file_reader']) && $config['file_reader'] == 'apache') {
        return $response->withHeader('X-Sendfile', $realfile);
    } elseif(isset($config['file_reader']) && $config['file_reader'] == 'nginx') {
        return $response->withHeader('X-Accel-Redirect', $realfile);
    } else {
        return $response->withBody(new Stream(fopen($realfile, 'r')));
    }
});

$app->get('/{token}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $container = $app->getContainer();
    $logger = $container->get('logger');
    $storage = $container->get('storage');

    $filename = $args['filename'];
    $token = $args['token'];
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $logger->info('Invalid token request from {address} {ua}: {uri}', ['uri' => (string) $request->getUri(), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        $response = $response->withStatus(404);
        return $response;
    }

    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $storage, $logger, TRUE);
    // Get template variable name from file
    $data = getDataFromFilename($filename, $logger);
    $scope_data = array_merge($scope, $scope['variables']);
    unset($scope_data['variables']);

    if (empty($data['template'])) {
        $logger->debug('Template not found for "{filename}". It does not match our patterns.d/ rules', $data);
        return $response->withStatus(404);
    } elseif(empty($scope_data[$data['template']])) {
        $logger->error('Template not found for "{filename}". The variable "{template}" from pattern "{pattern_name}" is not set properly', $data);
        return $response->withStatus(500);
    }

    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config, $logger);
            $scope_data = $filterObj($scope_data);
        }
    }

    // Add provisioning_complete variable
    if ($token === $scope_data['tok2']) {
        $scope_data['provisioning_complete'] = '1';
    } else {
        $scope_data['provisioning_complete'] = '';
    }

    // Add user agent
    $scope_data['provisioning_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    try {
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write(renderTwigTemplate($scope_data[$data['template']], $scope_data));
        $logger->debug('Rendered template "{template}" with data: {data}', ['data' => json_encode($scope_data), 'template' => $scope_data[$data['template']]]);
        $logger->info('Serving request from {address} {ua}: {uri} ({mac})', ['mac' => $scope_data['mac'], 'uri' => (string) $request->getUri(), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        return $response;
    } catch (Exception $e) {
        $logger->error($e);
        $response = $response
            ->withHeader('Content-type', 'text/plain')
            ->withStatus(500)
        ;
        $response->getBody()->write("Internal server error\n\nSee the application log for details.\n");
        return $response;
    }
});

$app->get('/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $container = $app->getContainer();
    $logger = $container->get('logger');
    $storage = $container->get('storage');

    $filename = $args['filename'];

    $data = getDataFromFilename($filename, $logger);

    if (empty($data['scopeid'])) {
        $logger->debug(sprintf('Scope not found for "%s"', $filename));
        return $response->withStatus(404);
    }

    $id = $data['scopeid'];
    // Convert mac address to uppercase if id is a mac address
    if (preg_match('/[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}/',$id) != FALSE) {
        $id = strtoupper($id);
    }

    // Instantiate scope
    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $storage, $logger, TRUE);
    // Get template variable name from file
    $scope_data = array_merge($scope, $scope['variables']);
    unset($scope_data['variables']);

    if (empty($data['template'])) {
        $logger->debug('Template not found for "{filename}". It does not match our patterns.d/ rules', $data);
        return $response->withStatus(404);
    } elseif(empty($scope_data[$data['template']])) {
        $logger->error('Template not found for "{filename}". The variable "{template}" from pattern "{pattern_name}" is not set properly', $data);
        return $response->withStatus(500);
    }

    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config, $logger);
            $scope_data = $filterObj($scope_data);
        }
    }

    // Remove secrets from scope data
    foreach ($scope_data as $key => $value) {
        if (strpos($key, 'account_') !== FALSE ||
            strpos($key, 'adminpw') !== FALSE ||
            strpos($key, 'userpw') !== FALSE) {
            $scope_data[$key] = '';
        }
    }
    // Use token 1 instead of token 2
    $scope_data['tok2'] = $scope_data['tok1'];

    // Add provisioning_complete variable
    $scope_data['provisioning_complete'] = '';

    // Add user agent
    $scope_data['provisioning_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    try {
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write(renderTwigTemplate($scope_data[$data['template']], $scope_data));
        $logger->debug('Rendered template "{template}" with data: {data}', ['data' => json_encode($scope_data), 'template' => $scope_data[$data['template']]]);
        $logger->info('Serving request from {address} {ua}: {uri} ({mac})', ['mac' => $scope_data['mac'], 'uri' => (string) $request->getUri(), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        return $response;
    } catch (Exception $e) {
        $logger->error($e);
        $response = $response
            ->withHeader('Content-type', 'text/plain')
            ->withStatus(500)
        ;
        $response->getBody()->write("Internal server error\n\nSee the application log for details.\n");
        return $response;
    }
});

function renderTwigTemplate($template, $scope_data) {
    global $config;
    $loader = new \Twig\Loader\ChainLoader([
        new \Twig\Loader\FilesystemLoader($config['rw_dir'] . 'templates-custom/'),
        new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/'),
    ]);
    $preg_replace_filter = new \Twig\TwigFilter('preg_replace', function($subject, $pattern, $replacement) {
        return preg_replace($pattern, $replacement, $subject);
    });
    $twig = new \Twig\Environment($loader,['autoescape' => false]);
    $twig->addFilter($preg_replace_filter);
    $payload = $twig->render($template, $scope_data);
    return $payload;
}

function getDataFromFilename($filename,$logger) {
    global $config;
    $result = array(
        'filename' => $filename,
    );
    $patterns = array();
    foreach (glob($config['ro_dir'] . 'patterns.d/*.ini') as $pattern_file) {
        $patterns = array_merge($patterns, parse_ini_file($pattern_file, true));
    }
    foreach ($patterns as $pattern_name => $pattern) {
        if (preg_match('/^'.$pattern['pattern'].'$/', $filename, $tmp)) {
            $result['pattern_name'] = $pattern_name;
            $result['template'] = $pattern['template'];
            $result['scopeid'] = preg_replace('/'.$pattern['pattern'].'/', $pattern['scopeid'] , $filename );
            $result['content_type'] = empty($pattern['content_type']) ? 'text/plain; charset=utf-8' : $pattern['content_type'];
            break;
        }
    }
    return $result;
}

// Run app
$app->run();
