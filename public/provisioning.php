<?php

require_once '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
$container = $app->getContainer();
$container['config'] = $config;
$container['logger'] = function($c) {
    return \Tancredi\LoggerFactory::createLogger($c);
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

// Add request/response logging middleware
$app->add(new \Tancredi\LoggingMiddleware($container));

$app->get('/check/ping', function(Request $request, Response $response, array $args) use ($app) {
    return $response->withJson(filemtime('/etc/tancredi.conf'),200);
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
        $this->logger->error(sprintf('Template not found for "%s". The variable "%s" from pattern "%s" is not set properly', $filename, $data['template'], $data['pattern_name']));
        return $response->withStatus(404);
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

    $this->logger->debug(print_r($scope_data,true));
    try {
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write(renderTwigTemplate($scope_data[$data['template']], $scope_data));
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e->getMessage());
        $response = $response->withStatus(500);
        return $response;
    }
});

$app->get('/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $config;
    $filename = $args['filename'];
    $this->logger->info('Received a file request without token. File: ' . $filename);

    $data = getDataFromFilename($filename,$this->logger);

    if (empty($data['scopeid'])) {
        $this->logger->error(sprintf('Scope not found for "%s"', $filename));
        return $response->withStatus(404);
    }

    $id = $data['scopeid'];
    // Convert mac address to uppercase if id is a mac address
    if (preg_match('/[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}/',$id) != FALSE) {
        $id = strtoupper($id);
    }

    // Instantiate scope
    $this->logger->debug("New scope id: \"$id\"");
    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $this->storage, $this->logger, TRUE);
    // Get template variable name from file
    $scope_data = array_merge($scope, $scope['variables']);
    unset($scope_data['variables']);

    if (empty($data['template'])) {
        $this->logger->error(sprintf('Template not found for "%s". It does not match our patterns.d/ rules', $filename));
        return $response->withStatus(404);
    } elseif(empty($scope_data[$data['template']])) {
        $this->logger->error(sprintf('Template not found for "%s". The variable "%s" from pattern "%s" is not set properly', $filename, $data['template'], $data['pattern_name']));
        return $response->withStatus(404);
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

    $this->logger->debug(print_r($scope_data,true));
    try {
        // Load twig template
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write(renderTwigTemplate($scope_data[$data['template']], $scope_data));
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e->getMessage());
        $response = $response->withStatus(500);
        return $response;
    }
});

function renderTwigTemplate($template, $scope_data) {
    global $config;
    $loader = new \Twig\Loader\ChainLoader([
        new \Twig\Loader\FilesystemLoader($config['rw_dir'] . 'templates-custom/'),
        new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/'),
    ]);
    $twig = new \Twig\Environment($loader,['autoescape' => false]);
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
        if (preg_match('/'.$pattern['pattern'].'/', $filename, $tmp)) {
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
