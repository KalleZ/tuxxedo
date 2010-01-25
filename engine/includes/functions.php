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
	 * Exception handler, this terminates the script execution 
	 * if an exception is fatal and buffer non fatal exceptions 
	 * so they can be displayed on the template
	 *
	 * @param	Exception		The exception to handle
	 * @return	void			No value is returned
	 */
	function tuxxedo_exception_handler(Exception $e)
	{
		if($e instanceof Tuxxedo_Named_Formdata_Exception)
		{
			global $tuxxedo;

			$list 		= '';
			$template	= $tuxxedo->style->fetch('error_validationbit');

			foreach($e->getFields() as $name)
			{
				eval('$list .= "' . $template . '";');
			}

			eval(page('error_validation'));
		}
		elseif($e instanceof Tuxxedo_Basic_Exception)
		{
			tuxxedo_doc_error($e);
		}
		elseif($e instanceof Tuxxedo_Exception)
		{
			tuxxedo_gui_error($e->getMessage());
		}

		if(Tuxxedo::globals('error_reporting'))
		{
			Tuxxedo::globals('errors')->append($e->getMessage());
		}
	}

	/**
	 * Error handler, this handles general errors from php. If 
	 * the script should error non fatal errors such as warnings 
	 * or notices, it will add them to the error buffer and show 
	 * then on the main template output. Note that this function is 
	 * not designed to be called directly and should be called by 
	 * php itself
	 *
	 * @param	integer			Error level
	 * @param	string			Error message
	 * @param	string			File
	 * @param	integer			Line number
	 * @return	void			No value is returned
	 *
	 * @throws	Tuxxedo_Basic_Exception	Throws a basic exception on fatal error types
	 */
	function tuxxedo_error_handler($level, $message, $file = NULL, $line = NULL)
	{
		if(!Tuxxedo::globals('error_reporting') || !(error_reporting() & $level))
		{
			return;
		}

		if($level & E_RECOVERABLE_ERROR)
		{
			tuxxedo_doc_error('<strong>Recoverable error:</strong> ' . $message);
		}
		elseif($level & E_USER_ERROR)
		{
			tuxxedo_doc_error('<strong>Fatal error:</strong> ' . $message);
		}
		elseif($level & E_NOTICE || $level & E_USER_NOTICE)
		{
			$message = '<strong>Notice:</strong> ' . $message;
		}
		elseif(TUXXEDO_PHP_VERSION >= 50300 && ($level & E_DEPRECATED || $level & E_USER_DEPRECATED))
		{
			$message = '<strong>Deprecated:</strong> ' . $message;
		}
		elseif($level & E_STRICT)
		{
			$message = '<strong>Strict standards:</strong> ' . $message;
		}
		else
		{
			$message = '<strong>Warning:</strong> ' . $message;
		}

		if(!is_null($file) && !is_null($line))
		{
			$message .= ' in ' . tuxxedo_trim_path($file) . ' on line ' . $line;
		}

		Tuxxedo::globals('errors')->append($message);
	}

	/**
	 * Print a document error (startup) and halts script execution
	 *
	 * @param 	string 			The message to show
	 * @return	void			No value is returned
	 */
	function tuxxedo_doc_error($e)
	{
		static $called;
		global $tuxxedo;

		if(!is_null($called))
		{
			return;
		}

		$called		= true;
		$buffer 	= ob_get_clean();
		$exception	= ($e instanceof Exception);
		$message	= ($exception ? $e->getMessage() : (string) $e);
		$errors 	= Tuxxedo::globals('errors');

		if($exception && $tuxxedo->db && $e instanceof Tuxxedo_SQL_Exception)
		{
			$message = 'An error occured while querying the database';

			if(TUXXEDO_DEBUG)
			{
				$message .=	':' . PHP_EOL . 
						PHP_EOL . 
						'<strong>Database driver:</strong> ' . constant(get_class($tuxxedo->db) . '::DRIVER_NAME') . PHP_EOL . 
						(($sqlstate = $e->getSQLState()) !== false ? '<strong>SQL State:</strong> ' . $sqlstate . PHP_EOL : '') . 
						'<strong>Error code:</strong> ' . $e->getCode() . PHP_EOL . 
						PHP_EOL . 
						str_replace(Array("\r", "\n"), '', $e->getMessage());
			}
		}
		elseif(empty($message))
		{
			$message = 'Unknown error occured!';
		}
		elseif(function_exists('utf8_encode'))
		{
			$message = utf8_encode($message);
		}

		if(TUXXEDO_DEBUG && sizeof($errors) && !$tuxxedo->style)
		{
			$message .= 	PHP_EOL . 
					PHP_EOL . 
					'The following error(s) were not sent to the output buffer:' . PHP_EOL . 
					'<ul>' . PHP_EOL;

			foreach($errors as $error)
			{
				$message .= '<li>' . $error . '</li>';
			}

			$message .= '</ul>' . PHP_EOL;
		}

		header('Content-Type: text/html');

		if(TUXXEDO_EXPOSE)
		{
			header('Tuxxedo-Engine: ' . Tuxxedo::VERSION_STRING);
		}

		echo(
			'<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . 
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . PHP_EOL . 
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . PHP_EOL . 
			'<head>' . PHP_EOL . 
			'<title>Tuxxedo Software Engine Error</title>' . PHP_EOL . 
			'<style type="text/css">' . PHP_EOL . 
			'<!--' . PHP_EOL . 
			'* { font-family: Calibri, Tahoma, Sans-serif; }' . PHP_EOL . 
			'code { font-family: Consolas, Monaco,  \'Courier New\', Monospace; }' . PHP_EOL . 
			'div.container-clear { clear: both; }' . PHP_EOL . 
			'div.container-left { float: left;' . (TUXXEDO_DEBUG ? ' width: 50%;' : '') . ' }' . PHP_EOL . 
			'div.container-right { background-color: #FFFFFF; float: right; }' . PHP_EOL . 
			'div.head { padding: 3px; }' . PHP_EOL . 
			'li, ul { margin: 0px; }' . PHP_EOL . 
			'td.strong, tr.strong { font-weight: bold; }' . PHP_EOL . 
			'.error, .head { background-color: #D2D2D2; }' . PHP_EOL . 
			'.error, td { padding: 7px; }' . PHP_EOL .
			'// -->' . PHP_EOL .
			'</style>' . PHP_EOL . 	
			'</head>' . PHP_EOL . 
			'<body>' . PHP_EOL . 
			(!stristr($buffer, '<?xml') ? $buffer . PHP_EOL : '') . 
			'<h1>Tuxxedo Engine Error</h1>' . PHP_EOL . 
			'<div class="error">' . PHP_EOL
			);

		if(TUXXEDO_DEBUG)
		{
			global $tuxxedo;

			echo(
				'<div class="container-right">' . PHP_EOL . 
				'<table width="100%" cellspacing="0" cellpadding="0">' . PHP_EOL . 
				'<tr>' . PHP_EOL . 
				'<td valign="top">' . 
				'<table width="25%" cellspacing="0" cellpadding="0">' . PHP_EOL . 
				'<tr>' . PHP_EOL . 
				'<td class="head strong">Version</td>' . PHP_EOL . 
				'<td nowrap="nowrap">' . Tuxxedo::VERSION_STRING . '</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL . 
				'<tr>' . PHP_EOL . 
				'<td class="head strong">Script</td>' . PHP_EOL . 
				'<td nowrap="nowrap">' . realpath($_SERVER['SCRIPT_FILENAME']) . '</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL . 
				'<tr>' . PHP_EOL . 
				'<td class="head strong">Timestamp</td>' . PHP_EOL . 
				'<td nowrap="nowrap">' . date('H:i:s, j/n - Y') . '</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL . 
				'</table>' . PHP_EOL . 
				'</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL . 
				'</table>' . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<div class="container-left">' . PHP_EOL . 
				nl2br($message) .  PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<div class="container-clear"></div>' . PHP_EOL . 
				'</div>' . PHP_EOL
				);

			$bt = ($exception ? tuxxedo_debug_backtrace($e) : tuxxedo_debug_backtrace());

			if(sizeof($bt))
			{
				echo(
					'<h1>Debug backtrace</h1>' . PHP_EOL . 
					'<table width="100%" cellspacing="0" cellpadding="0">' . PHP_EOL . 
					'<tr class="head">' . PHP_EOL . 
					'<td>&nbsp;</td>' . PHP_EOL . 
					'<td class="head strong">Call</td>' . PHP_EOL . 
					'<td class="head strong">File</td>' . PHP_EOL . 
					'<td class="head strong">Line</td>' . PHP_EOL . 
					'<td class="head strong">Notes</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				foreach($bt as $n => $trace)
				{
					echo(
						'<tr' . ($trace->current ? ' class="strong"' : '') . '>' . PHP_EOL . 
						'<td rowspan="2" class="strong">' . ++$n . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap">' . $trace->call . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap" width="100%">' . $trace->file . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap">' . $trace->line . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap">' . $trace->notes . '</td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);

					if(!empty($trace->callargs))
					{
						echo(
							'<tr>' . PHP_EOL . 
							'<td colspan="4">' . PHP_EOL . 
							'<div class="head">' . PHP_EOL . 
							$trace->callargs . PHP_EOL . 
							'</div>' . PHP_EOL . 
							'</rd>' . PHP_EOL . 
							'</tr>' . PHP_EOL
							);
					}
				}

				echo(
					'</table>' . PHP_EOL
					);
			}

			if($tuxxedo->db && $tuxxedo->db->getNumQueries())
			{
				echo(
					'<h1>Executed SQL Queries</h1>' . PHP_EOL . 
					'<table width="100%" cellspacing="0" cellpadding="0">' . PHP_EOL . 
					'<tr class="head">' . PHP_EOL . 
					'<td width="100">&nbsp;</td>' . PHP_EOL . 
					'<td class="head strong" width="100%">SQL</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				foreach($tuxxedo->db->getQueries() as $n => $sql)
				{
					echo(
						'<tr>' . PHP_EOL . 
						'<td class="strong">' . ++$n . '</td>' . PHP_EOL . 
						'<td><code>' . $sql . '</code></td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);
				}

				echo(
					'</table>' . PHP_EOL
					);
			}
		}
		else
		{
			echo(
				'<div class="container-left">' . PHP_EOL . 
				nl2br($message) .  PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<div class="container-clear"></div>' . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<p>' . PHP_EOL . 
				'<em>' . 
				'This error was generated by Tuxxedo Engine ' . Tuxxedo::VERSION . ', if you are a site visitor then please notify the webmaster about the problem.' . 
				'</em>' . PHP_EOL . 
				'</p>'
				);
		}

		die(
			'</body>' . PHP_EOL . 
			'</html>'
			);
	}

	/**
	 * Trims a file path to hide its path prior to the root 
	 * of the application
	 *
	 * @param	string			The path to trim
	 * @param	boolean			Should the path also be trimmed if debug mode is on? Defaults to true
	 * @return	string			The trimmed path
	 */
	function tuxxedo_trim_path($path, $debug_trim = true)
	{
		if(!$debug_trim && TUXXEDO_DEBUG)
		{
			return($path);
		}

		return(ltrim(str_replace(Array('/', '\\', TUXXEDO_DIR), Array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, ''), $path), DIRECTORY_SEPARATOR));
	}

	/**
	 * Shutdown handler
	 *
	 * @return	void			No value is returned
	 */
	function tuxxedo_shutdown_handler()
	{
		$errors = Tuxxedo::globals('errors');

		if(!TUXXEDO_DEBUG || (!$errors || !$errors->count()))
		{
			return;
		}

		global $tuxxedo;

		$buffer = '<br />';

		foreach($errors as $error)
		{
			$buffer .= $error . '<br />';
		}

		Tuxxedo::globals('errors', new ArrayObject);

		if(!$tuxxedo->style)
		{
			Tuxxedo_doc_error($buffer);
		}
		else
		{
			$output = ob_get_clean();

			if($pos = stripos($output, '</body>'))
			{
				$output = substr_replace($output, $buffer . '</body>', $pos, 7);
			}
			else
			{
				$output .= '<br />' . $buffer;
			}

			echo($output);
		}
	}

	/**
	 * Autoload handler
	 *
	 * The autoloader routes builtin classes to their respective 
	 * root file. If a non builtin class is attempted to be loaded, 
	 * then the autoloader will assume the class to be declared in 
	 * the file `class_<lowercase_name>.php'
	 *
	 * @param	string			Class name to load
	 */
	function tuxxedo_autoload_handler($class)
	{
		static $map, $dependencies;

		if(!$map)
		{
			$map 		= Array(
						/* Core classes, always available */
						'tuxxedo'					=> 'core', 
						'tuxxedo_infoaccess'				=> 'core', 
						'tuxxedo_exception'				=> 'core', 
						'tuxxedo_formdata_exception'			=> 'core', 
						'tuxxedo_named_formdata_exception'		=> 'core', 
						'tuxxedo_basic_exception'			=> 'core', 

						/* Database core, drivers are not autoloadable */
						'tuxxedo_database'				=> 'database', 
						'tuxxedo_database_result'			=> 'database', 
						'tuxxedo_database_driver'			=> 'database', 
						'tuxxedo_database_driver_result'		=> 'database', 
						'tuxxedo_sql_exception'				=> 'database', 

						/* Caching utilities */
						'tuxxedo_datastore'				=> 'cache', 

						/* Data filtering */
						'tuxxedo_datafilter'				=> 'filter', 

						/* Data managers, drivers are not autoloadable */
						'tuxxedo_datamanager'				=> 'datamanager', 
						'tuxxedo_datamanager_api'			=> 'datamanager', 

						/* Style API */
						'tuxxedo_style'					=> 'template', 

						/* Template compiler */
						'tuxxedo_template_compiler'			=> 'template_compiler', 
						'tuxxedo_template_compiler_exception'		=> 'template_compiler', 
						'tuxxedo_template_compiler_dummy'		=> 'template_compiler', 

						/* Internationalization API */
						'tuxxedo_internationalization'			=> 'intl', 
						'tuxxedo_internationalization_phrasegroup'	=> 'intl', 

						/* Users and sessions API */
						'tuxxedo_usersession'				=> 'user'
						);
		}

		$class = strtolower((string) $class);

		if(empty($class) || class_exists($class))
		{
			return;
		}

		if(!isset($map[$class]))
		{
			require(TUXXEDO_DIR . '/includes/class_' . $class . '.php');
			return;
		}

		require(TUXXEDO_DIR . '/includes/class_' . $map[$class] . '.php');
	}

	/**
	 * Handles multiple errors repeatingly
	 *
	 * @param	string			A sprintf-like format
	 * @param	array			An array with elements to loop through
	 * @return	void			No value is returned
	 *
	 * @throws	Tuxxedo_Basic_Exception	Throws a basic exception until the errors have been cleared
	 */
	function tuxxedo_multi_error($format, Array $elements)
	{
		if(!sizeof($elements))
		{
			return;
		}

		throw new Tuxxedo_Basic_Exception($format, reset($elements));
	}

	/**
	 * Issues a redirect and terminates the script
	 *
	 * @param	string			The message to show to the user while redirecting
	 * @param	string			The redirect location
	 * @param	string			Redirect timeout in seconds
	 * @return	void			No value is returned
	 */
	function tuxxedo_redirect($message, $location, $timeout = 3)
	{
		eval(page('redirect'));
		exit;
	}

	/**
	 * Issues a redirect using headers and then terminates the script
	 *
	 * @param	string			The redirect location
	 * @return	void			No value is returned
	 */
	function tuxxedo_header_redirect($location)
	{
		header('Location: ' . $location);
		exit;
	}

	/**
	 * Prints an error message using the current loaded 
	 * theme and then terminates the script
	 *
	 * @param	string			The error message
	 * @return	void			No value is returned
	 */
	function tuxxedo_gui_error($message)
	{
		eval(page('error'));
		exit;
	}

	/**
	 * Date format function
	 *
	 * @param	integer			The timestamp to format
	 * @return	string			Returns the formatted date
	 */
	function tuxxedo_date($timestamp)
	{
		global $tuxxedo;

		$tuxxedo->datetime->modify($timestamp);

		return($tuxxedo->datetime->format($tuxxedo->cache->options['date_format']));
	}

	/**
	 * Generates code to print a page
	 *
	 * @param	string			The template name to print
	 * @param	boolean			Include the wrapper (header and footer) templates?
	 * @return	void			No value is returned
	 */
	function page($template, $wrapper = false)
	{
		global $tuxxedo;

		if($wrapper)
		{
			global $header, $footer;

			return('echo("$header ' . $tuxxedo->style->fetch($template) . ' $footer");');
		}

		return(
			'global $header, $footer;' . 
			'echo("' . $tuxxedo->style->fetch($template) . '");'
			);
	}

	/**
	 * Email validation, check if a supplied email 
	 * is written with a correct syntax.
	 *
	 * This function is based on code by:
	 * Alexander Meesters <admin@budgetwebhosting.nl>
	 *
	 * @param	string			The email address to validate
	 * @return	boolean			Returns true if the email is valid, otherwise false
	 */
	function is_valid_email($email)
	{
		if(extension_loaded('filter'))
		{
			return((boolean) filter_var($email, FILTER_VALIDATE_EMAIL));
		}

		if(!preg_match('/[^@]{1,64}@[^@]{1,255}/', $email))
		{
			return(false);
		}

 		$email_array 	= explode('@', $email);
  		$local_array 	= explode('.', $email_array[0]);
		$local_length	= sizeof($local_array);

		for($i = 0; $i < $local_length; ++$i)
		{
			if(!preg_match('�(([A-Za-z0-9!#$%&\'*+/=?^_`{|}~-][A-Za-z0-9!#$%&\'*+/=?^_`{|}~\.-]{0,63})|("[^(\\|")]{0,62}"))�', $local_array[$i]))
			{
				return(false);
			}
		}

		if(!preg_match('@\[?[0-9\.]+\]?@', $email_array[1]))
		{
			$domain_array = explode('.', $email_array[1]);

			if(sizeof($domain_array) < 2)
			{
				return(false);
			}

			for($i = 0; $i < sizeof($domain_array); ++$i)
			{
				if(!preg_match('@(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))@', $domain_array[$i]))
				{
					return(false);
				}
			}
		}

		return(true);
	}

	/**
	 * Fetches user and usergroup information for 
	 * a specific user. This function will return 
	 * session information about the user if the 
	 * user id matches the one thats currently 
	 * logged in, in this session.
	 *
	 * @param	mixed			A unique identifier to find the user
	 * @param	boolean			Find by email? Defaults to find by id
	 * @return	object			Returns an object with user and usergroup information on success, otherwise false
	 *
	 * @throws	Tuxxedo_Basic_Exception	Throws a basic exception if the database call fails
	 */
	function fetch_userinfo($identifier, $by_email = false)
	{
		global $tuxxedo;

		if($tuxxedo->userinfo !== false && (!$by_email && $tuxxedo->userinfo->id == $identifier || $by_email && $tuxxedo->userinfo->email == $identifier))
		{
			return($tuxxedo->userinfo);
		}

		$query = $tuxxedo->db->query('
						SELECT 
							* 
						FROM 
							`' . TUXXEDO_PREFIX . 'users` 
						WHERE 
							' . ($by_email ? '`email` = \'%s\'' : '`id` = %d') . '
						LIMIT 1', $identifier);

		if(!$query || !$query->getNumRows())
		{
			return(false);
		}
		elseif(($userinfo = $query->fetchObject()) !== false && !isset($tuxxedo->cache->usergroups[$tuxxedo->userinfo->usergroupid]))
		{
			return(false);
		}

		$userinfo->usergroupinfo = $tuxxedo->cache->usergroups[$userinfo->usergroupid];

		return($userinfo);
	}

	/**
	 * Hashes a password using a salt
	 *
	 * @param	string			The password to encrypt
	 * @param	string			The unique salt for this password
	 * @return	string			Returns the computed password
	 */
	function password_hash($password, $salt)
	{
		return(sha1(sha1($password) . $salt));
	}

	/**
	 * Checks if a password matches with its hash value
	 *
	 * @param	string			The raw password
	 * @param	string			The user salt that generated the password
	 * @param	string			The hashed password
	 * @return	boolean			Returns true if the password matches, otherwise false
	 */
	function is_valid_password($password, $salt, $hash)
	{
		return(password_hash($password, $salt) === $hash);
	}

	/**
	 * Format a translation string
	 *
	 * @param	string			The phrase to perform replacements on
	 * @param	scalar			Replacement string #1
	 * @param	scalar			Replacement string #n
	 * @return	string			Returns the formatted translation string
	 */
	function format_phrase()
	{
		$args = func_get_args();
		$size = sizeof($args);

		if(!$size)
		{
			return('');
		}
		elseif($size == 1)
		{
			return($args[0]);
		}

		for($i = 0; $i < $size; ++$i)
		{
			$args[0] = str_replace('{' . $i . '}', $args[$i], $args[0]);
		}

		return($args[0]);
	}
?>