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
	 * Datamanagers adapter namespace, this contains all the different 
	 * datamanager handler implementations to comply with the standard 
	 * adapter interface, and with the plugins for hooks.
	 *
	 * @author		Kalle Sommer Nielsen	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	namespace Tuxxedo\Datamanager\Adapter;


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Datamanager\Adapter;
	use Tuxxedo\Datamanager\Hooks;
	use Tuxxedo\Exception;
	use Tuxxedo\Registry;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Datamanager for permissions
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 * @since		1.1.0
	 */
	class Permission extends Adapter implements Hooks\Cache
	{
		/**
		 * Datamanager name
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const DM_NAME			= 'permission';

		/**
		 * Identifier name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const ID_NAME			= 'name';

		/**
		 * Table name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const TABLE_NAME		= 'permissions';


		/**
		 * Fields for validation of permissions
		 *
		 * @var		array
		 */
		protected $fields		= [
							'name'		=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_IDENTIFIER
										], 
							'bits'		=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_NUMERIC
										]
							];


		/**
		 * Constructor, fetches a new permission based on its name if set
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The permission name
		 * @param	integer				Additional options to apply on the datamanager
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws an exception if the permission name is set and it failed to load for some reason
		 * @throws	\Tuxxedo\Exception\SQL		Throws a SQL exception if a database call fails
		 */
		public function __construct(Registry $registry, $identifier = NULL, $options = parent::OPT_DEFAULT, Adapter $parent = NULL)
		{
			if($identifier !== NULL)
			{
				$permission = $registry->db->equery('
									SELECT 
										* 
									FROM 
										`' . \TUXXEDO_PREFIX . 'permissions` 
									WHERE 
										`name` = \'%s\'', $identifier);

				if(!$permission || !$permission->getNumRows())
				{
					throw new Exception('Invalid permission name passed to datamanager');
				}

				$this->data 		= $permission->fetchAssoc();
				$this->data['bits']	= (integer) $this->data['bits'];
				$this->identifier 	= $identifier;

				$permission->free();
			}

			parent::init($registry, $options, $parent);
		}

		/**
		 * Save the permission in the datastore, this method is called from 
		 * the parent class in cases when the save method was success
		 *
		 * @param	array				A virtually populated array from the datamanager abstraction
		 * @return	boolean				Returns true if the datastore was updated with success, otherwise false
		 */
		public function rebuild()
		{
			if($this->context == parent::CONTEXT_DELETE && !isset($this->registry->datastore->permissions[$this->data['name']]))
			{
				return(true);
			}

			$permissions = $this->registry->datastore->permissions;

			unset($permissions[$this->information['name']]);

			if($this->context == parent::CONTEXT_SAVE)
			{
				$permissions[$this->information['name']] = (integer) $this->information['bits'];
			}

			return($this->registry->datastore->rebuild('permissions', $permissions));
		}
	}
?>