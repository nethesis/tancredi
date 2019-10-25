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

/*********************************
* GET /phones
**********************************/
$app->get('/phones', function(Request $request, Response $response) use ($app) {
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

/*********************************
* GET /phones/{mac}
**********************************/
$app->get('/phones/{mac}', function(Request $request, Response $response, array $args) use ($app) {
    $mac = $args['mac'];
    // get all scopes of type "phone"
    $scope = new \Tancredi\Entity\Scope($mac);
    $scope_data = $scope->getVariables();
    if (empty($scope_data)) {
        return $response->withStatus(404);
    }
    $results = array(
            'mac' => $mac,
            'model' => $scope->metadata['inheritFrom'],
            'display_name' => $scope->metadata['displayName'],
            'tok1' => \Tancredi\Entity\TokenManager::getToken1($mac),
            'tok2' => \Tancredi\Entity\TokenManager::getToken2($mac),
            'model_url' => "/tancredi/api/v1/models/" . $scope->metadata['inheritFrom'],
            'variables' => $scope_data
        );
    return $response->withJson($results,200);
});





































// Run app
$app->run();

