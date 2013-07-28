<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christian Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */
	 
	 
	/**
	 * Tuxxedo Model-View-Controller bundle
	 */
	namespace Tuxxedo\Mvc\Router;
	 
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
			self::$routes[$method][$route]	= [
								'callback'	=> $callback,
								'stack'		=> $stack,
								'options'	=> (isset(self::$routes['group'][$route]) ? array_merge(self::$routes['group'][$route],$options) : $options)
							];
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
				
				if(!isset($args[1]) || (isset($args[1]) && !in_array(gettype($args[1]),['string','array','object'])) || (isset($args[1]) && is_object($args[1]) === true && !($args[1] instanceof \Closure)))
				{
					die('Error in the second argument, has to be either array, closure or a string<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				if((isset($args[2]) && !in_array(gettype($args[2]),['string','object'])) || (isset($args[2]) && is_object($args[2]) === true && !($args[2] instanceof \Closure)))
				{
					die('Error in the third argument, has to be either array, closure or a string<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				if(isset($args[3]) && is_object($args[3]) === true && !($args[3] instanceof \Closure))
				{
					die('Error in the fourth argument, has to be an closure<br>Route: ' . $args[0] . '<br>Request method: ' . $name);
				}

				$options	= isset($args[1]) && is_array($args[1]) ? $args[1] : [];

				if(isset($args[1]) && is_string($args[1]))
				{
					$stack	= explode(':',$args[1]);
				}

				if(isset($args[2]) && is_string($args[2]))
				{
					$stack	= explode(':',$args[2]);
				}
				
				$route	= $args[0];
			} 
			else
			{
				if(!isset($args[0]) || (isset($args[1]) && !in_array(gettype($args[0]),['string','array','object'])) || (isset($args[0]) && is_object($args[0]) === true && !($args[0] instanceof \Closure)))
				{
					die('Error in the first argument within a group collection, has to be an array<br>Route group: ' . self::$route . '<br>Request method: ' . $name);
				}

				if((isset($args[1]) && !in_array(gettype($args[1]),['string','array','object'])) || (isset($args[1]) && is_object($args[1]) === true && !($args[1] instanceof \Closure)))
				{
					die('Error in the second argument, has to be either array, closure or a string<br>Route: ' . self::$route . '<br>Request method: ' . $name);
				}

				if((isset($args[2]) && !in_array(gettype($args[2]),['string','object'])) || (isset($args[2]) && is_object($args[2]) === true && !($args[2] instanceof \Closure)))
				{
					die('Error in the third argument, has to be either array, closure or a string<br>Route: ' . self::$route . '<br>Request method: ' . $name);
				}
	
				if(isset($args[0]) && is_array($args[0]))
				{
					$options	= \array_merge($args[0],self::$routes['group'][self::$route]);
				}
				elseif(isset($args[1]) && is_array($args[1]))
				{
					$options	= \array_merge($args[1],self::$routes['group'][self::$route]);
				}
				else
				{
					$options	= self::$routes['group'][self::$route];
				}

				if(isset($args[1]) && is_string($args[1]))
				{
					$stack	= explode(':',$args[1]);
				}

				if(isset($args[2]) && is_string($args[2]))
				{
					$stack	= explode(':',$args[2]);
				}

				$route	= self::$route;
			}

			if(isset($callback) === false && ($args[\sizeof($args) - 1] instanceof \Closure) === true)
			{
				$callback	= $args[\sizeof($args) - 1];
			}
			elseif(isset($callback) === false && ($args[\sizeof($args) - 1] instanceof \Closure) === false)
			{
				$callback	= NULL;
			}
			$stack		= isset($stack)	? $stack : self::DEFAULT_STACK;
			
			self::register($name,$route,$options,$stack,$callback);
			return;
		}

		/**
		 * Register a group of routes, with the router.
		 *
		 * @param	String	$route		The group route.
		 * @param 	Array	$options	The global route options.
		 * @param	Closure	$callback	The callback method, this will hold all child methods.
		 * @return	Void
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
		public static function _getRoute($method,$url)
		{
			if(isset(self::$routes[$method][$url]))
			{

				return(['route' => self::$routes[$method][$url], 'params' => []]);
			}
			
			$route	= self::_getUrlRouteMatch($url,$method);

			if($route !== false)
			{
				unset($route['match'],$route['search'],$route['replace'],$route['url']);
				return(['route' => self::$routes[$method][$route['route']],'params' => $route['params']]);
			}


			if($route === false)
			{
				return(self::_getNonStaticRoute($url));
			}
			return(false);
		}

		private static function _getNonStaticRoute($url)
		{
			$url	= (String) $url == '' ? '' : substr($url,1);
			$stack	= explode(':',self::DEFAULT_STACK);
			$params	= [];

			if(empty($url))
			{
				return(['route' => ['stack' => $stack]]);
			}

			$url	= (Array) explode('/',(String) $url);
			$size	= (Integer) sizeof((Array) $url);
			if($size === 1)
			{
				$stack[0]	= $url[0];
			}

			if($size === 2)
			{
				$stack[0]	= $url[0];
				$stack[1]	= $url[1];
			}

			if($size > 2)
			{
				$stack[0]	= $url[0];
				$stack[1]	= $url[1];
				unset($url[0],$url[1]);
				$params		= $url;
			}

			return(['route' => ['stack' => $stack],'params' => $params]);
		}

		/**
		 * Regex match routes, to find the current url route
		 *
		 * @param 	String	$url		Current url.
		 * @return	Boolean|Array		Returns false on no match, otherwise array
		 */
		private static function _getUrlRouteMatch($url,$request)
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