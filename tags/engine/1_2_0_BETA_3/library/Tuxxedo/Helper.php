<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen 	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 * @subpackage		Library
	 *
	 * =============================================================================
	 */


	/**
	 * Core Tuxxedo library namespace. This namespace contains all the main 
	 * foundation components of Tuxxedo Engine, plus additional utilities 
	 * thats provided by default. Some of these default components have 
	 * sub namespaces if they provide child objects.
	 *
	 * @author		Kalle Sommer Nielsen	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	namespace Tuxxedo;


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Registry;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;

	
	/**
	 * Helper loading interface
	 *
	 * @author		Kalle Sommer Nielsen 	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 * @since		1.1.0
	 */
	abstract class Helper
	{
		/**
		 * Contains the loaded helpers
		 *
		 * @var		array
		 */
		protected static $loaded_helpers	= [];


		/**
		 * Constructs a new helper
		 *
	 	 * @param	\Tuxxedo\Registry		The Tuxxedo object reference
		 */
		abstract public function __construct(Registry $registry);

		/**
		 * Factory method for loading a new helper
		 *
		 * When loading multiple helpers and set them to register in the registry, then 
		 * the last registered one will override the old one in the registry
		 *
		 * @param	string				The helper handle to instanciate
		 * @param	boolean				Whether to register this helper in the registry
		 * @return	object				Returns a helper handle object reference
		 *
		 * @changelog	1.2.0				This method no longer throws a basic exception on invalid handles as its handled by the autoloader
		 */ 
		final public static function factory($helper, $register = false)
		{
			static $registry;

			if(!$registry)
			{
				$registry = Registry::init();
			}

			$class = (\strpos($helper, '\\') === false ? '\Tuxxedo\Helper\\' : '') . \ucfirst(\strtolower($helper));

			if(isset(self::$loaded_helpers[$helper]))
			{
				$ref = new $class($registry);

				if($register)
				{
					$registry->register($helper, $ref);
				}

				return($ref);
			}

			self::$loaded_helpers[$helper] 	= true;
			$ref 				= new $class($registry);

			if($register)
			{
				$registry->register($helper, $ref);
			}

			return($ref);
		}
	}
?>