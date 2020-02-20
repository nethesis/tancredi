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
    global $config;
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $scopes = $this->storage->listScopes('phone');
    $results = array();
    foreach ($scopes as $scopeId) {
        $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
        $scope_data = $scope->getVariables();
        $results[] = array(
            'mac' => $scopeId,
            'model' => $scope->metadata['inheritFrom'],
            'display_name' => $scope->metadata['displayName'],
            'model_url' => $config['api_url_path'] . "models/" . $scope->metadata['inheritFrom'],
            'phone_url' => $config['api_url_path'] . "models/" . $scopeId
        );
    }

    $response = $response->withJson($results,200,JSON_FLAGS);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* GET /phones/{mac}
**********************************/
$app->get('/phones/{mac}', function(Request $request, Response $response, array $args) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $mac = $args['mac'];
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
    $response = $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* POST /phones
**********************************/
$app->post('/phones', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $post_data = $request->getParsedBody();
    $mac = $post_data['mac'];
    $model = $post_data['model'];
    $display_name = ($post_data['display_name'] ? $post_data['display_name'] : "" );
    $variables = array_key_exists('variables',$post_data) ? $post_data['variables'] : array();
    if (empty($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
            'title' => 'Missing MAC address'
        );
        $response = $response->withJson($results,400,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
    $scope->metadata['displayName'] = $display_name;
    $scope->metadata['inheritFrom'] = (empty($model) ? 'defaults' : $model);
    $scope->metadata['scopeType'] = "phone";
    $scope->setVariables($variables);
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , TRUE); // create first time access token
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , FALSE); // create token
    $response = $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),201,JSON_FLAGS);
    $response = $response->withHeader('Location', '/tancredi/api/v1/phones/' . $mac);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* PATCH /phones/{mac}
**********************************/
$app->patch('/phones/{mac}', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $mac = $args['mac'];
    $patch_data = $request->getParsedBody();

    if (!$this->storage->scopeExists($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }

    if (array_key_exists('model',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
        $scope->metadata['inheritFrom'] = $patch_data['model'];
        $scope->setVariables();
        $response = $response->withJson(getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    if (array_key_exists('variables',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
        $scope->setVariables($patch_data['variables']);
        $response = $response->withStatus(204);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    $response = $response->withStatus(400);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* DELETE /phones/{mac}
**********************************/
$app->delete('/phones/{mac}', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $mac = $args['mac'];

    if (!$this->storage->scopeExists($mac)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    \Tancredi\Entity\TokenManager::deleteToken(\Tancredi\Entity\TokenManager::getToken1($mac));
    \Tancredi\Entity\TokenManager::deleteToken(\Tancredi\Entity\TokenManager::getToken2($mac));
    $this->storage->deleteScope($mac);
    $response = $response->withStatus(204);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* GET /models
**********************************/
$app->get('/models', function(Request $request, Response $response) use ($app) {
    global $config;
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $query_params = $request->getQueryParams();
    $this->logger->debug("GET /models/ " . json_encode($query_params));
    $scopes = $this->storage->listScopes('model');
    $results = array();

    $filter_used = FALSE;
    if (array_key_exists('filter', $query_params) && array_key_exists('used', $query_params['filter'])) {
        $filter_used = TRUE;
        // get all scopes that are inherited by other scopes
        $inherited_scopes = array();
        foreach ($this->storage->listScopes() as $scopeId) {
            $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
            if (array_search($scope->metadata['inheritFrom'],$inherited_scopes) === FALSE) {
                $inherited_scopes[] = $scope->metadata['inheritFrom'];
            }
        }
    }

    foreach ($scopes as $scopeId) {
        if ($filter_used && array_search($scopeId,$inherited_scopes) === FALSE) {
            continue;
        }
        $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
        $scope_data = $scope->getVariables();
        $results[] = array(
            'name' => $scopeId,
            'display_name' => $scope->metadata['displayName'],
            'model_url' => $config['api_url_path'] . "models/" . $scopeId
        );
    }
    $response = $response->withJson($results,200,JSON_FLAGS);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* GET /models/{id}
**********************************/
$app->get('/models/{id}[/version/{version:original}]', function(Request $request, Response $response, array $args) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $id = $args['id'];
    if (array_key_exists('version',$args) && $args['version'] == 'original') {
        $original = true;
    } else {
        $original = false;
    }
    $query = $request->getQueryParams();
    // get all scopes of type "model"
    if (!$this->storage->scopeExists($id) or $this->storage->getScopeMeta($id,'scopeType') !== 'model') {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    if (array_key_exists('inherit',$query) and $query['inherit'] == 1) {
        $results = getModelScope($id, $this->storage, $this->logger, true, $original);
    } else {
        $results = getModelScope($id, $this->storage, $this->logger, false, $original);
    }
    $response = $response->withJson($results,200,JSON_FLAGS);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* POST /models
**********************************/
$app->post('/models', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $post_data = $request->getParsedBody();
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
    $scope->metadata['displayName'] = $display_name;
    $scope->metadata['inheritFrom'] = 'defaults';
    $scope->metadata['scopeType'] = "model";
    $scope->setVariables($variables);
    $response = $response->withJson(getModelScope($id, $this->storage, $this->logger),201,JSON_FLAGS);
    $response = $response->withHeader('Location', '/tancredi/api/v1/models/' . $id);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* PATCH /models/{id}
**********************************/
$app->patch('/models/{id}', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $id = $args['id'];
    $patch_data = $request->getParsedBody();

    if (!$this->storage->scopeExists($id)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $response = $response->withStatus(204);
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
        return $response;
    }
    $response = $response->withStatus(400);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* DELETE /models/{id}
**********************************/
$app->delete('/models/{id}', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $id = $args['id'];

    if (!$this->storage->scopeExists($id)) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
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
        $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
	return $response;
    }

    $this->storage->deleteScope($id);
    $response = $response->withStatus(204);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* GET /defaults
**********************************/
$app->get('/defaults', function(Request $request, Response $response) use ($app) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $scope = new \Tancredi\Entity\Scope('defaults', $this->storage, $this->logger);
    $scope_data = $scope->getVariables();
    $response = $response->withJson($scope_data,200,JSON_FLAGS);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

/*********************************
* PATCH /defaults
**********************************/
$app->patch('/defaults', function (Request $request, Response $response, $args) {
    $this->logger->debug($request->getMethod() ." " . $request->getUri() . " " . json_encode($request->getParsedBody()));
    $patch_data = $request->getParsedBody();

    $scope = new \Tancredi\Entity\Scope('defaults', $this->storage, $this->logger);
    foreach ($patch_data as $patch_key => $patch_value) {
        if (is_null($patch_value)) {
            unset($scope->data[$patch_key]);
            unset($patch_data[$patch_key]);
        }
    }

    $scope->setVariables($patch_data);
    $response = $response->withStatus(204);
    $this->logger->debug($request->getMethod() ." " . $request->getUri() .' Result:' . $response->getStatusCode() . ' ' . __FILE__.':'.__LINE__);
    return $response;
});

function getModelScope($id,$storage,$logger,$inherit = false, $original = false) {
    global $config;
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
        'model_url' => $config['api_url_path'] . "models/" . $scope->metadata['inheritFrom']
    );
    return $results;
}


function getPhoneScope($mac,$storage,$logger,$inherit = false) {
    global $config;
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
        'model_url' => $config['api_url_path'] . "models/" . $scope->metadata['inheritFrom']
    );
    return $results;
}

// Run app
$app->run();

