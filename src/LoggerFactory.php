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
 * Instantiate and configure a Monolog\Logger instancea
 *
 */
class LoggerFactory 
{
    public static function createLogger(\ArrayAccess $dc)
    {
        $logger = new \Monolog\Logger('tancredi');
        $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

        $config = $dc['config'];
        if( ! empty($config['logfile'])) {
            $handler = new \Monolog\Handler\StreamHandler($config['logfile']);
            $formatter = new \Monolog\Formatter\LineFormatter("[%datetime%] %level_name%: %message%\n");
        } else {
            $handler = new \Monolog\Handler\ErrorLogHandler();
            // We assume the error_log already adds a time stamp to log messages:
            $formatter = new \Monolog\Formatter\LineFormatter("%level_name%: %message%");
        }
        $handler->setFormatter($formatter);

        if($config['loglevel'] == 'ERROR') {
            $handler->setLevel($logger::ERROR);
        } elseif ($config['loglevel'] == 'WARNING') {
            $handler->setLevel($logger::WARNING);
        } elseif ($config['loglevel'] == 'INFO') {
            $handler->setLevel($logger::INFO);
        } else {
            $handler->setLevel($logger::DEBUG);
        }

        $logger->pushHandler($handler);
        \Monolog\ErrorHandler::register($logger);
        return $logger;
    }

}