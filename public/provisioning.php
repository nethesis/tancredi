<?php namespace Tancredi;

define ("TEMPLATES_DIR", "/usr/share/nethvoice/tancredi/data/templates/");
define ("PATTERNS_DIR", "/usr/share/nethvoice/tancredi/data/patterns.d/");
define ("TEMPLATES_CUSTOM_DIR", "/usr/share/nethvoice/tancredi/data/templates-custom/");
define ("NOT_FOUND_SCOPES", "/usr/share/nethvoice/tancredi/data/not_found_scopes");

require '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;

use \Monolog\Logger;

use \Monolog\Handler\StreamHandler;

ini_set('date.timezone', 'UTC');

$log = new Logger('Tancredi');

$log->pushHandler(new StreamHandler('/var/log/pbx/tancredi.log', Logger::DEBUG));

$app = new \Slim\Slim();

$app->get('/:token/:file', function($token,$filename) use ($app) {
    global $log;
    $log->info('Received a token and file request. Token: ' .$token . '. File: ' . $filename);
    $id = \Tancredi\Entity\TokenManager::getIdFromToken($token);
    if ($id === FALSE) {
        // Token doesn't exists
        $log->error('Invalid token requested. Token: ' . $token);
        $app->response->setStatus(403);
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
        $app->response->setStatus(404);
        return;
    }
    // Load twig template
    $loader = new \Twig\Loader\FilesystemLoader(TEMPLATES_DIR);
    $twig = new \Twig\Environment($loader);
    $out = $twig->render($template,$scope_data);
    echo $out;
});

$app->get('/:file', function($filename) use ($app) {
    global $log;
    $log->info('Received a file request without token. File: ' . $filename);

    $data = getDataFromFilename($filename);
    $log->debug(print_r($data,true));
    if (array_key_exists('scope_id',$data) and !empty($data['scope_id'])) {
        $id = $data['scope_id'];
    } else {
        $log->error('Can\'t get id from filename');
        $app->response->setStatus(403);
        return;
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
        $app->response->setStatus(404);
        return;
    }
    // Load twig template
    $loader = new \Twig\Loader\FilesystemLoader(TEMPLATES_DIR);
    $twig = new \Twig\Environment($loader);
    $out = $twig->render($template,$scope_data);
    echo $out;
});

function getDataFromFilename($filename) {
    global $log;
    $result = array();
    $patterns = array();
    foreach (scandir(PATTERNS_DIR) as $pattern_file) {
        if ($pattern_file === '.' or $pattern_file === '..' or substr($pattern_file,-4) !== '.ini') continue;
        $patterns = array_merge($patterns,parse_ini_file(PATTERNS_DIR.$pattern_file,true));
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
        if (file_exists(NOT_FOUND_SCOPES)) {
            $data = (array) json_decode(file_get_contents(NOT_FOUND_SCOPES));
        }
        // add MAC to NOT_FOUND_SCOPES file
        $data[$scope_id] = time();
        file_put_contents(NOT_FOUND_SCOPES, json_encode($data));
    }
}

// Run app
$app->run();

