<?php

require_once '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
$container = $app->getContainer();
$container['logger'] = function($c) {
    global $config;
    $logger = new \Monolog\Logger('Tancredi');
    $file_handler = new \Monolog\Handler\StreamHandler($config['logfile'],\Monolog\Logger::DEBUG); //TODO use config['log_level'] here somehow
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

$app->get('/check/ping', function(Request $request, Response $response, array $args) use ($app) {
    return $response->withJson(filemtime('/etc/tancredi.conf'),200);
});

$app->get('/{token}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()) . " " . $_SERVER['HTTP_USER_AGENT']);
    global $config;
    $filename = $args['filename'];
    $token = $args['token'];
    $this->logger->info('Received a token and file request. Token: ' .$token . '. File: ' . $filename);
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $this->logger->error('Invalid token requested. Token: ' . $token);
        $response = $response->withStatus(403);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }

    $this->logger->debug('Token '.$token.' is valid');
    // Instantiate scope
    $this->logger->debug("New scope id: \"$id\"");
    $scope = \Tancredi\Entity\Scope::getPhoneScope($id, $this->storage, $this->logger, TRUE);
    // Get template variable name from file
    $data = getDataFromFilename($filename,$this->logger);
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
        // Load twig template
        $template = $scope_data[$data['template']];
        if (file_exists($config['rw_dir'] . 'templates-custom/' . $template)) {
            $loader = new \Twig\Loader\FilesystemLoader($config['rw_dir'] . 'templates-custom/');
        } else {
            $loader = new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/');
        }
        $twig = new \Twig\Environment($loader,['autoescape' => false]);
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write($twig->render($template, $scope_data));
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result: 200 ok '. __FILE__.':'.__LINE__);
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e->getMessage());
        $response = $response->withStatus(500);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
});

$app->get('/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()) . " " . $_SERVER['HTTP_USER_AGENT']);
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
        $template = $scope_data[$data['template']];
        $loader = new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/');
        $twig = new \Twig\Environment($loader,['autoescape' => false]);
        $response = $response->withHeader('Cache-Control', 'private');
        $response = $response->withHeader('Content-Type', $data['content_type']);
        $response->getBody()->write($twig->render($template, $scope_data));
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result: 200 ok ' . __FILE__.':'.__LINE__);
        return $response;
    } catch (Exception $e) {
        $this->logger->error($e->getMessage());
        $response = $response->withStatus(500);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
});

function getDataFromFilename($filename,$logger) {
    global $config;
    $result = array();
    $patterns = array();
    foreach (scandir($config['ro_dir'] . 'patterns.d/') as $pattern_file) {
        if ($pattern_file === '.' or $pattern_file === '..' or substr($pattern_file,-4) !== '.ini') continue;
        $patterns = array_merge($patterns,parse_ini_file($config['ro_dir'] . 'patterns.d/'.$pattern_file,true));
    }
    foreach ($patterns as $pattern_name => $pattern) {
        if (preg_match('/'.$pattern['pattern'].'/', $filename, $tmp)) {
            $result['pattern_name'] = $pattern_name;
            $result['template'] = $pattern['template'];
            $logger->debug($pattern['pattern'].' '.$pattern['scopeid'] .' '.  $filename);
            $result['scopeid'] = preg_replace('/'.$pattern['pattern'].'/', $pattern['scopeid'] , $filename );
            $result['content_type'] = empty($pattern['content_type']) ? 'text/plain; charset=utf-8' : $pattern['content_type'];
            break;
        }
    }
    return $result;
}

// Run app
$app->run();
