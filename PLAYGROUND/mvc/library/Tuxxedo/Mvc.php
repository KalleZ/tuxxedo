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
	 
	namespace Tuxxedo;

	use Tuxxedo\Mvc\Router\Url;
	//use Tuxxedo\Mvc\Controller;
	
	
	class Mvc
	{
		/**
		 * Stak option controller.
		 * 
		 * @var 	String		Controller stack options
		 */
		const		STACK_CONTROLLER	= 0;

		/**
		 * Stak option controller.
		 * 
		 * @var 	String		Controller stack options
		 */
		const		STACK_ACTION		= 1;

		/**
		 * Default options for mv bundle
		 * 
		 * @var 	Array		Default mvc bundle options.
		 */
		private static $options			= [];

		/**
		 * Holds the mvc route options.
		 *
		 * @var		Array		Route options.
		 */ 
		private static $route			= [];

		/**
	 	 * Initialize the mvc kernel-bundle
	 	 *
	 	 * @return	Void
	 	 */
		public static function initialize(Array $options = NULL)
		{
			self::$options				= ($options ? $options : []);
			self::$options['REQUEST_METHOD']	= strtolower((isset(self::$options['REQUEST_METHOD']) ? self::$options['REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD']));
			self::$options['REQUEST_URI']		= (isset(self::$options['REQUEST_URI']) ? self::$options['REQUEST_URI'] : $_SERVER['REQUEST_URI']);

			self::$route				= Url::_getRoute(self::$options['REQUEST_METHOD'],self::$options['REQUEST_URI']);
			self::_getController();
			self::_getAction();

		}

		/**
		 * Returns thee controller name,
		 * but it will also set the controller in the route array.
		 * 
		 * @return	String		Controller name.
		 */
		private static function _getController()
		{
			return(self::_getStackOptions(self::STACK_CONTROLLER));
		}

		/**
		 * Returns thee action name,
		 * but it will also set the action in the route array.
		 * 
		 * @return	String		Action name.
		 */
		private static function _getAction()
		{
			return(self::_getStackOptions(self::STACK_ACTION));
		}

		/**
		 * Sets and gets the route controller or action.
		 * 
		 * @return	String		Controller name or action name.
		 */
		private static function _getStackOptions($option)
		{
			if(strpos(self::$route['route']['stack'][$option],'{',0) !== true)
			{
				$opt	= \str_replace(Array('{','}'),'',self::$route['route']['stack'][$option]);
			}
			else
			{
				$opt	= self::$route['route']['stack'][$option];
			}

			if((string) $opt === (string)(int) $opt)
			{
				--$opt;
				if(isset(self::$route['params'][$opt]))
				{
					$opt	= self::$route['params'][$opt];
				}
			}

			self::$route['route']['stack'][$option]	= $opt;
			return(self::$route['route']['stack'][$option]);
		}
	}