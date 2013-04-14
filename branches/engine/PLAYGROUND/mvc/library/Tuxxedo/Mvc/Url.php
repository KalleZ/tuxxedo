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
		 * List of all registerd routes.
		 *
		 * @var 	Array 	$routes		List of the routes rigsterd
		 */
		private static $routes	= Array(
						'get'		=> Array(),
						'post'		=> Array(),
						'delete'	=> Array(),
						'put'		=> Array(),
						'group'		=> Array());

		/**
		 * Placeholder for group route
		 */
		private static $route;

		/**
		 * Register a new url to the router,
		 * alongside with the options and callback function.
		 * 
		 * @param 	String 	$url 		The new url to register to the router
		 * @param 	String 	$options 	Options for the route
		 * @param 	Closure	$callback 	Callback function to be executed if the route mathces
		 * @return 	Void
		 */
		private static function register($method,$route,Array $options,$callback = NULL)
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
			//var_dump($options);
			self::$routes[$method][$route]	= Array(
								'function'	=> $callback,
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
				//var_dump($args[1]);
				if(!isset($args[1]))
				{
					die('Error in the second argument, has to be either array or annymos function<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				if(isset($args[2]) && !$args[2] instanceof \Closure)
				{
					die('Error in the third argument, has to be an annymos function<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}
				self::register($name,$args[0],(is_array($args[1]) ? $args[1] : Array()),(is_array($args[1]) ? $args[2] : $args[1]));
			}
			else
			{
				if(isset($args[0]) && ((!$args[0] instanceof \Closure)) && (!is_array($args[0])))
				{
					die('Error in the first argument within a group collection, has to be an array or annymos function<br>Route group: ' . self::$route . '<br>Request method: ' . $name);
				}
				self::register($name,self::$route,(is_array($args[0]) ? $args[0] : Array()),(!is_array($args[0]) ? $args[0] : $args[1]));
			}
			return;
		}

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

		public static function test()
		{
			return(self::$routes);
		}
	}