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

$app->get('/{token}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()) . " " . $_SERVER['HTTP_USER_AGENT']);
    global $config;
    $logger = new \Monolog\Logger('Tancredi');
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
    $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
    $this->logger->debug("Scope $id last edit time: " . $scope->getLastEditTime() . " last_read_time: " . $scope->getLastReadTime());
    // Get template variable name from file
    $data = getDataFromFilename($filename,$this->logger);
    $this->logger->debug(print_r($data,true));
    $template_var_name = $data['template'];
    $scope_data = $scope->getVariables();
    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config, $this->logger);
            $scope_data = $filterObj($scope_data);
        }
    }

    //Add token2 variable
    $scope_data['tok2'] = \Tancredi\Entity\TokenManager::getToken2($id);

    // Add provisioning_complete variable
    if ($token === $scope_data['tok2']) {
        $scope_data['provisioning_complete'] = '1';
    } else {
        $scope_data['provisioning_complete'] = '';
    }

    // Ensure default values for provisioning_url_path and hostname variables
    $scope_data['provisioning_url_path'] = $scope_data['provisioning_url_path'] ?: $config['provisioning_url_path'];
    $scope_data['hostname'] = $scope_data['hostname'] ?: gethostname();

    // Add user agent
    $scope_data['provisioning_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    $this->logger->debug(print_r($scope_data,true));
    if (array_key_exists($template_var_name,$scope_data)) {
        $template = $scope_data[$template_var_name];
    } else {
        // Missing template
        $this->logger->error('Template variable ' . $template_var_name . ' doesn\'t exists in scope ' . $scope->id );
        $response = $response->withStatus(404);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    try {
        // Load twig template
        if (file_exists($config['rw_dir'] . 'templates-custom/' . $template)) {
            $loader = new \Twig\Loader\FilesystemLoader($config['rw_dir'] . 'templates-custom/');
        } else {
            $loader = new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/');
        }
        $twig = new \Twig\Environment($loader,['autoescape' => false]);
        $response = $response->getBody()->write($twig->render($template,$scope_data));
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
    $this->logger->debug(print_r($data,true));
    if (array_key_exists('scopeid',$data) and !empty($data['scopeid'])) {
        $id = $data['scopeid'];
        // Convert mac address to uppercase if id is a mac address
        if (preg_match('/[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}-[a-f0-9]{2}/',$id) != FALSE) {
            $id = strtoupper($id);
        }
    } else {
        $this->logger->error('Can\'t get id from filename');
        $response = $response->withStatus(403);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    // Instantiate scope
    $this->logger->debug("New scope id: \"$id\"");
    $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
    $this->logger->debug("Scope $id last edit time: " . $scope->getLastEditTime() . " last_read_time: " . $scope->getLastReadTime());
    // Get template variable name from file
    $template_var_name = $data['template'];
    $scope_data = $scope->getVariables();
    // Save scope id into not found scopes if it has empty data
    if (empty($scope_data)) {
        saveNotFoundScopes($id);
    }
    // Load filters
    if (array_key_exists('runtime_filters',$config) and !empty($config['runtime_filters'])) {
        foreach (explode(',',$config['runtime_filters']) as $filter) {
            $filter = "\\Tancredi\\Entity\\" . $filter;
            $filterObj = new $filter($config,$this->logger);
            $scope_data = $filterObj($scope_data);
        }
    }
    //Add token2 variable
    $scope_data['tok2'] = \Tancredi\Entity\TokenManager::getToken2($id);

    // Add provisioning_complete variable
    $scope_data['provisioning_complete'] = '';

    // Ensure default values for provisioning_url_path and hostname variables
    $scope_data['provisioning_url_path'] = $scope_data['provisioning_url_path'] ?: $config['provisioning_url_path'];
    $scope_data['hostname'] = $scope_data['hostname'] ?: gethostname();

    // Add user agent
    $scope_data['provisioning_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    $this->logger->debug(print_r($scope_data,true));
    if (array_key_exists($template_var_name,$scope_data)) {
        $template = $scope_data[$template_var_name];
    } else {
        // Missing template
        $this->logger->error('Template variable ' . $template_var_name . ' doesn\'t exists in scope ' . $scope->id );
        $response = $response->withStatus(404);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    try {
        // Load twig template
        $loader = new \Twig\Loader\FilesystemLoader($config['ro_dir'] . 'templates/');
        $twig = new \Twig\Environment($loader,['autoescape' => false]);
	$response = $response->getBody()->write($twig->render($template,$scope_data));
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
    foreach ($patterns as $pattern) {
        if (preg_match('/'.$pattern['pattern'].'/', $filename, $tmp)) {
            $result['template'] = $pattern['template'];
            $logger->debug($pattern['pattern'].' '.$pattern['scopeid'] .' '.  $filename);
            $result['scopeid'] = preg_replace('/'.$pattern['pattern'].'/', $pattern['scopeid'] , $filename );
            break;
        }
    }
    return $result;
}

function saveNotFoundScopes($scope_id){
    global $config;
    if (preg_match('/[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}/', $scope_id)) {
        // Provided scope id is a MAC address
        $data = array();
        if (file_exists($config['rw_dir'] . 'not_found_scopes')) {
            $data = (array) json_decode(file_get_contents($config['rw_dir'] . 'not_found_scopes'));
        }
        // add MAC to $config['rw_dir'] . 'not_found_scopes' file
        $data[$scope_id] = time();
        file_put_contents($config['rw_dir'] . 'not_found_scopes', json_encode($data));
    }
}

// Run app
$app->run();
