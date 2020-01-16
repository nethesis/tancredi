<?php

require_once '../vendor/autoload.php';

define("JSON_FLAGS",JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

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

// load auth middleware if it exists
if (array_key_exists('auth_class',$config) and !empty($config['auth_class'])) {
    $auth_class = "\\Tancredi\\Entity\\" . $config['auth_class'];
    $app->add(new $auth_class($config));
}

/*********************************
* GET /phones
**********************************/
$app->get('/phones', function(Request $request, Response $response) use ($app) {
    $this->logger->debug("GET /phones/");
    $scopes = $this->storage->listScopes('phone');
    $results = array();
    foreach ($scopes as $scopeId) {
        $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
        $scope_data = $scope->getVariables();
        $results[] = array(
            'mac' => $scopeId,
            'model' => $scope->metadata['inheritFrom'],
            'display_name' => $scope->metadata['displayName'],
            'model_url' => "/tancredi/api/v1/models/" . $scope->metadata['inheritFrom'],
            'phone_url' => "/tancredi/api/v1/models/" . $scopeId
        );
    }

    return $response->withJson($results,200,JSON_FLAGS);
});

/*********************************
* GET /phones/{mac}
**********************************/
$app->get('/phones/{mac}', function(Request $request, Response $response, array $args) use ($app) {
    $mac = $args['mac'];
    $this->logger->debug("GET /phones/" . $mac);
    // get all scopes of type "phone"
    if (!$this->storage->scopeExists($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    return $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
});

/*********************************
* POST /phones
**********************************/
$app->post('/phones', function (Request $request, Response $response, $args) {
    $post_data = $request->getParsedBody();
    $this->logger->debug("POST /phones " . json_encode($post_data));
    $mac = $post_data['mac'];
    $model = $post_data['model'];
    $display_name = ($post_data['display_name'] ? $post_data['display_name'] : "" );
    $variables = $post_data['variables'];
    if (empty($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
            'title' => 'Missing MAC address'
        );
        $response = $response->withJson($results,400,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    if ($this->storage->scopeExists($mac)) {
        // Error: scope is already configured
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#phone-exists',
            'title' => 'The phone mac address is already registered'
        );
        $response = $response->withJson($results,409,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
    $scope->metadata['displayName'] = $display_name;
    $scope->metadata['inheritFrom'] = (empty($model) ? 'global' : $model);
    $scope->metadata['model'] = $model;
    $scope->metadata['scopeType'] = "phone";
    $scope->setVariables($variables);
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , TRUE); // create first time access token
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , FALSE); // create token
    $response = $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),201,JSON_FLAGS);
    $response = $response->withHeader('Location', '/tancredi/api/v1/phones/' . $mac);
    return $response;
});

/*********************************
* PATCH /phones/{mac}
**********************************/
$app->patch('/phones/{mac}', function (Request $request, Response $response, $args) {
    $mac = $args['mac'];
    $patch_data = $request->getParsedBody();
    $this->logger->debug("PATCH /phones/" .$mac . " " . json_encode($patch_data));

    if (!$this->storage->scopeExists($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }

    if (array_key_exists('mac',$patch_data) or array_key_exists('model_url',$patch_data) or array_key_exists('tok1',$patch_data) or array_key_exists('tok2',$patch_data)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#read-only-attribute',
            'title' => 'Cannot change a read-only attribute'
        );
        $response = $response->withJson($results,403,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }

    if (array_key_exists('model',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
        $scope->metadata['inheritFrom'] = $patch_data['model'];
        $scope->metadata['model'] = $patch_data['model'];
        $scope->setVariables();
        return $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
    }
    if (array_key_exists('variables',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
        $scope->setVariables($patch_data['variables']);
        return $response->withStatus(204);
    }
    return $response->withStatus(400);
});

/*********************************
* DELETE /phones/{mac}
**********************************/
$app->delete('/phones/{mac}', function (Request $request, Response $response, $args) {
    $mac = $args['mac'];
    $this->logger->debug("DELETE /phones/" .$mac);

    if (!$this->storage->scopeExists($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    \Tancredi\Entity\TokenManager::deleteToken(\Tancredi\Entity\TokenManager::getToken1($mac));
    \Tancredi\Entity\TokenManager::deleteToken(\Tancredi\Entity\TokenManager::getToken2($mac));
    $this->storage->deleteScope($mac);
    return $response->withStatus(204);
});

/*********************************
* GET /models
**********************************/
$app->get('/models', function(Request $request, Response $response) use ($app) {
    $this->logger->debug("GET /models/");
    $scopes = $this->storage->listScopes('model');
    $results = array();
    foreach ($scopes as $scopeId) {
        $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
        $scope_data = $scope->getVariables();
        $results[] = array(
            'name' => $scopeId,
            'display_name' => $scope->metadata['displayName'],
            'model_url' => "/tancredi/api/v1/models/" . $scopeId
        );
    }
    return $response->withJson($results,200,JSON_FLAGS);
});

/*********************************
* GET /models/{id}
**********************************/
$app->get('/models/{id}[/version/{version:original}]', function(Request $request, Response $response, array $args) use ($app) {
    $id = $args['id'];
    if (array_key_exists('version',$args) && $args['version'] == 'original') {
        $original = true;
    } else {
        $original = false;
    }
    $query = $request->getQueryParams();
    $this->logger->debug("GET /models/" . $id . " " . json_encode($query));
    // get all scopes of type "model"
    if (!$this->storage->scopeExists($id) or $this->storage->getScopeMeta($id,'scopeType') !== 'model') {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    if (array_key_exists('inherit',$query) and $query['inherit'] == 1) {
        $results = getModelScope($id, $this->storage, $this->logger, true, $original);
    } else {
        $results = getModelScope($id, $this->storage, $this->logger, false, $original);
    }
    return $response->withJson($results,200,JSON_FLAGS);
});

/*********************************
* POST /models
**********************************/
$app->post('/models', function (Request $request, Response $response, $args) {
    $post_data = $request->getParsedBody();
    $this->logger->debug("POST /models " . json_encode($post_data));
    $id = $post_data['name'];
    $display_name = ($post_data['display_name'] ? $post_data['display_name'] : "" );
    $variables = $post_data['variables'];
    if (empty($id)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
            'title' => 'Missing model name'
        );
        $response = $response->withJson($results,400,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
   }
   if ($this->storage->scopeExists($id)) {
        // Error: scope is already configured
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#phone-exists',
            'title' => 'The model name is already registered'
        );
        $response = $response->withJson($results,409,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }
    $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
    $scope->metadata['displayName'] = $display_name;
    $scope->metadata['inheritFrom'] = 'globals';
    $scope->metadata['scopeType'] = "model";
    $scope->setVariables($variables);
    $response = $response->withJson(getModelScope($id, $this->storage, $this->logger),201,JSON_FLAGS);
    $response = $response->withHeader('Location', '/tancredi/api/v1/models/' . $id);
    return $response;
});

/*********************************
* PATCH /models/{id}
**********************************/
$app->patch('/models/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $patch_data = $request->getParsedBody();
    $this->logger->debug("PATCH /models/" .$id . " " . json_encode($patch_data));

    if (!$this->storage->scopeExists($id)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
	return $response;
    }

    if (array_key_exists('name',$patch_data)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#read-only-attribute',
            'title' => 'Cannot change a read-only attribute'
        );
        $response = $response->withJson($results,403,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    }

    if (array_key_exists('variables',$patch_data) or array_key_exists('display_name',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
        if (array_key_exists('display_name',$patch_data)) {
            $scope->metadata['displayName'] = $patch_data['display_name'];
        }
        if (array_key_exists('variables',$patch_data)) {
             $scope->setVariables($patch_data['variables']);
        } else {
             $scope->setVariables();
        }
        return $response->withStatus(204);
    }
    return $response->withStatus(400);
});

/*********************************
* DELETE /models/{id}
**********************************/
$app->delete('/models/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $this->logger->debug("DELETE /models/" .$id);

    if (!$this->storage->scopeExists($id)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
	return $response;
    }

    if ($this->storage->scopeInUse($id)) {
         $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#resource-in-use',
            'title' => 'The resource is in use by other resources and cannot be deleted'
        );
        $response = $response->withJson($results,409,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
	return $response;
    }

    $this->storage->deleteScope($id);
    return $response->withStatus(204);
});

/*********************************
* GET /defaults
**********************************/
$app->get('/defaults', function(Request $request, Response $response) use ($app) {
    $this->logger->debug("GET /defaults");
    $scope = new \Tancredi\Entity\Scope('globals', $this->storage, $this->logger);
    $scope_data = $scope->getVariables();
    return $response->withJson($scope_data,200,JSON_FLAGS);
});

/*********************************
* PATCH /defaults
**********************************/
$app->patch('/defaults', function (Request $request, Response $response, $args) {
    $patch_data = $request->getParsedBody();
    $this->logger->debug("PATCH /defaults" . json_encode($patch_data));

    $scope = new \Tancredi\Entity\Scope('globals', $this->storage, $this->logger);
    foreach ($patch_data as $patch_key => $patch_value) {
        if (is_null($patch_value)) {
            unset($scope->data[$patch_key]);
            unset($patch_data[$patch_key]);
        }
    }

    $scope->setVariables($patch_data);

    return $response->withStatus(204);
});

function getModelScope($id,$storage,$logger,$inherit = false, $original = false) {
    $scope = new \Tancredi\Entity\Scope($id, $storage, $logger, null, $original);
    if ($inherit) {
        $scope_data = $scope->getVariables();
    } else {
        $scope_data = $scope->data;
    }
    $results = array(
        'name' => $id,
        'display_name' => $scope->metadata['displayName'],
        'variables' => $scope_data,
        'model_url' => "/tancredi/api/v1/models/" . $scope->metadata['inheritFrom']
    );
    return $results;
}


function getPhoneScope($mac,$storage,$logger,$inherit = false) {
    $scope = new \Tancredi\Entity\Scope($mac, $storage, $logger, null);
    if ($inherit) {
        $scope_data = $scope->getVariables();
    } else {
        $scope_data = $scope->data;
    }
    $results = array(
        'mac' => $mac,
        'model' => $scope->metadata['inheritFrom'],
        'display_name' => $scope->metadata['displayName'],
        'tok1' => \Tancredi\Entity\TokenManager::getToken1($mac),
        'tok2' => \Tancredi\Entity\TokenManager::getToken2($mac),
        'variables' => $scope_data,
        'model_url' => "/tancredi/api/v1/models/" . $scope->metadata['inheritFrom']
    );
    return $results;
}

// Run app
$app->run();

