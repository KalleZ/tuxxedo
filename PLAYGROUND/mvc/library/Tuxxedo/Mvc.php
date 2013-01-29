<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christiana Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright	Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */

	 //namespace Tuxxedo;
	
	/**
	 * Mvc container, contains the mvc app
	 */
	class Mvc
	{
		/*
		 * Router object.
		 *
		 *@var		object
		 */
		 private static $router;
		 
		 /*
		 * View object.
		 *
		 *@var		object
		 */
		 private static $view;
		 
		 /*
		 * Controller object.
		 *
		 *@var		object
		 */
		 private static $controller;
		
		/**
		 * Registry object
		 *
		 *@var		object
		 */
		 private static $registry;
		
		public static function init()
		{
			self::$registry	= Registry::init();
			self::$router	= new Router();
		}
	}