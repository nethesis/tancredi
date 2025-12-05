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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Send a debug message to logger for each request
 */
class LoggingMiddleware implements MiddlewareInterface
{
    private $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $context = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $response->getStatusCode(),
            'request' => $this->formatPayload((string) $request->getBody()),
            'response' => $this->formatPayload((string) $response->getBody()),
        ];
        $this->logger->debug('{method} {uri} ({status}) [{request}, {response}]', $context);
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
