<?php
namespace web\lib\mvc;

public class Mvc{

	public static function run($request){

		$config = $configManager::loadSysConfig('config');
		$container = new \stdClass();
		$router = self::matchRouter($config['routers']);
		
		$container->request = self::formatRequest($request, $router->model);
		$container->response = [];
		$container->config = $config
		$container->router = $router;

		$controller = $router->controller;
		$action = $router->action;
		$result = $controller->{$action}($container);

		return self::render($result);
	}

	public static function formatRequest($request ,$model = null){
		if($model)
			$request->model = self::makeModel($model);
		return $request;	
	}

	public static function matchRouter($routerConfig){

		return routerFacory::getRouter($routerConfig);
	}

	public static function makrModel($modelName){

		return ModelFactory::getModel($modelName);
	}

	public static function render($result){
		
		if(!isset($result['data'])){
			throw new Exception("data is not set", 1);
		}

		if(!isset($result['render'])){
			throw new Exception("render is not set", 1);
		}

		$render = renderFactory::getRender($result['render']);

		return $render->rende($result['data']);
	}


}