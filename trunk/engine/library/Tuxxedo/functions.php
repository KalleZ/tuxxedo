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

	use Tuxxedo\Registry;
	use Tuxxedo\Exception;
	use Tuxxedo\Version;

	/**
	 * Exception handler, this terminates the script execution 
	 * if an exception is fatal and buffer non fatal exceptions 
	 * so they can be displayed on the template
	 *
	 * @param	   Exception		   The exception to handle
	 * @return	  void			No value is returned
	 */
	function tuxxedo_exception_handler(\Exception $e)
	{
		static $registry;

		if(!$registry)
		{
			$registry = class_exists('\Tuxxedo\Registry', false);
		}

		if($e instanceof Exception\Basic)
		{
			tuxxedo_doc_error($e);
		}
		elseif($e instanceof Exception)
		{
			tuxxedo_gui_error($e->getMessage());
		}

		if($registry && Registry::globals('error_reporting'))
		{
			$errors = Registry::globals('errors');

			if(!is_array($errors))
			{
				Registry::globals('errors', Array($e->getMessage()));
			}
			else
			{
				array_push($errors, $e->getMessage());

				Registry::globals('errors', $errors);
			}
		}
		else
		{
			echo('<strong>Exception:</strong> ' . $e->getMessage() . '<br /> <br />');
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
	 * @param	   integer		 Error level
	 * @param	   string		  Error message
	 * @param	   string		  File
	 * @param	   integer		 Line number
	 * @return	void			No value is returned
	 *
	 * @throws	  Tuxxedo_Basic_Exception Throws a basic exception on fatal error types
	 */
	function tuxxedo_error_handler($level, $message, $file = NULL, $line = NULL)
	{
		static $registry;

		if(!$registry)
		{
			$registry = class_exists('\Tuxxedo\Registry', false);
		}

		if($registry && !Registry::globals('error_reporting') || !(error_reporting() & $level))
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
		elseif($level & E_DEPRECATED || $level & E_USER_DEPRECATED)
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

		if($file !== NULL && $line !== NULL)
		{
			$message .= ' in ' . tuxxedo_trim_path($file) . ' on line ' . $line;
		}

		if($registry)
		{
			$errors = Registry::globals('errors');

			if(!is_array($errors))
			{
				Registry::globals('errors', Array($message));
			}
			else
			{
				array_push($errors, $message);

				Registry::globals('errors', $errors);
			}
		}
		else
		{
			echo($message . '<br /> <br />');
		}
	}

	/**
	 * Print a document error (startup) and halts script execution
	 *
	 * @param	   string		  The message to show
	 * @return	  void			No value is returned
	 */
	function tuxxedo_doc_error($e)
	{
		static $called;
		global $registry, $configuration;

		if($called !== NULL)
		{
			return;
		}

		$called	 = true;
		$buffer	 = ob_get_clean();
		$exception	  = ($e instanceof \Exception);
		$utf8	   = function_exists('utf8_encode');
		$message	= ($exception ? $e->getMessage() : (string) $e);
		$errors	= ($registry ? Registry::globals('errors') : false);
		$application	= ($configuration['application']['name'] ? $configuration['application']['name'] . ($configuration['application']['version'] ? ' ' . $configuration['application']['version'] : '') : false);

		if($exception && $registry->db && $e instanceof Exception\SQL)
		{
			$message = 'An error occured while querying the database';

			if(TUXXEDO_DEBUG)
			{
				$message .=	 ':' . PHP_EOL . 
						PHP_EOL . 
						'<strong>Database driver:</strong> ' . constant(get_class($registry->db) . '::DRIVER_NAME') . PHP_EOL . 
						(($sqlstate = $e->getSQLState()) !== false ? '<strong>SQL State:</strong> ' . $sqlstate . PHP_EOL : '') . 
						'<strong>Error code:</strong> ' . $e->getCode() . PHP_EOL . 
						PHP_EOL . 
						'<strong>Error message:</strong>' . PHP_EOL . 
						str_replace(Array("\r", "\n"), '', $e->getMessage()) . PHP_EOL . 
						PHP_EOL . 
						'<strong>SQL:</strong>' . PHP_EOL . 
						str_replace(Array("\r", "\n"), '', $e->getSQL());
			}
		}
		elseif(empty($message))
		{
			$message = 'Unknown error occured!';
		}
		elseif($utf8)
		{
			$message = utf8_encode($message);
		}

		if(TUXXEDO_DEBUG && $errors && sizeof($errors) && !$registry->style)
		{
			$message .=	 PHP_EOL . 
					'The following errors were logged while executing:' . PHP_EOL . 
					'<ul>' . PHP_EOL;

			foreach($errors as $error)
			{
				$message .= '<li>' . (!$utf8 ?: utf8_encode($error)) . '</li>';
			}

			$message .= '</ul>' . PHP_EOL;
		}

		header('Content-Type: text/html');

		echo(
			'<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . 
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . PHP_EOL . 
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . PHP_EOL . 
			'<head>' . PHP_EOL . 
			'<title>Tuxxedo Software Engine Error</title>' . PHP_EOL . 
			'<style type="text/css">' . PHP_EOL . 
			'<!--' . PHP_EOL . 
			'body { background-color: #021420; color: #3B7286; font-family: "Helvetica Neue", Helvetica, Trebuchet MS, Verdana, Tahoma, Arial, sans-serif; font-size: 82%; padding: 0px 30px; }' . PHP_EOL . 
			'h1 { color: #FFFFFF; }' . PHP_EOL . 
			'table td { padding: 5px; }' . PHP_EOL . 
			'table td div.hr { border-top: 2px solid #3B7286; height: 1px; }' . PHP_EOL . 
			'table tr.head { background-color: #D2D2D2; }' . PHP_EOL . 
			'table tr.strong * { font-weight: bold; }' . PHP_EOL . 
			'.box { background-color: #D2D2D2; border: 3px solid #D2D2D2; border-radius: 4px; }' . PHP_EOL . 
			'.box .inner { background-color: #FFFFFF; border-radius: 4px; padding: 6px; }' . PHP_EOL . 
			'.box .outer { padding: 6px; }' . PHP_EOL . 
			'// -->' . PHP_EOL .
			'</style>' . PHP_EOL .  
			'</head>' . PHP_EOL . 
			'<body>' . PHP_EOL . 
			(!stristr($buffer, '<?xml') ? $buffer . PHP_EOL : '') . 
			'<h1>Tuxxedo Engine Error</h1>' . PHP_EOL
			);

		if(TUXXEDO_DEBUG)
		{
			echo(
				'<div class="box">' . PHP_EOL . 
				'<div class="outer">' . PHP_EOL
				);

			if($application)
			{
				echo(
					'<strong>Application:</strong> ' . $application . ' - '
					);
			}

			echo(
				'<strong>Engine Version:</strong> ' . Version::SIMPLE . ' - ' . 
				'<strong>Script:</strong> ' . realpath($_SERVER['SCRIPT_FILENAME']) . ' - '
				);

			if(($date = tuxxedo_date(NULL, 'H:i:s j/n - Y (e)')))
			{
				echo(
					'<strong>Timestamp:</strong> ' . $date . PHP_EOL
					);
			}

			echo(
				'</div>' . PHP_EOL . 
				'<div class="inner">' . PHP_EOL . 
				nl2br($message) . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'</div>' . PHP_EOL
				);

			$bt = ($exception ? tuxxedo_debug_backtrace($e) : tuxxedo_debug_backtrace());

			if($bts = sizeof($bt))
			{
				echo(
					'<h1>Debug backtrace</h1>' . PHP_EOL . 
					'<div class="box">' . PHP_EOL . 
					'<div class="inner">' . PHP_EOL . 
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
						'<td rowspan="2"><h3>' . ++$n . '</h3></td>' . PHP_EOL . 
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
							'<td colspan="5">' . PHP_EOL . 
							'<div class="head">' . PHP_EOL . 
							'<em>' . $trace->callargs . '</em>' . PHP_EOL . 
							'</div>' . PHP_EOL . 
							'</rd>' . PHP_EOL . 
							'</tr>' . PHP_EOL
							);
					}

					if($n != $bts)
					{
						echo(
							'<tr>' . PHP_EOL . 
							'<td colspan="5">' . PHP_EOL . 
							'<div class="hr">' . PHP_EOL . 
							'</div>' . PHP_EOL . 
							'</rd>' . PHP_EOL . 
							'</tr>' . PHP_EOL
							);
					}
				}

				echo(
					'</table>' . PHP_EOL . 
					'</div>' . PHP_EOL . 
					'</div>' . PHP_EOL
					);
			}

			if($registry->db && $registry->db->getNumQueries())
			{
				echo(
					'<h1>Executed SQL Queries</h1>' . PHP_EOL . 
					'<div class="box">' . PHP_EOL . 
					'<div class="inner">' . PHP_EOL . 
					'<table width="100%" cellspacing="0" cellpadding="0">' . PHP_EOL . 
					'<tr class="head">' . PHP_EOL . 
					'<td width="100">&nbsp;</td>' . PHP_EOL . 
					'<td class="head strong" width="100%">SQL</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				foreach($registry->db->getQueries() as $n => $sql)
				{
					echo(
						'<tr>' . PHP_EOL . 
						'<td><h3>' . ++$n . '</h3></td>' . PHP_EOL . 
						'<td><code>' . $sql . '</code></td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);
				}

				echo(
					'</table>' . PHP_EOL . 
					'</div>' . PHP_EOL . 
					'</div>' . PHP_EOL
					);
			}
		}
		else
		{
			echo(
				'<div class="box">' . PHP_EOL . 
				'<div class="inner">' . PHP_EOL . 
				nl2br($message) .  PHP_EOL . 
				'</div>' . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<p>' . PHP_EOL . 
				'<em>' . 
				'This error was generated by ' . ($application ? $application . ' (' : '') . 'Tuxxedo Engine ' . Version::SIMPLE . ($application ? ')' : '') . 
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
	 * Formattable doc error
	 *
	 * @param	string			The error message, in a printf-alike formatted string or just a normal string
	 * @param	mixed			Optional argument #n for formatting
	 * @return	Void			No value is returned
	 */
	function tuxxedo_doc_errorf()
	{
		$args = func_get_args();

		if(!sizeof($args))
		{
			$args[0] = 'Unknown error';
		}

		tuxxedo_doc_error(call_user_func_array('sprintf', $args));
	}

	/**
	 * Trims a file path to hide its path prior to the root 
	 * of the application
	 *
	 * @param	   string		  The path to trim
	 * @param	   boolean		 Should the path also be trimmed if debug mode is on? Defaults to true
	 * @return	  string		  The trimmed path
	 */
	function tuxxedo_trim_path($path, $debug_trim = true)
	{
		if(!$debug_trim && TUXXEDO_DEBUG)
		{
			return($path);
		}

		if(empty($path))
		{
			return('');
		}

		return(DIRECTORY_SEPARATOR . ltrim(str_replace(Array('/', '\\', TUXXEDO_DIR), Array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, ''), $path), DIRECTORY_SEPARATOR));
	}

	/**
	 * Shutdown handler
	 *
	 * @return	  void			No value is returned
	 */
	function tuxxedo_shutdown_handler()
	{
		static $registry;

		if(!$registry)
		{
			$registry = class_exists('\Tuxxedo\Registry', false);
		}

		$errors = ($registry ? Registry::globals('errors') : false);

		if(!$registry || !TUXXEDO_DEBUG || (!$errors || !sizeof($errors)))
		{
			return;
		}

		global $registry;

		$buffer = '<br />';

		foreach($errors as $error)
		{
			$buffer .= $error . '<br />';
		}

		Registry::globals('errors', Array());

		if(!$registry->style)
		{
			tuxxedo_doc_error($buffer);
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
	 * Handles multiple errors repeatingly
	 *
	 * @param	   string		  A sprintf-like format
	 * @param	   array		   An array with elements to loop through
	 * @return	  void			No value is returned
	 *
	 * @throws	  Tuxxedo_Basic_Exception Throws a basic exception until the errors have been cleared
	 */
	function tuxxedo_multi_error($format, Array $elements)
	{
		if(!sizeof($elements))
		{
			return;
		}

		throw new Exception\Basic($format, reset($elements));
	}

	/**
	 * Issues a redirect and terminates the script
	 *
	 * @param	   string		  The message to show to the user while redirecting
	 * @param	   string		  The redirect location
	 * @param	   string		  Redirect timeout in seconds
	 * @return	  void			No value is returned
	 */
	function tuxxedo_redirect($message, $location, $timeout = 3)
	{
		eval(page('redirect'));
		exit;
	}

	/**
	 * Issues a redirect using headers and then terminates the script
	 *
	 * @param	   string		  The redirect location
	 * @return	  void			No value is returned
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
	 * @param	   string		  The error message
	 * @param	   boolean		 Whether to show the 'Go back' button or not
	 * @return	  void			No value is returned
	 */
	function tuxxedo_gui_error($message, $goback = true)
	{
		eval(page('error'));
		exit;
	}

	/**
	 * Date format function
	 *
	 * @param	   integer		 The timestamp to format
	 * @param	   string		  Optional format to use, defaults to the format defined within the options
	 * @return	  string		  Returns the formatted date
	 */
	function tuxxedo_date($timestamp = NULL, $format = NULL)
	{
		global $registry;

		if($timestamp === NULL)
		{
			$timestamp = (defined('TIMENOW') ? TIMENOW : TIMENOW_UTC);
		}

		if($format === NULL)
		{
			$format = $registry->cache->options['date_format'];
		}

		if(!$registry->datetime)
		{
			return(date($format, $timestamp));
		}

		$old_timestamp = $registry->datetime->getTimestamp();

		$registry->datetime->setTimestamp($timestamp);
		$format = $registry->datetime->format($format);
		$registry->datetime->setTimestamp($old_timestamp);

		return($format);
	}

	/**
	 * Generates code to print a page
	 *
	 * @param	   string		  The template name to print
	 * @return	  void			No value is returned
	 */
	function page($template)
	{
		global $registry;

		return(
			'global $header, $footer;' . 
			'echo("' . $registry->style->fetch($template) . '");'
			);
	}

	/**
	 * Email validation, check if a supplied email 
	 * is written with a correct syntax.
	 *
	 * This function is based on code by:
	 * Alexander Meesters <admin@budgetwebhosting.nl>
	 *
	 * @param	   string		  The email address to validate
	 * @return	  boolean		 Returns true if the email is valid, otherwise false
	 */
	function is_valid_email($email)
	{
		static $have_filter_ext;

		if($have_filter_ext === NULL)
		{
			$have_filter_ext = extension_loaded('filter');
		}

		if($have_filter_ext)
		{
			return((boolean) filter_var($email, FILTER_VALIDATE_EMAIL));
		}

		if(!preg_match('/[^@]{1,64}@[^@]{1,255}/', $email))
		{
			return(false);
		}

		$email_array	= explode('@', $email);
		$local_array	= explode('.', $email_array[0]);
		$local_length   = sizeof($local_array);

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
	 * Format a translation string
	 *
	 * @param	   string		  The phrase to perform replacements on
	 * @param	   scalar		  Replacement string #1
	 * @param	   scalar		  Replacement string #n
	 * @return	  string		  Returns the formatted translation string
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
			$args[0] = str_replace('{' . ($i + 1) . '}', $args[$i], $args[0]);
		}

		return($args[0]);
	}
?>