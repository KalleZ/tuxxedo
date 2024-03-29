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
	use Tuxxedo\User as UserAPI;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Datamanager for users
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	class User extends Adapter implements Hooks\Cache
	{
		/**
		 * Datamanager name
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const DM_NAME			= 'user';

		/**
		 * Identifier name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const ID_NAME			= 'id';

		/**
		 * Table name for the datamanager
		 *
		 * @var		string
		 *
		 * @since	1.2.0
		 */
		const TABLE_NAME		= 'users';


		/**
		 * Usergroup identifier copy
		 *
		 * @var		integer
		 * @since	1.1.0
		 */
		protected $usergroupid;

		/**
		 * Whether or not to rehash the password
		 *
		 * @var		boolean
		 * @since	1.2.0
		 */
		protected $rehash_password	= false;


		/**
		 * Fields for validation of users
		 *
		 * @var		array
		 *
		 * @changelog	1.1.0			Changed the name of 'styleid' to 'style_id'
		 * @changelog	1.1.0			Changed the name of 'languageid' to 'language_id'
		 */
		protected $fields		= [
							'id'			=> [
											'type'		=> parent::FIELD_PROTECTED
											], 
							'username'		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidUsername']
											], 
							'email'			=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidEmail']
											], 
							'name'			=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_STRING_EMPTY, 
											'default'	=> ''
											], 
							'password'		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidPassword']
											], 
							'usergroupid'		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidUsergroup']
											], 
							'salt'			=> [
											'type'		=> parent::FIELD_PROTECTED
											], 
							'style_id'		=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidStyleId']
											], 
							'language_id'		=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidLanguageId']
											], 
							'timezone'		=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidTimezone'], 
											'parameters'	=> []
											], 
							'timezone_offset'	=> [
											'type'		=> parent::FIELD_PROTECTED, 
											'default'	=> 0
											], 

							'permissions'		=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_NUMERIC, 
											'default'	=> 0
											]
							];


		/**
		 * Constructor, fetches a new user based on its id if set
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The user id
		 * @param	integer				Additional options to apply on the datamanager
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws an exception if the user id is set and it failed to load for some reason
		 * @throws	\Tuxxedo\Exception\SQL		Throws a SQL exception if a database call fails
		 */
		public function __construct(Registry $registry, $identifier = NULL, $options = parent::OPT_DEFAULT, Adapter $parent = NULL)
		{
			if($identifier !== NULL)
			{
				$user = $registry->db->query('
								SELECT 
									* 
								FROM 
									`' . \TUXXEDO_PREFIX . 'users` 
								WHERE 
									`id` = %d', $identifier);

				if(!$user || !$user->getNumRows())
				{
					throw new Exception('Invalid user id');
				}

				$this->data 					= $user->fetchAssoc();
				$this->data['permissions']			= (integer) $this->data['permissions'];
				$this->usergroupid				= (integer) $this->data['usergroupid'];
				$this->identifier 				= $identifier;
				$this->fields['timezone_offset']['parameters']	= [$this->data['timezone']];

				$user->free();
			}

			parent::init($registry, $options, $parent);
		}

		/**
		 * Overloads the arrayaccess set method, so that we can catch 
		 * overloads in the set method
		 *
		 * @param	scalar			The information row name to set
		 * @param	mixed			The information row value to set
		 * @return	void			No value is returned
		 *
		 * @since	1.2.0
		 */
		public function offsetSet($field, $value)
		{
			$field = \strtolower($field);

			if($field == 'timezone_offset')
			{
				$this->fields['timezone']['parameters'] = [$value];
			}
			elseif($field == 'password')
			{
				$this->rehash_password = true;
			}

			$this->data[$field] = $value;
		}

		/**
		 * Checks whether a usergroup is valid
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The usergroup id to check for validity
		 * @return	boolean				Returns true if the usergroup is loaded and exists in the datastore cache, otherwise false
		 */
		public static function isValidUsergroup(Adapter $dm, Registry $registry, $id = NULL)
		{
			return(isset($registry->datastore->usergroups[$id]));
		}

		/**
		 * Checks whether a timezone based by its name is valid
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The timezone name to check for validity
		 * @return	boolean				Returns true if the timezone is loaded and exists in the datastore cache, otherwise false
		 */
		public static function isValidTimezone(Adapter $dm, Registry $registry, $timezone = NULL)
		{
			if($timezone === NULL && (($dm->options & parent::OPT_LOAD_ONLY) || !$dm->identifier))
			{
				$timezone = 'UTC';
			}

			if(!isset($registry->datastore->timezones[$timezone]))
			{
				return(false);
			}

			$dm->data['timezone_offset'] = $registry->datastore->timezones[$timezone];

			return(true);
		}

		/**
		 * Checks whether a user name is taken or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The username to check
		 * @return	boolean				Returns true if the username is free to be taken, otherwise false
		 *
		 * @changelog	1.2.0				This method now requires a username to be at least 3 characters and no longer than 32 characters big
		 */
		public static function isValidUsername(Adapter $dm, Registry $registry, $username = NULL)
		{
			if($username)
			{
				$len = \strlen($username);

				if($len < 3 || $len > 32)
				{
					return(false);
				}
			}

			if(!self::isAvailableUserField($registry, 'username', $username))
			{
				return(isset($dm->data['id']) && !empty($dm->data['id']));
			}

			return(true);
		}

		/**
		 * Checks whether an email address is taken or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The username to check
		 * @return	boolean				Returns true if the email is free to be taken, otherwise false
		 */
		public static function isValidEmail(Adapter $dm, Registry $registry, $email = NULL)
		{
			if(!$email || !\filter_var($email, \FILTER_VALIDATE_EMAIL))
			{
				return(false);
			}

			if(!self::isAvailableUserField($registry, 'email', $email))
			{
				return(isset($dm->data['id']) && !empty($dm->data['id']));
			}

			return(true);
		}

		/**
		 * Checks whether a style id is valid or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The style id
		 * @return	boolean				True if the style exists, otherwise false
		 */
		public static function isValidStyleId(Adapter $dm, Registry $registry, $styleid = NULL)
		{
			return($styleid === NULL || $registry->datastore->styleinfo && isset($registry->datastore->styleinfo[$styleid]));
		}

		/**
		 * Checks whether a language id is valid or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The language id
		 * @return	boolean				True if the language exists, otherwise false
		 */
		public static function isValidLanguageId(Adapter $dm, Registry $registry, $languageid = NULL)
		{
			return($languageid === NULL || $registry->datastore->languages && isset($registry->datastore->languages[$languageid]));
		}

		/**
		 * Helper validation routine to check a single field in the database
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The field to check
		 * @param	string				The value to check
		 * @return	boolean				Returns false if the value exists, otherwise true
		 *
		 * @since	1.1.0
		 *
		 * @changelog	1.2.0				The return value is now negated
		 */
		protected static function isAvailableUserField(Registry $registry, $field, $value)
		{
			$query = $registry->db->equery('
							SELECT 
								`id` 
							FROM 
								`' . \TUXXEDO_PREFIX . 'users` 
							WHERE 
								`%s` = \'%s\' 
							LIMIT 1', $field, $value);

			return(!($query && $query->getNumRows()));
		}

		/**
		 * Checks whether a password is valid or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The language id
		 * @return	boolean				True if the password is vald, otherwise false
		 *
		 * @since	1.1.0
		 */
		public static function isValidPassword(Adapter $dm, Registry $registry, $password = NULL)
		{
			if($password !== NULL && $dm->rehash_password)
			{
				$dm->data['salt'] 	= UserAPI::getPasswordSalt();
				$dm->data['password']	= UserAPI::getPasswordHash($password, $dm->data['salt']);
			}
			elseif(($dm->options & parent::OPT_LOAD_ONLY) || !$dm->identifier)
			{
				return(false);
			}

			return(true);
		}

		/**
		 * Updates the number of users in the usergroup
		 *
		 * @return	boolean				Returns true if the datastore was updated with success, otherwise false
		 *
		 * @since	1.1.0
		 */
		public function rebuild()
		{
			if(!isset($this->information['usergroupid']) || !$this->usergroupid || !isset($this->registry->datastore->usergroups[$this->information['usergroupid']]) || !isset($this->registry->datastore->usergroups[$this->usergroupid]))
			{
				return(false);
			}
			elseif($this->information['usergroupid'] == $this->usergroupid)
			{
				return(true);
			}

			$usergroups = $this->registry->datastore->usergroups;

			if($this->context == parent::CONTEXT_SAVE)
			{
				++$usergroups[$this->information['usergroupid']]['users'];
			}
			elseif($this->context == parent::CONTEXT_DELETE)
			{
				--$usergroups[$this->usergroupid]['users'];
			}

			return($this->registry->datastore->rebuild('usergroups', $usergroups));
		}
	}
?>