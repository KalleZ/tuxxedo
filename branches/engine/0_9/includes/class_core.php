<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @package		Engine
	 *
	 * =============================================================================
	 */

	defined('TUXXEDO') or exit;


	/**
	 * Main Tuxxedo class, this acts as a mixed singleton/registry 
	 * object.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	final class Tuxxedo
	{
		/**
		 * Tuxxedo simple version, this contains the current 
		 * release in the form of:
		 *
		 * major.minor.release
		 *
		 * For example, 1.0, 1.0.1 ect.
	 	 *
		 * @var		string
		 */
		const VERSION			= '0.9.0';

		/**
		 * Tuxxedo version ID, this contains the version id in the form 
		 * of:
		 *
		 * id = (major_version * 10000) + (minor_version * 100) + release_version
		 *
		 * Examples of the version id string can be:
		 *
		 * 1.0.0	10000
		 * 1.1.0	10100
		 * 1.2.2	10202
		 *
		 * @var		integer
		 */
		const VERSION_ID		= '00901';

		/**
		 * Tuxxedo version string, this is the full version string, which 
		 * includes the pre-release name, version and the version number 
		 * of the upcoming version if pre-release. For example:
		 *
		 * 1.0.0 Alpha 1
		 * 1.0.3 Release Candidate 2
		 * 1.0.4
		 *
		 * @var		string
		 */
		const VERSION_STRING		= '0.9.1 (development preview)';

		/**
		 * For if we're in debug mode
		 *
		 * @var		boolean
		 */
		const DEBUG			= true;

		/**
		 * For exposing that an application using Engine
		 *
		 * @var		boolean
		 */
		const EXPOSE			= true;


		/**
		 * Holds the main instance
		 *
		 * @var		Tuxxedo
		 */
		private static $instance;

		/**
		 * Holds the configuration array
		 *
		 * @var		array
		 */
		private $configuration		= Array();

		/**
		 * Holds an array of the instances registered
		 *
		 * @var		array
		 */
		private $instances		= Array();


		/**
		 * Holds the list of global variables across 
		 * Engine
		 *
		 * @var		array
		 */
		private $globals		= Array();

		/**
		 * Disable the ability to construct the object
		 */
		private function __construct()
		{
		}

		/**
		 * Disable the ability to clone the object
		 */
		private function __clone()
		{
		}

		/**
		 * Magic get method, this handles overloading of registered 
		 * instances
		 *
		 * @param	string			Instance name
		 * @return	object			Returns the object instance if it exists, otherwise boolean false
		 */
		public function __get($name)
		{
			if(array_key_exists($name, self::$instance->instances))
			{
				return(self::$instance->instances[$name]);
			}

			return(false);
		}

		/**
		 * Initializes a new object instance, this implements the 
		 * singleton pattern and can be called from any context and 
		 * the same object is returned
		 *
		 * @param	array			The configuration array, this is only needed first time this is called
		 * @return	Tuxxedo			An instance to the Tuxxedo object
		 */
		public static function init(Array $configuration = NULL)
		{
			if(!(self::$instance instanceof self))
			{
				self::$instance = new self;
			}

			if(is_array($configuration))
			{
				self::$instance->configuration = $configuration;
			}

			return(self::$instance);
		}

		/**
		 * Registers a new instance and makes it accessable through 
		 * the name defined by the first parameter in the global scope 
		 * like the example below:
		 *
		 * <code>
		 * $tuxxedo = Tuxxedo::init();
		 * $tuxxedo->register('test', 'Classname');
 		 *
		 * $test->Methodname(); // or $tuxxedo->test->Methodname();
		 * </code>
		 *
		 * @param	string			The name of this instance
		 * @param	string			The class to register, this must implement a 'magic' method called invoke to work
		 * @return	void			No value is returned
		 *
		 * @throws	Tuxxedo_Basic_Exception	This a basic exception if the class doesn't exists or implements the magic invoke method
		 */
		public function register($refname, $class)
		{
			if(array_key_exists($refname, self::$instance->instances))
			{
				return;
			}
			elseif(!class_exists($class))
			{
				throw new Tuxxedo_Basic_Exception('Passed object class (%s) does not exists', $class);
			}
			elseif(method_exists($class, 'invoke'))
			{
				$instance = call_user_func(Array($class, 'invoke'), self::$instance, self::$instance->configuration, (array) self::getOptions());
			}

			self::$instance->set($refname, (isset($instance) ? $instance : new $class));
		}

		/**
		 * Sets a new reference in the registry
		 *
		 * @param	string			The name of the reference
		 * @param	mixed			The value of the reference
		 * @return	void			No value is returned
		 */
		public function set($refname, $reference)
		{
			$refname 		= strtolower($refname);
			$GLOBALS[$refname]	= self::$instance->instances[$refname] = $reference;
		}

		/**
		 * Gets a registered object instance
		 *
		 * @param	string		The name of the object to get
		 * @return	object		Returns an instance to the object and boolean false on error
		 */
		public static function get($obj)
		{
			if(!array_key_exists($obj, self::$instance->instances))
			{
				return(false);
			}

			return(self::$instance->instances[$obj]);
		}

		/**
		 * Gets the configuration array
		 *
	 	 * @return	array		Returns the configuration array if defined, otherwise false
		 */
		public static function getConfiguration()
		{
			if(isset(self::$instance->configuration))
			{
				return(self::$instance->configuration);
			}
			elseif(isset($GLOBALS['configuration']))
			{
				return($GLOBALS['configuration']);
			}

			return(false);
		}

		/**
		 * Gets the options from the datastore
		 *
	 	 * @return	array		Returns an array if the datastore is loaded and the options are cached, otherwise false
		 */
		public static function getOptions()
		{
			static $options;

			if(is_array($options) || isset(self::$instance->instances['cache']) && ($options = self::$instance->instances['cache']->fetch('options')))
			{
				return($options);
			}

			return(false);
		}

		/**
		 * Sets or gets a new global
		 *
		 * @param	string			The name of the variable to set
		 * @param	mixed			A value, this can be of any type, this is only used if adding or editing a variable
		 * @return	mixed			Returns the value of variable on both set and get, and boolean false if trying to get an undefined variable
		 */
		public static function globals($name, $value = NULL)
		{
			if(func_num_args() > 1)
			{
				self::$instance->globals[$name] = $value;
			}
			elseif(!array_key_exists($name, self::$instance->globals))
			{
				return(false);
			}

			return(self::$instance->globals[$name]);
		}
	}

	/**
	 * Information access, enables the ability for classes 
	 * to access their loaded information through the array-alike 
	 * syntax.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	abstract class Tuxxedo_InfoAccess implements ArrayAccess
	{
		/**
		 * Information array
		 * 
		 * @var		array
		 */
		protected $information		= Array();


		/**
		 * Checks whether an information is available 
		 *
		 * @param	scalar			The information row name to check
		 * @return	boolean			Returns true if the information is stored, otherwise false
		 */
		public function offsetExists($offset)
		{
			return(isset($this->information[$offset]));
		}

		/**
		 * Gets a value from the information store
		 * 
		 * @param	scalar			The information row name to get
		 * @return	mixed			Returns the information value, and NULL if the value wasn't found
		 */
		public function offsetGet($offset)
		{
			if(isset($this->information[$offset]))
			{
				return($this->information[$offset]);
			}
		}

		/**
		 * Sets a new information value, this is however not 
		 * allowed by default and the extending class must 
		 * override this method to allow it
		 *
		 * @param	scalar			The information row name to set
		 * @param	mixed			The new/update value for this row
		 * @return	void			No value is returned
		 */
		public function offsetSet($offset, $value)
		{
			throw new Tuxxedo_Basic_Exception('Information data may not be changed');
		}

		/**
		 * Deletes an information value, this is however not 
		 * allowed by default and the extending class must 
		 * override this method to allow it
		 *
		 * @param	scalar			The information row name to delete
		 * @return	void			No value is returned
		 */
		public function offsetUnset($offset)
		{
			throw new Tuxxedo_Basic_Exception('Infomation data may not be unset');
		}
	}

	/**
	 * Default exception, mainly used for general errors. All 
	 * Tuxxedo specific exceptions extend this exception.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class Tuxxedo_Exception extends Exception
	{
		/**
		 * Indicates whenever this is a fatal error or not
		 *
		 * @param	string			The error message, in a printf-alike formatted string or just a normal string
		 * @param	mixed			Optional argument #n for formatting
		 */
		public function __construct()
		{
			$args = func_get_args();

			if(!sizeof($args))
			{
				$args[0] = 'Unknown error';
			}

			parent::__construct(call_user_func_array('sprintf', $args));
		}
	}

	/**
	 * Form data exception, this exception is used to carry form data 
	 * so it can be displayed in a form if an error should occur while 
	 * processing the request
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class Tuxxedo_FormData_Exception extends Tuxxedo_Exception
	{
		/**
		 * Holds the current stored form data
		 *
		 * @var		array
		 */
		protected $formdata		= Array();


		/**
		 * Constructs a new formdata exception from an extended class
		 *
		 * @param	string			The exception error message
		 * @param	array			Form data to store as an array if any
		 */
		public function __construct($message, Array $formdata = NULL)
		{
			parent::__construct($message);

			if(is_array($formdata))
			{
				$this->formdata = $formdata;
			}
		}

		/**
		 * Gets the form data for a specific field
		 *
		 * @param	string			The field name to get
		 * @return	string			Returns the value of the form field, or false if field does not exists
		 */
		public function getField($name)
		{
			if(!isset($this->formdata[$name]))
			{
				return(false);
			}

			return((string) $this->formdata[$name]);
		}

		/**
		 * Gets all the fields within the form data exception
		 *
		 * @return	array			Returns an array with all the registered elements
		 */
		public function getFields()
		{
			return($this->formdata);
		}
	}

	/**
	 * Named form data exception, just like the regular formdata exception 
	 * this is used to identicate field names instead of values as the 
	 * regular one does.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class Tuxxedo_Named_Formdata_Exception extends Tuxxedo_FormData_Exception
	{
		/**
		 * Constructor
		 *
		 * @param	array			Named form data to store
		 */
		public function __construct(Array $formdata)
		{
			parent::__construct('Form validation failed', $formdata);
		}
	}

	/**
	 * Basic exception type, this is used for errors that 
	 * should act as fatal errors. If an exception of this 
	 * is caught by the default exception handler it will 
	 * terminate the execution.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class Tuxxedo_Basic_Exception extends Tuxxedo_Exception
	{
	}
?>