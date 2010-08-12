<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen 	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @package		Engine
	 *
	 * =============================================================================
	 */

	defined('TUXXEDO') or exit;


	/**
	 * Datamanager for styles
	 *
	 * @author	Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version	1.0
	 * @package	Engine
	 */
	class Tuxxedo_Datamanager_Style extends Tuxxedo_Datamanager implements Tuxxedo_Datamanager_API_Cache
	{
		/**
		 * Fields for validation of styles
		 *
		 * @var		array
		 */
		protected $fields		= Array(
							'id'		=> Array(
											'type'		=> self::FIELD_PROTECTED
											), 
							'name'		=> Array(
											'type'		=> self::FIELD_REQUIRED, 
											'validation'	=> self::VALIDATE_STRING
											), 
							'developer'	=> Array(
											'type'		=> self::FIELD_REQUIRED, 
											'validation'	=> self::VALIDATE_STRING
											), 
							'styledir' 	=> Array(
											'type'		=> self::FIELD_REQUIRED, 
											'validation'	=> self::VALIDATE_STRING
											), 
							'default'	=> Array(
											'type'		=> self::FIELD_OPTIONAL, 
											'validation'	=> self::VALIDATE_BOOLEAN, 
											'default'	=> 0
											)
							);


		/**
		 * Constructor, fetches a new style based on its id if set
		 *
		 * @param	Tuxxedo			The Tuxxedo object reference
		 * @param	integer			The style id
		 *
		 * @throws	Tuxxedo_Exception	Throws an exception if the style id is set and it failed to load for some reason
		 * @throws	Tuxxedo_Basic_Exception	Throws a basic exception if a database call fails
		 */
		public function __construct(Tuxxedo $tuxxedo, $identifier = NULL)
		{
			$this->tuxxedo 		= $tuxxedo;

			$this->dmname		= 'style';
			$this->tablename	= TUXXEDO_PREFIX . 'styles';
			$this->idname		= 'id';
			$this->information	= &$this->userdata;

			if($identifier !== NULL)
			{
				$styles = $tuxxedo->db->query('
								SELECT 
									* 
								FROM 
									`' . TUXXEDO_PREFIX . 'styles` 
								WHERE 
									`id` = %d
								LIMIT 1', $identifier);

				if(!$styles || !$styles->getNumRows())
				{
					throw new Tuxxedo_Exception('Invalid style id passed to datamanager');
				}

				$this->data 		= $styles->fetchAssoc();
				$this->identifier 	= $identifier;
			}
		}

		/**
		 * Save the style in the datastore, this method is called from 
		 * the parent class in cases when the save method was success
		 *
		 * @param	Tuxxedo			The Tuxxedo object reference
		 * @param	array			A virtually populated array from the datamanager abstraction
		 * @return	boolean			Returns true if the datastore was updated with success, otherwise false
		 */
		public function rebuild(Tuxxedo $tuxxedo, Array $virtual)
		{
			if(($datastore = $this->tuxxedo->cache->styleinfo) === false)
			{
				$datastore = Array();
			}
			
			$datastore[(integer) $this->identifier] = $virtual;

			return($this->tuxxedo->cache->rebuild('styleinfo', $datastore));
		}
	}
?>