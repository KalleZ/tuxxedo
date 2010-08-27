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
	 *
	 * =============================================================================
	 */
	
	namespace Tuxxedo\Database\Result;
	
		/**
	 * Base database driver result interface
	 *
	 * This defines the driver structure of which functions a driver 
	 * must implement in the result object and how they should be called.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	interface Tuxxedo_Database_Driver_Result
	{
		/**
		 * Frees the result from memory, and makes it unusable
		 *
		 * @return	boolean			Returns true if the result was freed, otherwise false
		 */
		public function free();

		/**
		 * Checks whether the result is freed or not
		 *
		 * @return	boolean			Returns true if the result is freed from memory, otherwise false
		 */
		public function isFreed();

		/**
		 * Get the number of rows in the result
		 *
		 * @return	integer			Returns the number of rows in the result, and 0 on error
		 */
		public function getNumRows();

		/**
		 * Fetch result with both associative and indexed indexes array
		 *
		 * @return	array			Returns an array with the result
		 */
		public function fetchArray();

		/**
		 * Fetches the result and returns an associative array
		 *
		 * @return	array			Returns an associative array with the result
		 */
		public function fetchAssoc();

		/**
		 * Fetches the result and returns an indexed array
		 *
		 * @return	array			Returns an indexed array with the result
		 */
		public function fetchRow();

		/**
		 * Fetches the result and returns an object, with overloaded 
		 * properties for rows names
		 *
		 * @return	object			Returns an object with the result
		 */
		public function fetchObject();
	}
