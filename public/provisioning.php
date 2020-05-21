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

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
$container = $app->getContainer();
$container['config'] = $config;
$container['logger'] = function($c) {
    return \Tancredi\LoggerFactory::createLogger('provisioning', $c);
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

// Register the client IP address for logging
$upstreamProxies = array_map('trim', explode(',', isset($config['upstream_proxies']) ? $config['upstream_proxies'] : ''));
$app->add(new \RKA\Middleware\IpAddress( ! empty($upstreamProxies), $upstreamProxies));

// Add request/response logging middleware
$app->add(new \Tancredi\LoggingMiddleware($container));

$app->get('/check/ping', function(Request $request, Response $response, array $args) use ($app) {
    return $response->withJson(filemtime('/etc/tancredi.conf'),200);
});

$app->get('/{token}/firmware/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $filename = $args['filename'];
    $token = $args['token'];
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $this->logger->info('Invalid token request from {address} {ua}: {uri}', ['uri' => strval($request->getUri()), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        $response = $response->withStatus(404);
        return $response;
    }

    $realfile = realpath($config['rw_dir'] . 'firmware/' . $filename);
    if( ! $realfile || dirname($realfile) != ($config['rw_dir'] . 'firmware')) {
        // File not found
        return $response->withStatus(404);
    }

    $response = $response->withHeader('Content-Type', 'application/octet-stream');

    if(isset($config['file_reader']) && $config['file_reader'] == 'apache') {
        return $response->withHeader('X-Sendfile', $realfile);
    } elseif(isset($config['file_reader']) && $config['file_reader'] == 'nginx') {
        return $response->withHeader('X-Accel-Redirect', $realfile);
    } else {
        return $response->withBody(new \Slim\Http\Stream(fopen($realfile, 'r')));
    }
});

$app->get('/{token}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $filename = $args['filename'];
    $token = $args['token'];
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $this->logger->info('Invalid token request from {address} {ua}: {uri}', ['uri' => strval($request->getUri()), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        $response = $response->withStatus(404);
        return $response;
    }

    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $this->storage, $this->logger, TRUE);
    // Get template variable name from file
    $data = getDataFromFilename($filename,$this->logger);
    $scope_data = array_merge($scope, $scope['variables']);
    unset($scope_data['variables']);

    if (empty($data['template'])) {
        $this->logger->debug('Template not found for "{filename}". It does not match our patterns.d/ rules', $data);
        return $response->withStatus(404);
    } elseif(empty($scope_data[$data['template']])) {
        $this->logger->error('Template not found for "{filename}". The variable "{template}" from pattern "{pattern_name}" is not set properly', $data);
        return $response->withStatus(500);
    }

    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config, $this->logger);
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
        $this->logger->debug('Rendered template "{template}" with data: {data}', ['data' => json_encode($scope_data), 'template' => $scope_data[$data['template']]]);
        $this->logger->info('Serving request from {address} {ua}: {uri} ({mac})', ['mac' => $scope_data['mac'], 'uri' => strval($request->getUri()), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e);
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
    $filename = $args['filename'];

    $data = getDataFromFilename($filename,$this->logger);

    if (empty($data['scopeid'])) {
        $this->logger->debug(sprintf('Scope not found for "%s"', $filename));
        return $response->withStatus(404);
    }

    $id = $data['scopeid'];
    // Convert mac address to uppercase if id is a mac address
    if (preg_match('/[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}/',$id) != FALSE) {
        $id = strtoupper($id);
    }

    // Instantiate scope
    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $this->storage, $this->logger, TRUE);
    // Get template variable name from file
    $scope_data = array_merge($scope, $scope['variables']);
    unset($scope_data['variables']);

    if (empty($data['template'])) {
        $this->logger->debug('Template not found for "{filename}". It does not match our patterns.d/ rules', $data);
        return $response->withStatus(404);
    } elseif(empty($scope_data[$data['template']])) {
        $this->logger->error('Template not found for "{filename}". The variable "{template}" from pattern "{pattern_name}" is not set properly', $data);
        return $response->withStatus(500);
    }

    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config,$this->logger);
            $scope_data = $filterObj($scope_data);
        }
    }

    // Add provisioning_complete variable
    $scope_data['provisioning_complete'] = '';

    // Add user agent
    $scope_data['provisioning_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    try {
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write(renderTwigTemplate($scope_data[$data['template']], $scope_data));
        $this->logger->debug('Rendered template "{template}" with data: {data}', ['data' => json_encode($scope_data), 'template' => $scope_data[$data['template']]]);
        $this->logger->info('Serving request from {address} {ua}: {uri} ({mac})', ['mac' => $scope_data['mac'], 'uri' => strval($request->getUri()), 'ua' => $_SERVER['HTTP_USER_AGENT'], 'address' => $request->getAttribute('ip_address')]);
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e);
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
