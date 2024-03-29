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
	 * Datamanager namespace, this contains all base adapter class that 
	 * datamanagers must extend in order to become loadable. The root 
	 * namespace also hosts interfaces that datamanagers can implement 
	 * to extend the magic within.
	 *
	 * @author		Kalle Sommer Nielsen	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	namespace Tuxxedo\Datamanager;


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Datamanager\Hooks;
	use Tuxxedo\Design;
	use Tuxxedo\Exception;
	use Tuxxedo\Registry;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Abstract datamanager class
	 *
	 * Every datamanager class must extend this class in order to be loadable and to 
	 * comply with the datamanager API. This also contains the factory method used 
	 * to instanciate a new datamanager instance.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	abstract class Adapter extends Design\InfoAccess implements \Iterator
	{
		/**
		 * Indicates that a field is required
		 *
		 * @var		integer
		 */
		const FIELD_REQUIRED			= 1;

		/**
		 * Indicates that a field is optional
		 *
		 * @var		integer
		 */
		const FIELD_OPTIONAL			= 2;

		/**
		 * Indicates that a field is protected
		 *
		 * @var		integer
		 */
		const FIELD_PROTECTED			= 3;

		/**
		 * Indicates that a field is virtual
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const FIELD_VIRTUAL			= 4;

		/**
		 * Context constant, default context
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const CONTEXT_NONE			= 1;

		/**
		 * Context constant, save() context
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const CONTEXT_SAVE			= 2;

		/**
		 * Context constant, delete() context
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const CONTEXT_DELETE			= 3;

		/**
		 * Context constant, void context
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const CONTEXT_VOID			= 4;

		/**
		 * Validation constant, no validation
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const VALIDATE_NONE			= 1;

		/**
		 * Validation constant, numeric value
		 *
		 * @var		integer
		 */
		const VALIDATE_NUMERIC			= 2;

		/**
		 * Validation constant, string value
		 *
		 * @var		integer
		 */
		const VALIDATE_STRING			= 3;

		/**
		 * Validation constant, email value
		 *
		 * @var		integer
		 */
		const VALIDATE_EMAIL			= 4;

		/**
		 * Validation constant, boolean value
		 *
		 * @var		integer
		 */
		const VALIDATE_BOOLEAN			= 5;

		/**
		 * Validation constant, callback
		 *
		 * @var		integer
		 */
		const VALIDATE_CALLBACK			= 6;

		/**
	 	 * Validation option constant, allow empty fields
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const VALIDATE_STRING_EMPTY		= 7;

		/**
		 * Validation option constant, identifier
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const VALIDATE_IDENTIFIER		= 8;

		/**
		 * Factory option constant - internationalization (default enabled)
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const OPT_INTL				= 1;

		/**
		 * Factory option constant - insert as new record
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const OPT_LOAD_ONLY			= 2;

		/**
		 * Factory option constant - internationalization, load if available
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const OPT_INTL_AUTO			= 4;

		/**
		 * Factory option constant - default options
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		const OPT_DEFAULT			= self::OPT_INTL_AUTO;


		/**
		 * Private instance to the Tuxxedo registry
		 *
		 * @var		\Tuxxedo\Registry
		 */
		protected $registry;

		/**
		 * Identifier, if any
		 *
		 * @var		array
		 */
		protected $identifier;

		/**
		 * Whether to re-identify the data when saving
		 *
		 * @var		boolean
		 * @since	1.1.0
		 */
		protected $reidentify 			= false;
 
		/**
		 * Iterator position
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		protected $iterator_position		= 0;

		/**
		 * Whether this datamanager are called from another datamanager
		 *
		 * @var		\Tuxxedo\Datamanager\Adapter
		 * @since	1.1.0
		 */
		protected $parent			= false;

		/**
		 * Context for hooks, and adapters
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		protected $context			= self::CONTEXT_NONE;

		/**
		 * The original data if instanciated by an identifier
		 *
		 * @var		array
		 */
		protected $data				= [];

		/**
		 * The original data of each modified field, should it differ from $data
		 *
		 * @var		array
		 * @since	1.2.0
		 */
		protected $original_data		= [];

		/**
		 * Cache data if the identifier is gonna be validated
		 *
		 * @var		array
		 * @since	1.1.0
		 */
		protected $identifier_data		= [];

		/**
		 * List of shutdown handlers to execute
		 *
		 * @var		array
		 * @since	1.1.0
		 */
		protected $shutdown_handlers		= [];

		/**
		 * List of fields that had one or more errors and therefore 
		 * could not be saved
		 *
		 * @var		array
		 */
		protected $invalid_fields		= [];

		/**
		 * List of loaded datamanagers used for caching in the 
		 * special required cases where more than one driver 
		 * have to be loaded
		 *
		 * @var		array
		 */
		protected static $loaded_datamanagers 	= [];


		/**
		 * Constructor for the current datamanager, this 
		 * can be used to either create a datamanager based 
		 * on a certain record determined by the passed identifier 
		 * or as a clean datamanager to insert a new record
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	mixed				The unique identifier to send to the datamanager
		 * @param	integer				The datamanager options
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 *
		 * @throws	\Tuxxedo\Exception		Throws an exception if the unique identifier sent to the datamanager was invalid
		 *
		 * @changelog	1.1.0				Added the $options parameter
		 * @changelog	1.1.0				Added the $parent parameter
		 */
		abstract public function __construct(Registry $registry, $identifier = NULL, $options = self::OPT_DEFAULT, Adapter $parent = NULL);

		/**
		 * Destructor for the current datamanager, this is 
		 * reserved for shutdown handlers in parent datamanagers.
		 *
		 * @since	1.1.0
		 */
		final public function __destruct()
		{
			if($this->shutdown_handlers)
			{
				foreach($this->shutdown_handlers as $callback)
				{
					call_user_func_array($callback['handler'], $callback['arguments']);
				}
			}
		}

		/**
		 * Clonable hook
		 *
		 * Magic __clone() method, this method will internally reset the state of a 
		 * datamanager.
		 *
		 * @return	void				No value is returned
		 *
		 * @since	1.2.0
		 */
		public function __clone()
		{
			$this->identfier 	= NULL;
			$this->reidentify	= false;
			$this->context		= self::CONTEXT_NONE;
			$this->options		|= self::OPT_LOAD_ONLY;

			if(isset($this->data[static::ID_NAME]))
			{
				unset($this->data[static::ID_NAME]);
			}
		}

		/**
		 * Overloads the info access 'get' method so that default data is allocated 
		 * when using the ArrayAccess accessor
		 *
		 * @param	scalar				The information row name to get
		 * @return	void				No value is returned
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws a basic exception on invalid properties
		 *
		 * @since	1.1.0
		 */
		public function offsetGet($offset)
		{
			if(!isset($this->fields[$offset]))
			{
				throw new Exception\Basic('Tried to access invalid datamanager property');
			}

			if(!isset($this->information[$offset]))
			{
				$this->information[$offset] = (isset($this->fields[$offset]) && isset($this->fields[$offset]['default']) ? $this->fields[$offset]['default'] : '');
			}


			return($this->information[$offset]);
		}

		/**
		 * Overloads the info access 'set' method so that its prohibited to 
		 * set elements that doesn't exists
		 *
		 * @param	scalar			The information row name to set
		 * @param	mixed			The information row value to set
		 * @return	void			No value is returned
		 *
		 * @since	1.1.0
		 */
		public function offsetSet($offset, $value)
		{
			if(!isset($this->fields[$offset]))
			{
				throw new Exception('Cannot define value for non existing field \'%s\'', $offset);
			}

			if(isset($this->information[$offset]) && $value != $this->information[$offset])
			{
				$this->original_data[$offset] = $value;
			}

			$this->information[$offset] = $value;
		}

		/**
		 * Datamanager initializer, this method initializes the default logic 
		 * used across all datamanager adapters
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				Additional options to apply on the datamanager
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 * @return	void				No value is returned
		 *
		 * @since	1.1.0
		 *
		 * @changelog	1.1.0				Added the $options parameter
		 * @changelog	1.1.0				Added the $parent parameter
		 */
		final protected function init(Registry $registry, $options = self::OPT_DEFAULT, Adapter $parent = NULL)
		{
			$this->registry		= $registry;
			$this->options		= $options;
			$this->parent		= $parent;
			$this->information 	= &$this->data;

			if($options & self::OPT_LOAD_ONLY)
			{
				$this->identifier = $this->fields[static::ID_NAME]['value'] = NULL;
			}

			if(isset($this->fields[static::ID_NAME]['validation']) && $this->fields[static::ID_NAME]['validation'] == self::VALIDATE_IDENTIFIER)
			{
				$query = $registry->db->query('
								SELECT 
									`%s` 
								FROM 
									`%s`', static::ID_NAME, \TUXXEDO_PREFIX . static::TABLE_NAME);

				if($query && $query->getNumRows())
				{
					foreach($query as $row)
					{
						$this->identifier_cache[] = $row[static::ID_NAME];
					}
				}
			}
		}

		/**
		 * Constructs a new datamanger instance
		 *
		 * @param	string				Datamanger name
		 * @param	mixed				An identifier to send to the datamanager to load default data upon instanciating it
		 * @param	integer				Additional options to apply on the datamanager
		 * @return	\Tuxxedo\Datamanager\Adapter	Returns a new datamanager instance
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws a basic exception if loading of a datamanger should fail for some reason
		 * @throws	\Tuxxedo\Exception\SQL		Throws a SQL exception if a database call fails when loading the datamanager
		 *
		 * @changelog	1.1.0				Added the $options parameter
		 * @changelog	1.1.0				Added the $parent parameter
		 */
		final public static function factory($datamanager, $identifier = NULL, $options = self::OPT_DEFAULT, Adapter $parent = NULL)
		{
			$registry = Registry::init();

			if($options & (self::OPT_INTL | self::OPT_INTL_AUTO))
			{
				if($options & self::OPT_INTL && !$registry->intl)
				{
					throw new Exception\Basic('Internationalization is not instanciated for datamanager phrases');
				}

				if($registry->intl && !$registry->intl->cache(['datamanagers']))
				{
					throw new Exception\Basic('Unable to cache datamanager phrases');
				}
			}

			$class	= (\strpos($datamanager, '\\') === false ? '\Tuxxedo\Datamanager\Adapter\\' : '') . \ucfirst(\strtolower($datamanager));
			$dm 	= new $class($registry, $identifier, $options, $parent);

			if(\in_array($datamanager, self::$loaded_datamanagers))
			{
				return($dm);
			}

			if(!\is_subclass_of($class, __CLASS__))
			{
				throw new Exception\Basic('Corrupt datamanager adapter, adapter class does not follow the driver specification');
			}

			self::$loaded_datamanagers[] = $datamanager;

			return($dm);
		}

		/**
		 * Gets a list over invalid fields, this is only populated 
		 * if an attempt to saving a datamanager have failed
		 *
		 * @return	array				Returns a list of those fields that failed validation
		 */
		public function getInvalidFields()
		{
			return($this->invalid_fields);
		}

		/**
		 * Gets a list of virtual fields from the datamanager adapter 
		 *
		 * @param	boolean				Whether or not to check for populated data (defaults to true)
		 * @return	array				Returns an array with field => value pairs, and empty array on none (if populated is set to off, all values are boolean true)
		 *
		 * @since	1.1.0
		 */
		public function getVirtualFields($populated = true)
		{
			if(!$this->fields)
			{
				return([]);
			}

			$fields = [];

			foreach($this->fields as $name => $props)
			{
				if(isset($props['type']) && $props['type'] == self::FIELD_VIRTUAL)
				{
					if($populated && !isset($this->data[$name]))
					{
						continue;
					}

					$fields[$name] = ($populated ? $this->data[$name] : true);
				}
			}

			return($fields);
		}

		/**
		 * Gets a field
		 *
		 * @param	string				The field to get, if this value is NULL then all the backend data will be returned
		 * @return	mixed				Returns the field value, and NULL if the field is non existant (set)
		 */
		public function get($field = NULL)
		{
			if($field === NULL)
			{
				return($this->data);
			}
			elseif(isset($this->data[$field]))
			{
				return($this->data[$field]);
			}
		}

		/**
		 * Sets a shutdown handler
		 *
		 * @param	callback			A callback to execute
		 * @param	array				Any additonal arguments the callback needs to execute properly
		 * @return	void				No value is returned
		 *
		 * @since	1.1.0
		 */
		public function setShutdownHandler($handler, Array $arguments)
		{
			if(!is_callable($handler))
			{
				return;
			}

			$this->shutdown_handlers[] = [
							'handler'	=> $handler, 
							'arguments'	=> $arguments
							];
		}

		/**
		 * Validation method, validates the supplied user data 
		 *
		 * @return	boolean				Returns true if the data is valid, otherwise false
		 *
		 * @changelog	1.1.0				This method was rewritten from scratch and now handles more cases than before
		 * @changelog	1.1.0				This method can now validate identifiers
		 */
		public function validate()
		{
			$this->invalid_fields = [];

			foreach($this->fields as $field => $props)
			{
				if($props['type'] == self::FIELD_PROTECTED && !isset($props['validation']) || $props['type'] == self::FIELD_OPTIONAL && !isset($props['default']) && !isset($this->data[$field]))
				{
					continue;
				}

				if(isset($props['default']) && !isset($this->data[$field]))
				{
					$this->data[$field] = $props['default'];
				}

				if(!isset($props['validation']) || $props['type'] == self::FIELD_VIRTUAL)
				{
					$props['validation'] = 0;
				}

				if($props['validation'] && !\in_array($props['validation'], [self::VALIDATE_STRING, self::VALIDATE_STRING_EMPTY, self::VALIDATE_BOOLEAN, self::VALIDATE_CALLBACK]) && $props['type'] != self::FIELD_PROTECTED && !isset($this->data[$field]))
				{
					$this->invalid_fields[] = $field;

					continue;
				}

				switch($props['validation'])
				{
					case(self::VALIDATE_NUMERIC):
					{
						if((!isset($this->data[$field]) && $props['type'] == self::FIELD_REQUIRED) || !\is_numeric($this->data[$field]))
						{
							$this->invalid_fields[] = $field;

							continue;
						}
					}
					break;
					case(self::VALIDATE_STRING_EMPTY):
					{
						$this->data[$field] = (isset($this->data[$field]) ? (string) $this->data[$field] : '');

						continue;
					}
					case(self::VALIDATE_STRING):
					{
						if((!isset($this->data[$field]) && $props['type'] == self::FIELD_REQUIRED) || empty($this->data[$field]))
						{
							$this->invalid_fields[] = $field;

							continue;
						}
					}
					break;
					case(self::VALIDATE_EMAIL):
					{
						if((!isset($this->data[$field]) && $props['type'] == self::FIELD_REQUIRED) || !\filter_var($this->data[$field], \FILTER_VALIDATE_EMAIL))
						{
							$this->invalid_fields[] = $field;

							continue;
						}
					}
					break;
					case(self::VALIDATE_BOOLEAN):
					{
						$this->data[$field] = (isset($this->data[$field]) ? (boolean) $this->data[$field] : (isset($props['default']) ? (boolean) $props['default'] : false));

						continue;
					}
					case(self::VALIDATE_CALLBACK):
					{
						$value = (isset($this->data[$field]) ? $this->data[$field] : NULL);

						if(!isset($props['callback']) || !\is_callable($props['callback']) || !\call_user_func($props['callback'], $this, $this->registry, $value))
						{
							$this->invalid_fields[] = $field;

							continue;
						}
					}
					break;
					case(self::VALIDATE_IDENTIFIER):
					{
						if(!isset($this->data[$field]) || empty($this->data[$field]))
						{
							$this->invalid_fields[] = $field;

							continue;
						}

						if($this->identifier)
						{
							$exists = \in_array($field, $this->identifier_data);

							if($this->identifier != $this->data[$field])
							{
								if($exists)
								{
									$this->invalid_fields[] = $field;

									continue;
								}
							}
							elseif($exists)
							{
								$this->invalid_fields[] = $field;

								continue;
							}
						}
						else
						{
							$this->reidentify = true;
						}
					}
					break;
					default:
					{
						if($props['type'] != self::FIELD_VIRTUAL)
						{
							$this->invalid_fields[] = $field;
						}
					}
					break;
				}
			}

			if($this->invalid_fields)
			{
				return(false);
			}

			return(true);
		}

		/**
		 * Save method, attempts to validate and save the data 
		 * into the database
		 *
		 * @param	boolean				Whether to execute hooks or not. This parameter is mainly designed for datamanager internals
		 * @return	boolean				Returns true if the data is saved with success, otherwise boolean false
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws a basic exception if the query should fail
		 * @throws	\Tuxxedo\Exception\Multi	Throws a multi exception if validation fails
		 *
		 * @changelog	1.1.0				Added the $execute_hooks parameter
		 * @changelog	1.1.0				This method now generates queries for more cases based on the state of the identifier
		 */
		public function save($execute_hooks = true)
		{
			if($this->context == self::CONTEXT_VOID)
			{
				return(false);
			}

			$this->context = self::CONTEXT_SAVE;

			if(!$this->validate())
			{
				$intl		= $this->registry->intl && ($this->options & (self::OPT_INTL | self::OPT_INTL_AUTO));
				$multidata 	= [];

				foreach($this->invalid_fields as $field)
				{
					$multidata[$field] = ($intl && ($phrase = $this->registry->intl->find('dm_' . static::DM_NAME . '_' . $field, 'datamanagers')) !== false ? $phrase : $field);
				}

				$this->context = self::CONTEXT_NONE;

				throw new Exception\Multi($multidata, ($intl ? $this->registry->intl->find('validation_failed', 'datamanagers') : ''));
			}

			$values		= '';
			$virtual	= ($this->identifier !== NULL ? \array_merge([static::ID_NAME => $this->identifier], $this->data) : $this->data);
			$virtual_fields	= $this->getVirtualFields();
			$n 		= \sizeof($virtual);

			if($virtual_fields)
			{
				$n -= \sizeof($virtual_fields);
			}

			$new_identifier = isset($this->data[static::ID_NAME]) && !$this->reidentify;
			$sql		= ($new_identifier ? 'UPDATE `' . \TUXXEDO_PREFIX . static::TABLE_NAME . '` SET ' : (($this->options & self::OPT_LOAD_ONLY) ? 'INSERT INTO' : 'REPLACE INTO') . ' `' . \TUXXEDO_PREFIX . static::TABLE_NAME . '` (');

			foreach($virtual as $field => $data)
			{
				if(($field == static::ID_NAME && ($this->options & self::OPT_LOAD_ONLY)) || isset($this->fields[$field]['type']) && $this->fields[$field]['type'] == self::FIELD_VIRTUAL)
				{
					if($field == static::ID_NAME && ($this->options & self::OPT_LOAD_ONLY))
					{
						--$n;
					}

					continue;
				}

				if($new_identifier)
				{
					$sql .= '`' . $field . '` = ' . (is_null($data) ? ($this->fields[$field]['validation'] == self::VALIDATE_NUMERIC || $this->fields[$field]['validation'] == self::VALIDATE_BOOLEAN ? '0' : (isset($this->fields[$field]['notnull']) && $this->fields[$field]['notnull'] ? '\'\'' : 'NULL')) : '\'' . $this->registry->db->escape($data) . '\'') . (--$n ? ', ' : '');
				}
				else
				{
					$sql 	.= '`' . $field . '`' . (--$n ? ', ' : '');
					$values .= (is_null($data) ? ($this->fields[$field]['validation'] == self::VALIDATE_NUMERIC || $this->fields[$field]['validation'] == self::VALIDATE_BOOLEAN ? '0' : (isset($this->fields[$field]['notnull']) && $this->fields[$field]['notnull'] ? '\'\'' : 'NULL')) : '\'' . $this->registry->db->escape($data) . '\'') . ($n ? ', ' : '');
				}
			}

			if($new_identifier)
			{
				$sql .= ' WHERE `' . static::ID_NAME . '` = \'' . $this->registry->db->escape($this->identifier) . '\'';
			}
			else
			{
				$sql .= ') VALUES (' . $values . ')';
			}

			if(!$this->registry->db->query($sql))
			{
				$this->context = self::CONTEXT_NONE;

				return(false);
			}

			if(($new_id = $this->registry->db->getInsertId()))
			{
				$this->data[static::ID_NAME] = $new_id;
			}

			if($execute_hooks)
			{
				if(!$this->parent)
				{
					$result 	= $this->hooks($this);
					$this->context 	= self::CONTEXT_NONE;

					return($result);
				}

				$this->parent->setShutdownHandler([$this, 'hooks'], [$this]);
			}

			$this->context = self::CONTEXT_NONE;

			return(true);
		}

		/**
	 	 * Deletes the data, within the database if an identifier was specified, else 
		 * the current set data is removed
		 *
		 * @return	boolean				Returns true if the deletion was a success otherwise boolean false
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws a basic exception if the query should fail
		 */
		public function delete()
		{
			if($this->context == self::CONTEXT_VOID)
			{
				return(false);
			}

			$this->invalid_fields = [];

			if($this->identifier === NULL && !($this->options & self::OPT_LOAD_ONLY))
			{
				return(true);
			}

			$this->context = self::CONTEXT_DELETE;

			if(($this instanceof Hooks\Cache && !$this->rebuild()))
			{
				$this->context = self::CONTEXT_NONE;

				return(false);
			}

			$this->context = self::CONTEXT_VOID;

			return($this->registry->db->equery('
								DELETE FROM 
									`' . \TUXXEDO_PREFIX . static::TABLE_NAME . '`
								WHERE 
									`' . static::ID_NAME . '` = \'%s\'', ($this->options & self::OPT_LOAD_ONLY ? $this->data[static::ID_NAME] : $this->identifier)));
		}

		/**
		 * Gets the parent datamanager pointer
		 *
		 * @return	\Tuxxedo\Datamanager\Adapter	Returns a datamanager pointer to the parent object if any, false on root or error
		 *
		 * @since	1.1.0
		 */
		public function getParent()
		{
			return($this->parent);
		}

		/**
		 * Gets the fields this datamanager provides
		 *
		 * @return	array				Returns an array with the fields
		 *
		 * @since	1.1.0
		 */
		public function getFields()
		{
			return(\array_keys($this->fields));
		}

		/**
		 * Gets default data to allocate the $data property internally
		 *
		 * @return	array				Returns an array with the same structure as the $data property and false on error
		 *
		 * @since	1.2.0
		 */
		public function getDataStruct()
		{
			$data = [];

			foreach($this->fields as $name => $props)
			{
				$data[$name] = (isset($props['default']) ? $props['default'] : '');
			}

			return($data);
		}

		/**
		 * Iterator method - current
		 * 
		 * @return	mixed				Returns the current field
		 *
		 * @since	1.1.0
		 */
		public function current()
		{
			return(\key($this->data));
		}

		/**
		 * Iterator method - rewind
		 *
		 * @return	void				No value is returned
		 *
		 * @since	1.1.0
		 */
		public function rewind()
		{
			\reset($this->data);

			$this->iterator_position = 0;
		}

		/**
		 * Iterator method - key
		 *
		 * @return	integer				Returns the currrent index
		 *
		 * @since	1.1.0
		 */
		public function key()
		{
			return($this->iterator_position);
		}

		/**
		 * Iterator method - next
		 *
		 * @return	void				No value is returned
		 *
		 * @since	1.1.0
		 */
		public function next()
		{
			if(\next($this->data) !== false)
			{
				++$this->iterator_position;
			}
		}

		/**
		 * Iterator method - valid
		 *
		 * @return	boolean				Returns true if its possible to continue iterating, otherwise false is returned
		 *
		 * @since	1.1.0
		 */
		public function valid()
		{
			return(\sizeof($this->data) - 1 != $this->iterator_position);
		}

		/**
		 * Hooks executor
		 *
		 * This method executes hooks on a datamanager instance, this is cannot be 
		 * called publically.
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The datamanager adapter instance to execute hooks on
		 * @return	boolean				Returns true if all fields passed through the hooks flawlessly
		 *
		 * @since	1.2.0
		 */
		protected function hooks(Adapter $self)
		{
			if(($self instanceof Hooks\Cache) && !$self->rebuild())
			{
				return(false);
			}

			$dispatch 	= ($self instanceof Hooks\VirtualDispatcher);
			$virtual	= $this->getVirtualFields();

			if($virtual && ($dispatch || $self instanceof Hooks\Virtual))
			{
				foreach($virtual as $field => $value)
				{
					if($dispatch)
					{
						$method = 'virtual' . $field;

						if(\method_exists($self, $method) && !$self->{$method}($value))
						{
							return(false);
						}
					}
					elseif(!$this->virtual($field, $value))
					{
						return(false);
					}
				}
			}

			return(true);
		}
	}
?>