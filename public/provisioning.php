<?php namespace Tancredi;

include_once(__DIR__ . '/../src/init.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

$app->get('/{token}/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $log;
    $filename = $args['filename'];
    $token = $args['token'];
    $log->info('Received a token and file request. Token: ' .$token . '. File: ' . $filename);
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $log->error('Invalid token requested. Token: ' . $token);
        return $response->withStatus(403);
        return;
    }

    $log->debug('Token '.$token.' is valid');
    // Instantiate scope
    $log->debug("New scope id: \"$id\"");
    $scope = new \Tancredi\Entity\Scope($id);
    $log->debug("Scope $id last edit time: " . $scope->getLastEditTime() . " last_read_time: " . $scope->getLastReadTime());
    // Get template variable name from file
    $data = getDataFromFilename($filename);
    $log->debug(print_r($data,true));
    $template_var_name = $data['template'];
    $scope_data = $scope->getVariables();
    $log->debug(print_r($scope_data,true));
    if (array_key_exists($template_var_name,$scope_data)) {
        $template = $scope_data[$template_var_name];
    } else {
        // Missing template
        $log->error('Template variable ' . $template_var_name . ' doesn\'t exists in scope ' . $scope->id );
        return $response->withStatus(404);
    }
    // Load twig template
    $loader = new \Twig\Loader\FilesystemLoader($config['templates_dir']);
    $twig = new \Twig\Environment($loader);
    return $response->getBody()->write($twig->render($template,$scope_data));
});

$app->get('/{filename}', function(Request $request, Response $response, array $args) use ($app) {
    global $log;
    $filename = $args['filename'];
    $log->info('Received a file request without token. File: ' . $filename);

    $data = getDataFromFilename($filename);
    $log->debug(print_r($data,true));
    if (array_key_exists('scope_id',$data) and !empty($data['scope_id'])) {
        $id = $data['scope_id'];
    } else {
        $log->error('Can\'t get id from filename');
        return $response->withStatus(403);
    }
    // Instantiate scope
    $log->debug("New scope id: \"$id\"");
    $scope = new \Tancredi\Entity\Scope($id);
    $log->debug("Scope $id last edit time: " . $scope->getLastEditTime() . " last_read_time: " . $scope->getLastReadTime());
    // Get template variable name from file
    $template_var_name = $data['template'];
    $scope_data = $scope->getVariables();
    // Save scope id into not found scopes if it has empty data
    if (empty($scope_data)) {
        saveNotFoundScopes($id);
    }
    $log->debug(print_r($scope_data,true));
    if (array_key_exists($template_var_name,$scope_data)) {
        $template = $scope_data[$template_var_name];
    } else {
        // Missing template
        $log->error('Template variable ' . $template_var_name . ' doesn\'t exists in scope ' . $scope->id );
        return $response->withStatus(404);
    }
    // Load twig template
    $loader = new \Twig\Loader\FilesystemLoader($config['templates_dir']);
    $twig = new \Twig\Environment($loader);
    return $response->getBody()->write($twig->render($template,$scope_data));
});

function getDataFromFilename($filename) {
    global $log;
    $result = array();
    $patterns = array();
    foreach (scandir($config['patterns_dir']) as $pattern_file) {
        if ($pattern_file === '.' or $pattern_file === '..' or substr($pattern_file,-4) !== '.ini') continue;
        $patterns = array_merge($patterns,parse_ini_file($config['patterns_dir'].$pattern_file,true));
    }
    foreach ($patterns as $pattern) {
        if (preg_match('/'.$pattern['pattern'].'/', $filename, $tmp)) {
            $result['template'] = $pattern['template'];
            $log->debug($pattern['pattern'].' '.$pattern['scopeid'] .' '.  $filename);
            $result['scope_id'] = preg_replace('/'.$pattern['pattern'].'/', $pattern['scopeid'] , $filename );
            break;
        }
    }
    return $result;
}

function saveNotFoundScopes($scope_id){
    if (preg_match('/[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}-[A-F0-9]{2}/', $scope_id)) {
        // Provided scope id is a MAC address
        $data = array();
        if (file_exists($config['not_found_scopes'])) {
            $data = (array) json_decode(file_get_contents($config['not_found_scopes']));
        }
        // add MAC to $config['not_found_scopes'] file
        $data[$scope_id] = time();
        file_put_contents($config['not_found_scopes'], json_encode($data));
    }
}

// Run app
$app->run();

