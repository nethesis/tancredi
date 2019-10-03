<?php namespace Tancredi;

require '../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;

$app = new \Slim\Slim();


$app->get('/:data+', function ($data) use ($app) { 
      //echo print_r($data,true);
      //echo json_encode($app->request()->headers()->all());
      $request_filename = array_pop($data);
      if (!empty($data) and count($data) === 3) {
          $mac_address = array_pop($data);
          $model = array_pop($data);
          $brand = array_pop($data);
      }

      // Get data from filename
      $pattern_dir = '../data/patterns.d/';
      if (isset($brand) && !empty($brand) && file_exists($pattern_dir . $brand . '.php')) {
          // Brand already known, use file specific for brand
          include($pattern_dir . $brand . '.php');
      } else {
          // search for a pattern in all pattern files
          foreach (scandir($pattern_dir) as $pattern_file) {
              if ($pattern_file === '.' or $pattern_file === '..') continue;
              include($pattern_dir . $pattern_file);
          }
      }

      //DEBUG
      foreach (['brand','model','mac_address','template'] as $var) {
          if (isset($$var)) {
              echo "$var: ".$$var."</br>\n";
          }
      }

      // TODO Get specific template file
      $template_dir = '../data/templates-custom/';
      $files = scandir($template_dir);
      $tmp_template_name = $template;
      if (isset($model)) $tmp_template_name = strstr('${MODEL}',$model,$tmp_template_name);
      if (isset($mac_address)) $tmp_template_name = strstr('${MAC_ADDRESS}',$mac_address,$tmp_template_name);
      foreach ($files as $file) {
          if ($file === $tmp_template_name) $template_name = $template_dir . $file;
      }
      // TODO Get generic template file

      

      // Get data
      if (isset($mac_address)) {
          $scope = new \Tancredi\Entity\Scope($mac_address,'phone');
          if (!isset($scope->vars['metadata']['inheritFrom']) or $scope->vars['metadata']['inheritFrom'] == "" and isset($model)) {
              $scope->setParent($brand.'-'.$model);
          } else {
              $scope->setParent('global');
          }
      } elseif (isset($model)){
          $scope = new \Tancredi\Entity\Scope($brand.'-'.$model,'model','global');
      } else {
          $scope = new \Tancredi\Entity\Scope('global','global');
      }

      // TODO return result
      // DEBUG
      echo print_r($scope->getVariables(),true);
});

// Run app
$app->run();
