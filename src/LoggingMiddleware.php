<?php namespace Tancredi;

/*
 * Copyright (C) 2020 Nethesis S.r.l.
 * http://www.nethesis.it - sviluppo@nethesis.it
 *
 * This script is part of Tancredi.
 *
 * Tancredi is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * Tancredi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tancredi.  If not, see COPYING.
 */

/**
 * Send a debug message to logger for each request
 */
class LoggingMiddleware
{
    private $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    public function __invoke($request, $response, $next)
    {
        $response = $next($request, $response);
        $context = [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'status' => $response->getStatusCode(),
            'request' => $this->formatPayload(strval($request->getBody())),
            'response' => $this->formatPayload(strval($response->getBody())),
        ];
        $this->container['logger']->debug('{method} {uri} ({status}) [{request}, {response}]', $context);
        return $response;
    }

    protected function formatPayload($payload) 
    {
        if( ! is_string($payload)) {
            return 'null';
        }
        if(strlen($payload) == 0) {
            return '""';
        }
        if(strlen($payload) <= 128) {
            return $payload;
        }
        return sprintf('%s ...(total length %s)', substr($payload, 0, 128), strlen($payload));
    }
}
