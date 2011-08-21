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
	use Tuxxedo\Datamanager;
	use Tuxxedo\Registry;
	use Tuxxedo\Session;


	/**
	 * Include check
	 */
	defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * User session class, this class manages the current user 
	 * session information and permission bitfields.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class User extends InfoAccess
	{
		/**
		 * User info constant, also get session information if 
		 * available
		 *
		 * @var		integer
		 */
		const OPT_SESSION	= 1;

		/**
		 * User info constant, cache the user information within 
		 * the class to save a query if trying to query the same 
		 * user again  twice
		 *
		 * @var		integer
		 */
		const OPT_CACHE		= 2;

		/**
		 * User info constant, return a reference to the current 
		 * stored information, no matter if a user is logged on or 
		 * not
		 *
		 * @var		integer
		 */
		const OPT_CURRENT_ONLY	= 4;


		/**
		 * Private instance to the Tuxxedo registry
		 *
		 * @var		\Tuxxedo\Registry
		 */
		protected $registry;

		/**
		 * User information
		 *
		 * @var		stdClass
		 */
		protected $userinfo;

		/**
		 * Usergroup information
		 *
		 * @var		stdClass
		 */
		protected $usergroupinfo;

		/**
		 * User session
		 *
		 * @var		\Tuxxedo\Session
		 */
		protected $session;

		/**
		 * User session datamanager
		 *
		 * @var		\Tuxxedo\Datamanager\Adapter\Session
		 */
		protected $sessiondm;

		/**
		 * Cached userinfo, for calls to get user information 
		 * about a specific user
		 *
		 * @var		array
		 */
		protected $cache	= Array();


		/**
		 * Constructor, instanciates a new user session.
		 *
		 * @param	boolean				Whether to auto detect if a user is logged in or not
		 * @param	boolean				Whether to start a session or not
		 */
		public function __construct($autodetect = true, $session = true)
		{
			$this->registry = Registry::init();

			if($session && $autodetect)
			{
				$this->session 		= $this->registry->register('session', '\Tuxxedo\Session');
				$this->sessiondm	= Datamanager\Adapter::factory('session', Session::$id, false);

				if(($userid = Session::get('userid')) !== false && !empty($userid) && ($userinfo = $this->getUserInfo($userid, 'id', self::OPT_SESSION)) !== false && $userinfo->password == Session::get('password'))
				{
					$this->userinfo				= $userinfo;
					$this->usergroupinfo			= (object) $this->registry->cache->usergroups[$userinfo->usergroupid];
					$this->userinfo->permissions		= (integer) $userinfo->permissions;
					$this->usergroupinfo->permissions 	= (integer) $this->usergroupinfo->permissions;
				}
			}

			if(!$this->userinfo)
			{
				$this->userinfo = $this->usergroupinfo = new \stdClass;
			}

			$this->userinfo->session	= $this->session;
			$this->information		= $this->userinfo;

			if($session)
			{
				$this->sessiondm['userid']		= (isset($this->userinfo->id) ? $this->userinfo->id : 0);
				$this->sessiondm['location']		= \TUXXEDO_SELF;
				$this->sessiondm['useragent']		= \TUXXEDO_USERAGENT;
			}

			$this->setPermissionConstants();
		}

		/**
		 * Destructor, executes the cleanup queries etc.
		 */
		public function __destruct()
		{
			if($this->session instanceof Session)
			{
				$this->sessiondm->save();

				$this->registry->db->query('
								DELETE FROM 
									`' . \TUXXEDO_PREFIX . 'sessions` 
								WHERE 
									`lastactivity` + %d < %d', $this->registry->options->cookie_expires, \TIMENOW_UTC);
			}
		}

		/**
		 * Authenticates a user. If a user is currently logged in, then it 
		 * will be logged out and the session id will be regenerated.
		 *
		 * A user can be logged in by a unique identifier, such as:
		 *  - Username
		 *  - Email
		 *  - etc.
		 *
		 * To attempt a login, the constructor must be instanciated with the 
		 * $session parameter set to true (default)
		 *
		 * @param	string			User identifier
		 * @param	string			User's password (raw format)
		 * @param	string			The identifier field to check and validate against
		 * @return	boolean			Returns true if the user was logged in with success, otherwise false
		 */
		public function login($identifier, $password, $identifier_field = 'username')
		{
			if(empty(Session::$id))
			{
				return(false);
			}
			elseif(isset($this->userinfo->id) && $this->userinfo->id)
			{
				$this->logout(true);
			}

			$userinfo = $this->getUserInfo($identifier, $identifier_field);

			if(!$userinfo || !self::isValidPassword($password, $userinfo->salt, $userinfo->password))
			{
				return(false);
			}

			Session::set('userid', $userinfo->id);
			Session::set('password', $userinfo->password);

			$this->userinfo				= $userinfo;
			$this->usergroupinfo			= (object) $this->registry->cache->usergroups[$userinfo->usergroupid];
			$this->sessiondm['userid'] 		= $userinfo->id;
			$this->userinfo->permissions		= (integer) $this->userinfo->permissions;
			$this->usergroupinfo->permissions 	= (integer) $this->usergroupinfo->permissions;

			$this->registry->set('userinfo', $this->userinfo);
			$this->registry->set('usergroup', $this->usergroupinfo);

			$this->setPermissionConstants();

			return(true);
		}

		/**
		 * Log the current logged in user out
		 *
		 * @param	boolean			Whether to restart the session or not
		 * @return	void			No value is returned
		 */
		public function logout($restart = false)
		{
			if(!isset($this->userinfo->id) || !$this->userinfo->id)
			{
				return;
			}

			$this->userinfo = $this->usergroupinfo = new \stdClass;

			$this->sessiondm->delete();

			Session::terminate();

			if($restart)
			{
				Session::start();
			}
		}

		/**
		 * Fetch user data about a specific user
		 *
		 * @param	string			The user identifier
		 * @param	string			The user identifier field, this defaults to 'id' to lookup by user id
		 * @param	integer			Additional options, this uses the Tuxxedo_User::OPT_* constants as a bitmask
		 * @return	object			Returns a user data object with all user information if a user was found, otherwise false
		 */
		public function getUserInfo($identifier = NULL, $identifier_field = 'id', $options = 0)
		{
			$identifier_field = \strtolower($identifier_field);

			if(isset($this->userinfo->id))
			{
				if(($identifier !== NULL && isset($this->userinfo->{$identifier_field}) && $this->userinfo->{$identifier_field} == $identifier) || $identifier === NULL)
				{
					return($this->userinfo);
				}
			}
			elseif($options & self::OPT_CURRENT_ONLY)
			{
				return($this->userinfo);
			}
			elseif($options & self::OPT_CACHE)
			{
				if($identifier_field == 'id' && isset($this->cache[$identifier]))
				{
					return($this->cache[$identifier]);
				}
				elseif($this->cache)
				{
					foreach($this->cache as $userinfo)
					{
						if($userinfo->{$identifier_field} == $identifier)
						{
							return($userinfo);
						}
					}
				}
			}

			if($options & self::OPT_SESSION)
			{
				$query = $this->registry->db->equery('
									SELECT
										' . \TUXXEDO_PREFIX . 'sessions.*, 
										' . \TUXXEDO_PREFIX . 'users.*
									FROM
										`' . \TUXXEDO_PREFIX . 'sessions` 
									LEFT JOIN
										`' . \TUXXEDO_PREFIX . 'users` 
										ON 
											' . \TUXXEDO_PREFIX . 'sessions.userid = ' . \TUXXEDO_PREFIX . 'users.id 
										WHERE 
											' . \TUXXEDO_PREFIX . 'users.%s = \'%s\' 
									LIMIT 1', $identifier_field, $identifier);
			}
			else
			{
				$query = $this->registry->db->equery('
									SELECT 
										* 
									FROM 
										`' . \TUXXEDO_PREFIX . 'users` 
									WHERE 
										`%s` = \'%s\'
									LIMIT 1', $identifier_field, $identifier);
			}

			if($query && $query->getNumRows())
			{
				$userinfo = $query->fetchObject();

				if($options & self::OPT_CACHE)
				{
					$this->cache[$userinfo->id] = $userinfo;
				}

				return($userinfo);
			}

			return(false);
		}

		/**
		 * Get usergroup information about the current user's group 
		 * or a customed defined based on the usergroup id
		 *
		 * @param	integer			The usergroup id to check, if NULL is passed then the current logged in usergroup is returned
		 * @return	object			Returns a standard object with the relevant usergroup information if found, otherwise false is returned
		 */
		public function getUserGroupInfo($id = NULL)
		{
			if($id === NULL)
			{
				if(isset($this->usergroupinfo->id))
				{
					return($this->usergroupinfo);
				}

				return(false);
			}
			elseif(isset($this->registry->cache->usergroups[$id]))
			{
				return($this->registry->cache->usergroups[$id]);
			}

			return(false);
		}

		/**
		 * Checks whether the user id a member of a 
		 * specific usergroup. This only checks for the 
		 * primary usergroup
		 *
		 * @param	integer			The usergroup id to check
		 * @return	boolean			Returns true if the user is a member of that usergroup otherwise false
		 */
		public function isMemberOf($groupid)
		{
			return(isset($this->userinfo->id) && $this->userinfo->id && $this->userinfo->usergroupid == $groupid);
		}

		/**
		 * Checks whether this session have a user logon or not
		 *
		 * @return	boolean			Returns true if a user is logged on, otherwise false
		 */
		public function isLoggedIn()
		{
			return(isset($this->userinfo->id) && $this->userinfo->id);
		}

		/**
		 * Checks whether the user's permissions can access a 
		 * certain feature. Note that this checks for the user's 
		 * permissions only, not per usergroup permissions
		 *
		 * @param	integer			The permission to check
		 * @param	boolean			Whether to check if the user's group have permission as a fallback
		 * @return	boolean			Returns true if the user is granted access, otherwise false
		 */
		public function isGranted($permission, $checkgroup = true)
		{
			if(!isset($this->userinfo->id) || !$this->userinfo->id)
			{
				return(false);
			}

			$granted = ($this->userinfo->permissions & $permission) !== 0;

			if(!$granted && $checkgroup)
			{
				return($this->isGroupGranted($permission));
			}

			return($granted);
		}


		/**
		 * Checks whether the user's usergroup permissions can 
		 * access a certain feature. Note that this checks for 
		 * the user's usergroup permissions only, not per 
		 * user permissions
		 *
		 * @param	integer			The permission to check
		 * @return	boolean			Returns true if the usergroup is granted access, otherwise false
		 */
		public function isGroupGranted($permission)
		{
			if(!isset($this->userinfo->id) || !$this->userinfo->id)
			{
				return(false);
			}

			return(($this->usergroupinfo['permissions'] & $permission) !== 0);
		}

		/**
		 * Checks if a password matches with its hash value
		 *
		 * @param	string			The raw password
		 * @param	string			The user salt that generated the password
		 * @param	string			The hashed password
		 * @return	boolean			Returns true if the password matches, otherwise false
		 */
		public static function isValidPassword($password, $salt, $hash)
		{
			return(self::getPasswordHash($password, $salt) === $hash);
		}

		/**
		 * Hashes a password using a salt
		 *
		 * @param	string			The password to encrypt
		 * @param	string			The unique salt for this password
		 * @return	string			Returns the computed password
		 */
		public static function getPasswordHash($password, $salt)
		{
			return(\sha1(\sha1($password) . $salt));
		}

		/**
		 * Generates a salt for using with password hashing
		 *
		 * @param	integer			The number of bytes the salt should be, must be 8 or greater
		 * @return	string			Returns the computed salt
		 */
		public static function getPasswordSalt($length = 8)
		{
			static $salt_range;

			if($length < 8)
			{
				return(false);
			}

			if(!$salt_range)
			{
				$salt_range = 'abcdefghijklmnopqrstuvwxyz0123456789|()[]{}!?=%&-_';
			}

			$salt = '';

			for($char = 0; $char < $length; ++$char)
			{
				$c = \mt_rand(0, 49);

				if($c < 26 && \mt_rand(0, 1))
				{
					$salt .= \strtoupper($salt_range{$c});
				}
				else
				{
					$salt .= $salt_range{$c};
				}
			}

			return($salt);
		}

		/**
		 * Defines global constant values of datastore permissions
		 *
		 * @return	void			No value is returned
		 */
		protected function setPermissionConstants()
		{
			if(!$this->registry->cache->permissions)
			{
				return;
			}

			foreach($this->registry->cache->permissions as $name => $bits)
			{
				$name = 'PERMISSION_' . strtoupper($name);

				if(!defined('\\' . $name))
				{
					define($name, (integer) $bits);
				}
			}
		}
	}
?>