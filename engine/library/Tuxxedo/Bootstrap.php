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
	use Tuxxedo\Datastore;
	use Tuxxedo\LocalCache;
	use Tuxxedo\Registry;


	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Bootstraper, this class works as an encapsulated and easier way 
	 * to write working bootstrapers while also not having to remember 
	 * startup orders and other similar things that can cause confusion.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 * @since		1.1.0
	 */
	class Bootstrap
	{
		/**
		 * Bootstrap mode - Minimal
		 *
		 * @var		integer
		 */
		const MODE_MINIMAL		= 1;

		/**
		 * Bootstrap mode - Normal
		 *
		 * @var		integer
		 */
		const MODE_NORMAL		= 2;

		/**
		 * Bootstrap mode - Custom
		 *
		 * @var		integer
		 */
		const MODE_CUSTOM		= 3;

		/**
		 * Loader flag - Core
		 *
		 * @var		integer
		 */
		const FLAG_CORE			= 1;

		/**
		 * Loader flag - Date
		 *
		 * @var		integer
		 */
		const FLAG_DATE			= 2;

		/**
		 * Loader flag - Database
		 *
		 * @var		integer
		 */
		const FLAG_DATABASE		= 4;

		/**
		 * Loader flag - Datastore
		 *
		 * @var		integer
		 */
		const FLAG_DATASTORE		= 8;

		/**
		 * Loader flag - Internationalization
		 *
		 * @var		integer
		 */
		const FLAG_INTL			= 16;

		/**
		 * Loader flag - Options
		 *
		 * @var		integer
		 */
		const FLAG_OPTIONS		= 32;

		/**
		 * Loader flag - Style
		 *
		 * @var		integer
		 */
		const FLAG_STYLE		= 64;

		/**
		 * Loader flag - User
		 *
		 * @var		integer
		 */
		const FLAG_USER			= 128;


		/**
		 * Holds which elements thats been loaded (flags)
		 *
		 * @var		integer
		 */
		protected static $loaded	= 0;

		/**
		 * Holds the registered hooks
		 *
		 * @var		array
		 * @since	1.2.0
		 */
		protected static $hooks		= [];

		/**
		 * Holds the elements that should be preloaded
		 *
		 * @var		array
		 */
		protected static $preloadables	= [
							'datastore'	=> [], 
							'phrasegroups'	=> [], 
							'templates'	=> []
							];

		/**
		 * Holds the various flags supported
		 *
		 * @var		array
		 * @since	1.2.0
		 */
		protected static $flags		= [
							self::FLAG_CORE, 
							self::FLAG_DATE, 
							self::FLAG_DATABASE, 
							self::FLAG_DATASTORE, 
							self::FLAG_INTL, 
							self::FLAG_OPTIONS, 
							self::FLAG_STYLE, 
							self::FLAG_USER
							];


		/**
		 * Sets elements that should be preloaded by the next init call
		 *
		 * @param	string			The type to preloadables for
		 * @param	array			The elements to preload
		 * @return	void			No value is returned
		 */
		public static function setPreloadables($type, Array $elements)
		{
			$type = \strtolower($type);

			if(!isset(self::$preloadables[$type]) || !$elements || !($elements = \array_unique($elements)))
			{
				return;
			}

			self::$preloadables[$type] = \array_unique(\array_merge(self::$preloadables[$type], $elements));
		}

		/**
		 * Hooks into the initilization code and runs a callback
		 * before the default code is executed.
		 *
		 * If the callback returns true, then the flag will be marked 
		 * as initialized, otherwise the default code is executed.
		 *
		 * To reset a hook, then simply pass NULL as the callback. This 
		 * unregisters ALL hooks registered to that paticular flag.
		 *
		 * @param	integer			The loader flag, this cannot be a bitmask
		 * @param	callback		The loader callback
		 * @param	string			The index of the preloadables, if any to send to the callback
		 * @return	void			No value is returned
		 *
		 * @since	1.2.0
		 */
		public static function setHook($flag, $callback, $preloadables = NULL)
		{
			$flag = (integer) $flag;

			if(!\in_array($flag, self::$flags))
			{
				return;
			}
			elseif(isset(self::$hooks[$flag]) && $callback === NULL)
			{
				unset(self::$hooks[$flag]);

				return;
			}
			elseif(!\is_callable($callback))
			{
				return;
			}

			if(!isset(self::$hooks[$flag]))
			{
				self::$hooks[$flag] = [];
			}

			self::$hooks[$flag][] = [
							'callback'	=> $callback, 
							'preloadables'	=> (isset(self::$preloadables[$preloadables]) ? $preloadables : NULL)
							];
		}

		/**
		 * Initializes the bootstraper
		 *
		 *
		 * @param	integer			The bootstraper mode
		 * @param	integer			The loader flags, this only have an effect on custom bootstraper mode
		 * @return	void			No value is returned
		 *
		 * @changelog	1.2.0			After a hook is executed, the preloadables is no longer unset, but reset to an array
		 */
		public static function init($mode = self::MODE_NORMAL, $flags = NULL)
		{
			static $self;

			if(!$self)
			{
				$self = new static;
			}

			switch($mode)
			{
				case(self::MODE_MINIMAL):
				{
					$flags = self::FLAG_CORE | self::FLAG_DATE;
				}
				break;
				case(self::MODE_CUSTOM):
				{
					$flags = (integer) $flags;

					if(!($flags & self::FLAG_CORE))
					{
						$flags |= self::FLAG_CORE;
					}

					if(!($flags & self::FLAG_DATE) && ($flags & self::FLAG_USER))
					{
						$flags |= self::FLAG_DATE;
					}
				}
				break;
				case(self::MODE_NORMAL):
				default:
				{
					$flags = self::FLAG_CORE | self::FLAG_DATE | self::FLAG_DATABASE | self::FLAG_DATASTORE | self::FLAG_INTL | self::FLAG_OPTIONS | self::FLAG_STYLE | self::FLAG_USER;
				}
				break;
			}

			if(self::$loaded)
			{
				$flags &= ~self::$loaded;
			}

			if(!$flags)
			{
				return;
			}

			if(\method_exists($self, 'preInit'))
			{
				static::preInit($flags);
			}

			if($flags & self::FLAG_CORE)
			{
				\error_reporting(-1);

				\date_default_timezone_set('UTC');

				require(\TUXXEDO_LIBRARY . '/configuration.php');
				require(\TUXXEDO_LIBRARY . '/Tuxxedo/Loader.php');
				require(\TUXXEDO_LIBRARY . '/Tuxxedo/functions.php');

				\tuxxedo_handler('exception', '\tuxxedo_exception_handler');
				\tuxxedo_handler('error', '\tuxxedo_error_handler');
				\tuxxedo_handler('shutdown', '\tuxxedo_shutdown_handler');
				\tuxxedo_handler('autoload', '\Tuxxedo\Loader::load');


				/**
				 * Set database table prefix constant
				 *
				 * @var		string
				 */
				\define('TUXXEDO_PREFIX', $configuration['database']['prefix']);


				$registry = Registry::init($configuration);

				Registry::globals('error_reporting', 	true);
				Registry::globals('errors', 		[]);

				if($configuration['application']['debug'] && $configuration['debug']['trace'])
				{
					$registry->register('trace', '\Tuxxedo\Debug\Trace');
				}
			}
			else
			{
				$registry = Registry::init();
			}

			if(self::$hooks)
			{
				foreach(self::$hooks as $flag => $hooks)
				{
					if(!$hooks || !($flags & $flag))
					{
						continue;
					}

					foreach($hooks as $hook)
					{
						if(\call_user_func_array($hook['callback'], [$registry, (($preloadables = $hook['preloadables']) ? self::$preloadables[$hook['preloadables']] : NULL), &$configuration]) && $flag != self::FLAG_CORE)
						{
							$flags &= ~$flag;

							self::$preloadables[$preloadables] = [];
						}
					}

					unset(self::$hooks[$flag]);
				}

				$registry = Registry::init($configuration);
			}

			if($flags & self::FLAG_DATE)
			{
				/**
				 * Current time constant
				 *
				 * @var		integer
				 */
				\define('TIMENOW_UTC', isset($_SERVER['REQUEST_TIME']) ? (integer) $_SERVER['REQUEST_TIME'] : \time());


				if(!($flags & self::FLAG_USER))
				{
					$registry->set('timezone', new \DateTimeZone('UTC'));
					$registry->set('datetime', new \DateTime('now', $registry->timezone));
				}
			}

			if($flags & self::FLAG_DATABASE)
			{
				$registry->register('db', '\Tuxxedo\Database');
			}

			if($flags & self::FLAG_DATASTORE)
			{
				$registry->set('datastore', new Datastore);

				if(self::$preloadables['datastore'])
				{
					$cache_buffer = [];

					$registry->datastore->cache(self::$preloadables['datastore'], $cache_buffer) or \tuxxedo_multi_error('Unable to load datastore elements', $cache_buffer);

					unset($cache_buffer);

					self::$preloadables['datastore'] = [];
				}
			}

			if($flags & self::FLAG_OPTIONS && $registry->datastore && $registry->datastore->options)
			{
				$registry->register('options', '\Tuxxedo\Options');
			}

			if($flags & self::FLAG_USER)
			{
				$registry->register('user', '\Tuxxedo\User');

				$registry->set('userinfo', $registry->user->getUserInfo());
				$registry->set('usergroup', $registry->user->getUserGroupInfo());

				if(($flags & self::FLAG_DATE) || (self::$loaded & self::FLAG_DATE))
				{
					$registry->set('timezone', new \DateTimeZone(\strtoupper(empty($registry->userinfo->id) ? (isset($registry->options) ? $registry->options->date_timezone : 'UTC') : $registry->userinfo->timezone)));
					$registry->set('datetime', new \DateTime('now', $registry->timezone));
				}
			}

			if($flags & self::FLAG_STYLE)
			{
				$registry->register('style', '\Tuxxedo\Style');

				if(self::$preloadables['templates'])
				{
					$cache_buffer = [];

					$registry->style->cache(self::$preloadables['templates'], $cache_buffer) or \tuxxedo_multi_error('Unable to load templates', $cache_buffer);

					unset($cache_buffer);

					self::$preloadables['templates'] = [];
				}
			}

			if($flags & self::FLAG_INTL)
			{
				$registry->register('intl', '\Tuxxedo\Intl');

				if(self::$preloadables['phrasegroups'])
				{
					$cache_buffer = [];

					$registry->intl->cache(self::$preloadables['phrasegroups'], $cache_buffer) or \tuxxedo_multi_error('Unable to load phrase groups', $cache_buffer);

					unset($cache_buffer);

					self::$preloadables['phrasegroups'] = [];
				}
			}

			if(\method_exists($self, 'postInit'))
			{
				static::postInit($registry);
			}

			self::$loaded |= $flags;
		}
	}
?>