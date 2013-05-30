<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christian Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright	Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */
	 
	namespace Tuxxedo;

	use Tuxxedo\Mvc\Url;
	//use Tuxxedo\Mvc\Controller;
	
	
	class Mvc
	{
		private static $options	=[];

		/**
		 * Holds the mvc route options.
		 *
		 * @var		Array		Route options.
		 */
		private static $route	= [];

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

			self::$route	= Url::_getRoute(self::$options['REQUEST_METHOD'],self::$options['REQUEST_URI']);
		}
	}