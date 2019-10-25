<?php namespace Tancredi;

require '../vendor/autoload.php';
include_once(__DIR__ . '/../src/functions.inc.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \Monolog\Logger;

use \Monolog\Handler\StreamHandler;

ini_set('date.timezone', 'UTC');

$log = new Logger('Tancredi');

$log->pushHandler(new StreamHandler('/var/log/pbx/tancredi.log', Logger::DEBUG));

$app = new \Slim\App;

$app->get('/phones', function(Request $request, Response $response) use ($app) {
    global $log;
    // get all scopes of type "phone"
    $scopes = listScopes('phone');
    $results = array();
    foreach ($scopes as $scopeId) {
        $scope = new \Tancredi\Entity\Scope($scopeId);
        $scope_data = $scope->getVariables();
        $results[] = array(
            'mac' => $scopeId,
            'model' => $scope->metadata['inheritFrom'],
            'display_name' => $scope->metadata['displayName'],
            'model_url' => "/tancredi/api/v1/models/" . $scope->metadata['inheritFrom'],
            'phone_url' => "/tancredi/api/v1/models/" . $scopeId
        );
    }
    return $response->withJson($results,200);
});





































// Run app
$app->run();

