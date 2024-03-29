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
	use Tuxxedo\Exception;
	use Tuxxedo\Datamanager\Adapter;
	use Tuxxedo\Datamanager\Hooks;
	use Tuxxedo\Registry;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Datamanager for options
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 * @since		1.1.0
	 */
	class Option extends Adapter implements Hooks\Cache, Hooks\Resetable
	{
		/**
		 * Datamanager name
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const DM_NAME			= 'option';

		/**
		 * Identifier name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const ID_NAME			= 'option';

		/**
		 * Table name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const TABLE_NAME		= 'options';


		/**
		 * Fields for validation of options
		 *
		 * @var		array
		 */
		protected $fields		= [
							'option'	=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_IDENTIFIER
										], 
							'value'		=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_STRING_EMPTY
										], 
							'defaultvalue'	=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_CALLBACK, 
										'callback'	=> [__CLASS__, 'isValidDefaultValue']
										], 
							'type'		=> [
										'type'		=> parent::FIELD_REQUIRED, 
										'validation'	=> parent::VALIDATE_CALLBACK, 
										'callback'	=> [__CLASS__, 'isValidType'], 
										'default'	=> 's'
										],
							'category'	=> [
										'type'		=> parent::FIELD_OPTIONAL, 
										'validation'	=> parent::VALIDATE_CALLBACK, 
										'callback'	=> [__CLASS__, 'isValidCategory']
										], 
							'newdefault' 	=> [
										'type'		=> parent::FIELD_VIRTUAL
										]
							];


		/**
		 * Constructor, fetches a new option based on its name if set
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The option name
		 * @param	integer				Additional options to apply on the datamanager
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws an exception if the option name is set and it failed to load for some reason
		 * @throws	\Tuxxedo\Exception\SQL		Throws a SQL exception if a database call fails
		 */
		public function __construct(Registry $registry, $identifier = NULL, $options = parent::OPT_DEFAULT, Adapter $parent = NULL)
		{
			if($identifier !== NULL)
			{
				$option = $registry->db->equery('
									SELECT 
										* 
									FROM 
										`' . \TUXXEDO_PREFIX . 'options` 
									WHERE 
										`option` = \'%s\'', $identifier);

				if(!$option || !$option->getNumRows())
				{
					throw new Exception\Basic('Invalid option name passed to datamanager');
				}

				$this->data 		= $option->fetchAssoc();
				$this->identifier 	= $identifier;

				$option->free();
			}

			parent::init($registry, $options, $parent);
		}

		/**
		 * Checks whether the default value is valid
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The value to check
		 * @return	boolean				Returns true if the default value is valid
		 */
		public static function isValidDefaultValue(Adapter $dm, Registry $registry, $defaultvalue = NULL)
		{
			if(!$dm['type'])
			{
				return(false);
			}
			elseif($dm->identifier === NULL || $dm['newdefault'])
			{
				$dm['defaultvalue'] = $defaultvalue = $dm['value'];
			}

			switch(\strtolower($dm['type']{0}))
			{
				case('b'):
				case('s'):
				{
					return(true);
				}
				case('i'):
				{
					return($defaultvalue !== NULL && \is_numeric($defaultvalue));
				}
			}

			return(false);
		}

		/**
		 * Checks whether the option values fits the type definition
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The type to check
		 * @return	boolean				Returns true if the type is valid, otherwise false
		 */
		public static function isValidType(Adapter $dm, Registry $registry, $type = NULL)
		{
			static $types;

			if(!$types)
			{
				$types = ['b', 'i', 's'];
			}

			if(($retval = \in_array(\strtolower($dm['type']{0}), $types)) !== false)
			{
				$dm['type'] = $dm['type']{0};
			}

			return($retval);
		}

		/**
		 * Checks whether the category is valid
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The category to check
		 * @return	boolean				Returns true if the category is valid
		 */
		public static function isValidCategory(Adapter $dm, Registry $registry, $category)
		{
			static $cache;

			if($cache === NULL)
			{
				$query = $registry->db->query('
								SELECT 
									`name`
								FROM
									`' . \TUXXEDO_PREFIX . 'optioncategories`');

				if($query && $query->getNumRows())
				{
					foreach($query as $row)
					{
						$cache[] = $row['name'];
					}
				}
			}

			return(\in_array($category, $cache));
		}

		/**
		 * Save the option in the datastore, this method is called from 
		 * the parent class in cases when the save method was success
		 *
		 * @return	boolean				Returns true if the datastore was updated with success, otherwise false
		 */
		public function rebuild()
		{
			if($this->context == parent::CONTEXT_DELETE && !isset($this->registry->datastore->options[$this->information['option']]))
			{
				return(true);
			}

			$options = $this->registry->datastore->options;

			unset($options[$this->information['option']]);

			if($this->context == parent::CONTEXT_SAVE)
			{
				$options[$this->information['option']] = [
								'category'	=> $this->data['category'], 
								'value'		=> $this->data['value']
								];
			}

			return($this->registry->datastore->rebuild('options', $options));
		}

		/**
		 * Resets the data to its default values while keeping the 
		 * identifier intact
		 *
		 * @return	boolean				Returns true on successful reset, otherwise false
		 */
		public function reset()
		{
			if($this->options & parent::OPT_LOAD_ONLY || $this->identifier === NULL)
			{
				return(false);
			}

			$this->information['value'] = $this->information['defaultvalue'];

			return($this->save());
		}
	}
?>