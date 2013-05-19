<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christiana Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */
	 
	 
	namespace Tuxxedo\Mvc;
	 
	class Url
	{
		/**
		 * Default routing stack.
		 * Stack is equal to a controller and action.
		 * Format:
		 * controller:action
		 *
		 * @var		String		Default routing stack string.
		 */
		const DEFAULT_STACK	= 'default:index';

		/**
		 * Complete list of all known and allowed arguments for the routes
		 * 
		 * @var 	Array	$args
		 */
		private static $args	= [
						'{=numeric}'	=> '([0-9]+)',
						'{=all}'	=> '([a-zA-Z0-9\.\-_%=]+)',
						'{=charater}'	=> '([a-zA-Z]+)',
						'{=*}'		=> '(.*)'];

		/**
		 * List of all registerd routes.
		 *
		 * @var 	Array 	$routes		List of the routes rigsterd
		 */
		private static $routes	= [
						'get'		=> [],
						'post'		=> [],
						'delete'	=> [],
						'put'		=> [],
						'group'		=> []];

		/**
		 * Placeholder for group route and late in run time used to hold the routing array
		 *
		 * @var		String|Array		Route to register and return.
		 */
		private static $route;

		/**
		 * Register a new url to the router,
		 * alongside with the options and callback function.
		 * 
		 * @param 	String 	$url 		The new url to register to the router
		 * @param 	String 	$options 	Options for the route
		 * @param	String	$stack		The controller action call stack.
		 * @param 	Closure	$callback 	Callback function to be executed if the route mathces
		 * @return 	Void
		 */
		private static function register($method,$route,Array $options,$stack,$callback = NULL)
		{
			if(isset(self::$route) && is_string(self::$route))
			{
				$callback	= (($route instanceof \Closure) ? $route : $callback);
				$route		= self::$route;
			}
			
			if(!is_string($route))
			{
				die('route as to be a string.');
			}

			if(!$callback instanceof \Closure  && $callback)
			{
				die('Callback has to be annymos function.');
			}

			if(isset(self::$routes[$method][$route]))
			{
				die('No duplicate routes is allowed');
			}
			
			$route	= strtolower(str_replace('//','/','/' . $route));
			self::$routes[$method][$route]	= Array(
								'callback'	=> $callback,
								'stack'		=> $stack,
								'options'	=> (isset(self::$routes['group'][$route]) ? array_merge(self::$routes['group'][$route],$options) : $options)
							);
			return;
		}

		/**
		 * Magic method to emulate request methods.
		 * 
		 * @param 	String	$name		Request method name. 
		 * @param 	Array	$args		Method arguments.
		 * @return	Void
		 */
		public static function __callStatic($name,$args)
		{
			if(sizeof($args) === 0)
			{
				die('Error, it takes at least to parameters to register url route.');
			}

			if(!isset(self::$route))
			{
				if(!isset(self::$routes[$name]))
				{
					die('Url method "' . $name . '" is not allowed');
				}

				if(!isset($args[0]) || !isset($args[0]) && !is_string($args[0]))
				{
					die('Error in first argument, has to bee string');
				}
				
				if(!isset($args[1]) || (isset($args[1]) && !in_array(gettype($args[1]),['string','array','object'])) || (isset($args[1]) && is_object($args[1]) === true && !$args[1] instanceof \Closure))
				{
					die('Error in the second argument, has to be either array, closure or a string<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				if((isset($args[2]) && !in_array(gettype($args[2]),['string','object'])) || (isset($args[2]) && is_object($args[2]) === true && !$args[2] instanceof \Closure))
				{
					die('Error in the third argument, has to be either array, closure or a string<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				if(isset($args[3]) && is_object($args[3]) === true && !$args[3] instanceof \Closure)
				{
					die('Error in the fourth argument, has to be an closure<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				$options	= is_array($args[1]) ? $args[1] : [];

				if(is_string($args[1]))
				{
					$stack	= explode(':',$args[1]);
				}
				elseif($args[1] instanceof \Closure)
				{
					$callback	= $args[1];
				}

				if(is_string($args[2]))
				{
					$stack	= explode(':',$args[2]);
				}
				elseif($args[2] instanceof \Closure)
				{
					$callback	= $args[2];
				}

				$route	= $args[0];
			} 
			else
			{
				if(!isset($args[0]) || (isset($args[1]) && !in_array(gettype($args[0]),['string','array','object'])) || (isset($args[0]) && is_object($args[0]) === true && !$args[0] instanceof \Closure))
				{
					die('Error in the first argument within a group collection, has to be an array<br>Route group: ' . self::$route . '<br>Request method: ' . $name);
				}

				if((isset($args[1]) && !in_array(gettype($args[1]),['string','array','object'])) || (isset($args[1]) && is_object($args[1]) === true && !$args[1] instanceof \Closure))
				{
					var_dump($args);
					die('Error in the second argument, has to be either array, closure or a string<br>Route: ' . self::$route . '<br>Request method: ' . $name);
				}

				if((isset($args[2]) && !in_array(gettype($args[2]),['string','object'])) || (isset($args[2]) && is_object($args[2]) === true && !$args[2] instanceof \Closure))
				{
					die('Error in the third argument, has to be either array, closure or a string<br>Route: ' . self::$route . '<br>Request method: ' . $name);
				}

				$options	= is_array($args[1]) ? $args[1] : [];

				if(is_string($args[1]))
				{
					$stack	= explode(':',$args[1]);
				}
				elseif($args[1] instanceof \Closure)
				{
					$stack		= self::DEFAULT_STACK;
					$callback	= $args[1];
				}

				if(is_string($args[2]))
				{
					$stack	= explode(':',$args[2]);
				}
				elseif($args[1] instanceof \Closure)
				{
					$stack		= self::DEFAULT_STACK;
					$callback	= $args[2];
				}
				$route	= self::$route;
			}

			$stack	= isset($stack)	? $stack : self::DEFAULT_STACK;
			self::register($name,$route,$options,$stack,$callback);
			return;
		}

		/**
		 * Register a group of routes, with the router.
		 *
		 * @param  [type] $route    [description]
		 * @param  [type] $options  [description]
		 * @param  [type] $callback [description]
		 * @return [type]           [description]
		 */
		public static function group($route,$options,$callback)
		{
			if(isset(self::$routes['group'][$route]))
			{
				die('No duplicate routes is allowed');
			}

			self::$routes['group'][$route]	= $options;
			self::$route			= $route;
			$callback($options);
			self::$route			= NULL;
			return;
		}

		/**
		 * Get the route for the current url
		 * 
		 * @param 	String $url		Current url string.
		 * @return	Array			An array of all options for the route.
		 */
		public static function _getRoute($url)
		{
			if(isset(self::$routes[REQUEST_METHOD][$url]))
			{
				return(self::$routes[REQUEST_METHOD][$url]);
			}
			
			$route	= self::_getUrlRouteMatch($url);

			if($route !== false)
			{
				unset($route['match'],$route['search'],$route['replace'],$route['url']);
				return($route);
			}

			return(false);
		}

		/**
		 * Regex match routes, to find the current url route
		 *
		 * @param 	String	$url		Current url.
		 * @return	Boolean|Array		Returns false on no match, otherwise array
		 */
		private function _getUrlRouteMatch($url,$request = REQUEST_METHOD)
		{
			if(empty(self::$routes[$request]))
			{
				return((Boolean) false);
			}

			list($search, $replace)	= array(array_keys(self::$args), array_values(self::$args));
			$options		= [
							'search'	=> $search,
							'replace'	=> $replace,
							'url'		=> $url,
							'route'		=> '',
							'params'	=> [],
							'match'		=> (Boolean) false];

			array_walk(self::$routes[$request],function($settings,$route,$options){
				$pattern	= '#^' . str_replace($options[0]['search'],$options[0]['replace'],$route) . '$#';
				if(preg_match($pattern,$options[0]['url'],$params))
				{
					$options[0]['params']	= array_slice($params,1);
					$options[0]['route']	= $route;
					$options[0]['match']	= (Boolean) true;
					return;
				}

			},[&$options]);
			return((Boolean) $options['match'] === true ? $options : (Boolean) false);
		}
	}