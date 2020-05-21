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

define("JSON_FLAGS",JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Http\UploadedFile;

$app = new \Slim\App;
$container = $app->getContainer();
$container['app'] = function($c) use ($app) {
    return $app;
};
$container['config'] = $config;
$container['logger'] = function($c) {
    return \Tancredi\LoggerFactory::createLogger('api-v1', $c);
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

// Register the client IP address for logging
$upstreamProxies = array_map('trim', explode(',', isset($config['upstream_proxies']) ? $config['upstream_proxies'] : ''));
$app->add(new \RKA\Middleware\IpAddress( ! empty($upstreamProxies), $upstreamProxies));

// load auth middleware if it exists
if (array_key_exists('auth_class',$config) and !empty($config['auth_class'])) {
    $auth_class = "\\Tancredi\\Entity\\" . $config['auth_class'];
    $app->add(new $auth_class($config));
}

// Add request/response logging middleware
$app->add(new \Tancredi\LoggingMiddleware($container));

/*********************************
* GET /phones
**********************************/
$app->get('/phones', function(Request $request, Response $response) use ($app) {
    global $config;
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
            'phone_url' => $config['api_url_path'] . "phones/" . $scopeId
        );
    }

    $response = $response->withJson($results,200,JSON_FLAGS);
    return $response;
});

/*********************************
* GET /phones/{mac}
**********************************/
$app->get('/phones/{mac}', function(Request $request, Response $response, array $args) use ($app) {
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
    $response = $response->withJson(\Tancredi\Entity\Scope::getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
    return $response;
});

/*********************************
* POST /phones
**********************************/
$app->post('/phones', function (Request $request, Response $response, $args) {
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
    $scope->metadata['inheritFrom'] = $model;
    $scope->metadata['scopeType'] = "phone";
    $scope->setVariables($variables);
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , TRUE); // create first time access token
    \Tancredi\Entity\TokenManager::createToken(uniqid($prefix = rand(), $more_entropy = TRUE), $mac , FALSE); // create token
    $response = $response->withJson(\Tancredi\Entity\Scope::getPhoneScope($mac, $this->storage, $this->logger),201,JSON_FLAGS);
    $response = $response->withHeader('Location', '/tancredi/api/v1/phones/' . $mac);
    return $response;
});

/*********************************
* PATCH /phones/{mac}
**********************************/
$app->patch('/phones/{mac}', function (Request $request, Response $response, $args) {
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
        return $response;
    }

    $readonly_params = ['mac', 'short_mac', 'model_url', 'tok1', 'tok2', 'provisioning_url1', 'provisioning_url2'];
    if (array_intersect($readonly_params, array_keys($patch_data))) {
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
        $scope->setVariables();
        $response = $response->withJson(\Tancredi\Entity\Scope::getPhoneScope($mac, $this->storage, $this->logger),200,JSON_FLAGS);
        return $response;
    }
    if (array_key_exists('variables',$patch_data)) {
        $scope = new \Tancredi\Entity\Scope($mac, $this->storage, $this->logger);
        $scope->setVariables($patch_data['variables']);
        $response = $response->withJson(\Tancredi\Entity\Scope::getPhoneScope($mac, $this->storage, $this->logger), 200, JSON_FLAGS);
        return $response;
    }
    $response = $response->withStatus(400);
    return $response;
});

/*********************************
* DELETE /phones/{mac}
**********************************/
$app->delete('/phones/{mac}', function (Request $request, Response $response, $args) {
    $mac = $args['mac'];

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
    $response = $response->withStatus(204);
    return $response;
});

/*********************************
* GET /models
**********************************/
$app->get('/models', function(Request $request, Response $response) use ($app) {
    global $config;
    $query_params = $request->getQueryParams();
    $scopes = $this->storage->listScopes('model');
    $results = array();

    $filter_used = FALSE;
    if (array_key_exists('filter', $query_params) && array_key_exists('used', $query_params['filter'])) {
        $filter_used = TRUE;
        // get all scopes that are inherited by other scopes
        $inherited_scopes = array();
        foreach ($this->storage->listScopes() as $scopeId) {
            $scope = new \Tancredi\Entity\Scope($scopeId, $this->storage, $this->logger);
            if ( ! empty($scope->metadata['inheritFrom']) && array_search($scope->metadata['inheritFrom'],$inherited_scopes) === FALSE) {
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
    return $response;
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
    $response = $response->withJson($results,200,JSON_FLAGS);
    return $response;
});

/*********************************
* POST /models
**********************************/
$app->post('/models', function (Request $request, Response $response, $args) {
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
        return $response;
    }
    if (preg_match('/^[a-zA-Z0-9_\-\.]+$/',$id) == 0) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
            'title' => 'Illegal character(s) in model name'
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
        $response = $response->withJson(getModelScope($id, $this->storage, $this->logger, false, false), 200, JSON_FLAGS);
        return $response;
    }
    $response = $response->withStatus(400);
    return $response;
});

/*********************************
* DELETE /models/{id}
**********************************/
$app->delete('/models/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];

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
    $response = $response->withStatus(204);
    return $response;
});

/*********************************
* GET /defaults
**********************************/
$app->get('/defaults', function(Request $request, Response $response) use ($app) {
    $scope = new \Tancredi\Entity\Scope('defaults', $this->storage, $this->logger);
    $scope_data = $scope->getVariables();
    $response = $response->withJson($scope_data,200,JSON_FLAGS);
    return $response;
});

/*********************************
* PATCH /defaults
**********************************/
$app->patch('/defaults', function (Request $request, Response $response, $args) {
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
    return $response;
});

/*********************************
* POST /firmware
**********************************/
$app->post('/firmware', function(Request $request, Response $response) use ($app) {
    $uploadedFile = array_pop($request->getUploadedFiles());
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        if (! preg_match('/^[a-zA-Z0-9\-_\.()]+$/', $uploadedFile->getClientFilename())) {
            $results = array(
                'type' => 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
                'title' => 'Invalid file name'
            );
            $response = $response->withJson($results,404,JSON_FLAGS);
            $response = $response->withHeader('Content-Type', 'application/problem+json');
            $response = $response->withHeader('Content-Language', 'en');
            return $response;
        }
        $uploadedFile->moveTo($this->config['rw_dir'] . 'firmware' . '/' . $uploadedFile->getClientFilename());
        $realfile = realpath($this->config['rw_dir'] . 'firmware' . '/' . $uploadedFile->getClientFilename());
        if( ! $realfile || dirname($realfile) != ($this->config['rw_dir'] . 'firmware')) {
            // File not found
            return $response->withStatus(404);
        }
        return $response->withStatus(204);
    }
    return $response->withStatus(500);
});

/*********************************
* GET /firmware
**********************************/
$app->get('/firmware', function(Request $request, Response $response) use ($app) {
    $files = glob($this->config['rw_dir'] . 'firmware/*');
    $res = array();
    foreach ($files as $file) {
        $stats = stat($file);
        $res[] = array(
            'name' => basename($file),
            'size' => $stats['size'],
            'mtime' => $stats['mtime']
        );
    }
    $response = $response->withJson($res,200,JSON_FLAGS);
    return $response;
});

/*********************************
* DELETE /firmware
**********************************/
$app->delete('/firmware/{file}', function(Request $request, Response $response, $args) use ($app) {
    $file = $args['file'];
    $realfile = realpath($this->config['rw_dir'] . 'firmware' . '/' . $file);
    if( ! $realfile  || dirname($realfile) != ($this->config['rw_dir'] . 'firmware')) {
        $results = array(
            'type' => 'https://github.com/nethesis/tancredi/wiki/problems#not-found',
            'title' => 'Resource not found'
        );
        $response = $response->withJson($results,404,JSON_FLAGS);
        $response = $response->withHeader('Content-Type', 'application/problem+json');
        $response = $response->withHeader('Content-Language', 'en');
        return $response;
    } elseif (unlink($this->config['rw_dir'] . 'firmware/' . $file)) {
        $response = $response->withStatus(204);
        return $response;
    }
    $response = $response->withStatus(500);
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
        'model_url' => $config['api_url_path'] . "models/" . $id,
    );
    return $results;
}

// Run app
$app->run();
