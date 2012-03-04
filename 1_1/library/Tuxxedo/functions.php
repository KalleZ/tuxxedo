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
	 * Aliasing rules
	 */
	use Tuxxedo\Exception;
	use Tuxxedo\Registry;
	use Tuxxedo\Version;


	/**
	 * Exception handler, this terminates the script execution 
	 * if an exception is fatal and buffer non fatal exceptions 
	 * so they can be displayed on the template
	 *
	 * @param	\Exception			The exception to handle
	 * @return	void				No value is returned
	 */
	function tuxxedo_exception_handler(\Exception $e)
	{
		if($e instanceof Exception\Basic)
		{
			tuxxedo_doc_error($e);
		}
		elseif($e instanceof Exception\FormData)
		{
			tuxxedo_error_list(htmlspecialchars($e->getMessage(), ENT_QUOTES), $e->getFields());
		}
		elseif($e instanceof Exception)
		{
			tuxxedo_error(htmlspecialchars($e->getMessage(), ENT_QUOTES));
		}

		if(Registry::globals('error_reporting'))
		{
			$errors = (array) Registry::globals('errors');

			array_push($errors, $e->getMessage());

			Registry::globals('errors', $errors);
		}
		else
		{
			echo('<strong>Exception:</strong> ' . htmlentities($e->getMessage()) . '<br /> <br />');
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
	 * @param	integer				Error level
	 * @param	string				Error message
	 * @param	string				File path
	 * @param	integer				Line number
	 * @return	void				No value is returned
	 *
	 * @throws	\Tuxxedo\Exception\Basic	Throws a basic exception on fatal error types
	 */
	function tuxxedo_error_handler($level, $message, $file = NULL, $line = NULL)
	{
		if(!Registry::globals('error_reporting') || !(error_reporting() & $level))
		{
			return;
		}

		$message = htmlentities($message);

		if($level & E_RECOVERABLE_ERROR)
		{
			if(($spos = strpos($message, TUXXEDO_DIR)) !== false)
			{
				$message = substr_replace($message, tuxxedo_trim_path(substr($message, $spos, $epos = strrpos($message, ' on line') - $spos)), $spos, $epos);
			}

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

		$errors = (array) Registry::globals('errors');

		array_push($errors, $message);

		Registry::globals('errors', $errors);
	}

	/**
	 * Handler register
	 *
	 * This function is a wrapper for registering handlers to various 
	 * functions, calling this function for registering handlers should 
	 * be registered using this function or some features may stop working 
	 * unexpectedly
	 *
	 * @param	string				The handler to register, can be one of 'error', 'exception', 'shutdown' or 'autoload'
	 * @param	callback			The callback to register to the handler
	 * @return	callback			Returns a callback, if only the first parameter is set, may also return false on error in any case
	 */
	function tuxxedo_handler($handler, $callback = NULL)
	{
		static $handlers;
		static $references;

		if($references === NULL)
		{
			$references 	= Array(
						'error'		=> 'set_error_handler', 
						'exception'	=> 'set_exception_handler', 
						'shutdown'	=> 'register_shutdown_function', 
						'autoload'	=> 'spl_autoload_register'
						);
		}

		$handler = strtolower($handler);

		if(!isset($references[$handler]))
		{
			return(false);
		}

		if($callback === NULL)
		{
			if(!isset($handlers[$handler]))
			{
				return(false);
			}

			return($handlers[$handler]);
		}

		$handlers[$handler] = $callback;

		$references[$handler]($callback);
	}

	/**
	 * Print a document error (startup) and halts script execution
	 *
	 * @param	mixed				The message to show, this can also be an exception
	 * @return	void				No value is returned
	 */
	function tuxxedo_doc_error($e)
	{
		static $called;

		if($called !== NULL)
		{
			return;
		}

		$registry 	= Registry::init();
		$configuration	= Registry::getConfiguration();

		$called		= true;
		$buffer		= ob_get_clean();
		$exception	= ($e instanceof \Exception);
		$exception_sql	= $exception && $registry->db && $e instanceof Exception\SQL;
		$utf8		= function_exists('utf8_encode');
		$message	= ($exception ? htmlentities($e->getMessage()) : (string) $e);
		$errors		= ($registry ? Registry::globals('errors') : false);
		$application	= ($configuration['application']['name'] ? $configuration['application']['name'] . ($configuration['application']['version'] ? ' ' . $configuration['application']['version'] : '') : false);

		if(empty($message))
		{
			$message = 'No error message given';
		}
		elseif($exception_sql)
		{
			$message = (defined('TUXXEDO_DEBUG') && TUXXEDO_DEBUG ? str_replace(Array("\r", "\n"), '', $e->getMessage()) : 'An error occured while querying the database');
		}
		elseif($utf8)
		{
			$message = utf8_encode($message);
		}

		header('Content-Type: text/html');

		echo(
			'<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . 
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . PHP_EOL . 
			'<html dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . PHP_EOL . 
			'<head>' . PHP_EOL . 
			'<title>Tuxxedo Engine Error</title>' . PHP_EOL . 
			'<style type="text/css">' . PHP_EOL . 
			'body { background-color: #021420; color: #3B7286; font-family: "Helvetica Neue", Helvetica, Trebuchet MS, Verdana, Tahoma, Arial, sans-serif; font-size: 82%; padding: 0px 30px; }' . PHP_EOL . 
			'h1 { color: #FFFFFF; }' . PHP_EOL . 
			'h1 sup { background-color: #3B7286; border-radius: 4px; color: #FFFFFF; font-size: 35%; padding: 1px 3px; }' . PHP_EOL . 
			'h2 { margin: 20px 0px 0px 0px; }' . PHP_EOL . 
			'h2 span { background-color: #D2D2D2; border-top-left-radius: 4px; border-top-right-radius: 4px; padding: 5px; padding-bottom: 0px; }' . PHP_EOL . 
			'li, ul { margin: 0px; }' . PHP_EOL . 
			'table tr.head td { background-color: #D2D2D2; padding: 5px; border-radius: 4px; }' . PHP_EOL . 
			'table tr.row, table tr.row * { margin: 0px; padding: 2px 5px; }' . PHP_EOL . 
			'table td.strong, table td.strong *, table tr.strong * { font-weight: bold; }' . PHP_EOL . 
			'table tr.strong td { background-color: #3B7286; border-radius: 4px; color: #FFFFFF; }' . PHP_EOL . 
			'table tr.strong td.empty { background-color: #FFFFFF; }' . PHP_EOL . 
			'.box { background-color: #D2D2D2; border: 3px solid #D2D2D2; border-radius: 4px; }' . PHP_EOL . 
			'.box.edge-title { border-top-left-radius: 0px; }' . PHP_EOL . 
			'.box .inner { background-color: #FFFFFF; border-radius: 4px; padding: 6px; }' . PHP_EOL . 
			'.box .outer { padding: 6px; }' . PHP_EOL . 
			'.infobox { background-color: #D2D2D2; border: 3px solid #D2D2D2; border-radius: 4px; padding: 6px; }' . PHP_EOL . 
			'.infobox td { padding-right: 5px; }' . PHP_EOL . 
			'.infobox td.value { background-color: #FFFFFF; border-radius: 4px; padding: 6px; }' . PHP_EOL . 
			'.spacer { margin-bottom: 10px; }' . PHP_EOL . 
			'</style>' . PHP_EOL .  
			'</head>' . PHP_EOL . 
			'<body>' . PHP_EOL . 
			(defined('TUXXEDO_DEBUG') && TUXXEDO_DEBUG && $buffer ? strip_tags($buffer) . PHP_EOL : '') . 
			'<h1>Tuxxedo Engine Error <sup>v' . Version::SIMPLE . '</sup></h1>' . PHP_EOL
			);

		if(defined('TUXXEDO_DEBUG') && TUXXEDO_DEBUG)
		{
			echo(
				'<div class="box">' . PHP_EOL . 
				'<div class="inner">' . PHP_EOL . 
				'<div class="infobox" style="float: left; width: 400px;">' . PHP_EOL . 
				'<table cellspacing="2" cellpadding="0">' . PHP_EOL
				);

			if($application)
			{
				echo(
					'<tr>' . PHP_EOL . 
					'<td><strong>Application:</strong></td>' . PHP_EOL . 
					'<td class="value" width="100%">' . $application . '</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);
			}

			echo(
				'<tr>' . PHP_EOL . 
				'<td nowrap="nowrap"><strong>Engine Version:</strong></td>' . PHP_EOL . 
				'<td class="value" width="100%">' . Version::FULL . '</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL .  
				'<tr>' . PHP_EOL . 
				'<td><strong>Script:</strong></td>' . PHP_EOL . 
				'<td class="value" nowrap="nowrap">' . tuxxedo_trim_path(realpath($_SERVER['SCRIPT_FILENAME'])) . '</td>' . PHP_EOL . 
				'</tr>' . PHP_EOL
				);

			if(($date = tuxxedo_date(NULL, 'H:i:s j/n - Y (e)')))
			{
				echo(
					'<tr>' . PHP_EOL . 
					'<td><strong>Timestamp:</strong></td>' . PHP_EOL . 
					'<td class="value">' . $date . '</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);
			}

			if($exception)
			{
				$class = get_class($e);

				if($class{0} != '\\')
				{
					$class = '\\' . $class;
				}

				echo(
					'<tr>' . PHP_EOL . 
					'<td nowrap="nowrap"><strong>Exception Type:</strong></td>' . PHP_EOL . 
					'<td class="value">' . $class . '</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);
			}

			if($exception_sql)
			{
				echo(
					'<tr>' . PHP_EOL . 
					'<td colspan="2">&nbsp;</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL . 
					'<tr>' . PHP_EOL . 
					'<td nowrap="nowrap"><strong>Database Driver:</strong></td>' . PHP_EOL . 
					'<td class="value" width="100%">' . $e->getDriver() . '</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL . 
					'<tr>' . PHP_EOL . 
					'<td nowrap="nowrap"><strong>Error code:</strong></td>' . PHP_EOL . 
					'<td class="value" width="100%">' . $e->getCode() . '</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				if(($sqlstate = $e->getSQLState()) !== false)
				{
					echo(
						'<tr>' . PHP_EOL . 
						'<td nowrap="nowrap"><strong>SQL State:</strong></td>' . PHP_EOL . 
						'<td class="value" width="100%">' . $sqlstate . '</td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);
				}
			}

			echo(
				'</table>' . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<div style="margin: 0px 0px 10px 430px;">' . PHP_EOL . 
				'<div class="infobox">' . PHP_EOL . 
				nl2br($message) . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'<br />' . PHP_EOL
				);

			if(defined('TUXXEDO_DEBUG') && TUXXEDO_DEBUG && $errors)
			{
				foreach($errors as $error)
				{
					if(!$error)
					{
						continue;
					}

					echo(
						'<div class="infobox">' . PHP_EOL . 
						(!$utf8 ? $error : utf8_encode($error)) . PHP_EOL . 
						'</div>' . PHP_EOL . 
						'<br />'
						);
				}

				Registry::globals('errors', Array());
			}

			if($exception_sql)
			{
				echo(
					'<div class="infobox">' . PHP_EOL . 
					'<strong>SQL:</strong> <code>' . str_replace(Array("\r", "\n"), '', $e->getSQL()) . '</code>' . PHP_EOL . 
					'</div>' . PHP_EOL
					);
			}

			echo(
				'</div>' . PHP_EOL . 
				'<div style="clear: left;"></div>' . PHP_EOL . 
				'</div>' . PHP_EOL . 
				'</div>' . PHP_EOL
				);

			$bt = ($exception ? tuxxedo_debug_backtrace($e) : tuxxedo_debug_backtrace());

			if($bts = sizeof($bt))
			{
				echo(
					'<h2><span>Backtrace</span></h2>' . PHP_EOL . 
					'<div class="box edge-title">' . PHP_EOL . 
					'<div class="inner">' . PHP_EOL . 
					'<table width="100%" cellspacing="2" cellpadding="0">' . PHP_EOL . 
					'<tr class="head">' . PHP_EOL . 
					'<td>&nbsp;</td>' . PHP_EOL . 
					'<td class="strong">Call</td>' . PHP_EOL . 
					'<td class="strong">File</td>' . PHP_EOL . 
					'<td class="strong">Line</td>' . PHP_EOL . 
					'<td class="strong">Notes</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				foreach($bt as $n => $trace)
				{
					echo(
						'<tr class="' . ($trace->current ? 'strong ' : '') . 'row">' . PHP_EOL . 
						'<td align="center"><h3>' . ++$n . '</h3></td>' . PHP_EOL . 
						'<td nowrap="nowrap">' . $trace->call . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap" width="100%">' . $trace->file . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap" align="right">' . $trace->line . '</td>' . PHP_EOL . 
						'<td nowrap="nowrap">' . $trace->notes . '</td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);

					if($trace->current)
					{
						echo(
							'<tr class="strong row">' . PHP_EOL . 
							'<td class="empty"><h3>&nbsp;</h3></td>' . PHP_EOL . 
							'<td colspan="4">' . $trace->callargs . '</td>' . PHP_EOL . 
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

			if($registry && $registry->db && $registry->db->getNumQueries())
			{
				echo(
					'<h2><span>Queries</span></h2>' . PHP_EOL . 
					'<div class="box edge-title">' . PHP_EOL . 
					'<div class="inner">' . PHP_EOL . 
					'<table width="100%" cellspacing="2" cellpadding="0">' . PHP_EOL . 
					'<tr class="head">' . PHP_EOL . 
					'<td width="10">&nbsp;</td>' . PHP_EOL . 
					'<td class="strong">SQL</td>' . PHP_EOL . 
					'</tr>' . PHP_EOL
					);

				foreach($registry->db->getQueries() as $n => $sql)
				{
					echo(
						'<tr class="row">' . PHP_EOL . 
						'<td align="center"><h3>' . ++$n . '</h3></td>' . PHP_EOL . 
						'<td><code>' . $sql . '</code></td>' . PHP_EOL . 
						'</tr>' . PHP_EOL
						);
				}

				echo(
					'</table>' . PHP_EOL . 
					'</div>' . PHP_EOL . 
					'</div>' . PHP_EOL . 
					'<p>' . PHP_EOL . 
					'<em>' . 
					'Tuxxedo Engine &copy; 2006+ - Tuxxedo Software Development' . 
					'</em>' . PHP_EOL . 
					'</p>'
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
	 * @param	string				The error message, in a printf-alike formatted string or just a normal string
	 * @param	mixed				Optional argument #n for formatting
	 * @return	Void				No value is returned
	 */
	function tuxxedo_doc_errorf()
	{
		if(!func_num_args())
		{
			tuxxedo_doc_error('Unknown error');
		}

		tuxxedo_doc_error(call_user_func_array('sprintf', func_get_args()));
	}

	/**
	 * Trims a file path to hide its path prior to the root 
	 * of the application
	 *
	 * @param	string				The path to trim
	 * @param	boolean				Should the path also be trimmed if debug mode is on? Defaults to true
	 * @return	string				The trimmed path
	 */
	function tuxxedo_trim_path($path, $debug_trim = true)
	{
		static $dir;

		if(!$debug_trim && defined('TUXXEDO_DEBUG') && TUXXEDO_DEBUG)
		{
			return($path);
		}

		if(empty($path))
		{
			return('');
		}

		if(!$dir)
		{
			$dir = realpath(TUXXEDO_DIR);
		}

		if(strpos($path, '/') !== false || strpos($path, '\\') !== false || strpos($path, $dir) !== false)
		{
			$path = str_replace(Array('/', '\\', $dir), DIRECTORY_SEPARATOR, $path);
		}

		return(($path{1} != ':' && $path{2} != '\\' ? DIRECTORY_SEPARATOR : '') . ltrim($path, DIRECTORY_SEPARATOR));
	}

	/**
	 * Shutdown handler
	 *
	 * @return	void				No value is returned
	 */
	function tuxxedo_shutdown_handler()
	{
		$configuration	= Registry::getConfiguration();
		$output 	= (ob_get_length() ? ob_get_clean() : '');

		if($configuration['application']['debug'] && $output && substr(ltrim($output), 0, 11) == 'Fatal error')
		{
			$error = trim(substr_replace($output, '<strong>Fatal error</strong>', 0, 12));

			if(($spos = strpos($error, TUXXEDO_DIR)) !== false)
			{
				$error = substr_replace($error, tuxxedo_trim_path(substr($error, $spos, $epos = strrpos($error, ' on line') - $spos)), $spos, $epos);
			}

			tuxxedo_doc_error($error);
		}

		$errors = Registry::globals('errors');

		if(!defined('TUXXEDO_DEBUG') || !TUXXEDO_DEBUG || !$errors)
		{
			echo($output);

			return;
		}

		if(PHP_SAPI == 'cli')
		{
			$buffer = PHP_EOL . strip_tags(implode(PHP_EOL, $errors));
		}
		else
		{
			$buffer = '<br />' . implode('<br />', $errors);
		}

		if($pos = stripos($output, '</body>'))
		{
			$output = substr_replace($output, $buffer . '</body>', $pos, 7);
		}
		else
		{
			$output .= $buffer;
		}

		echo($output);
	}

	/**
	 * Handles multiple errors repeatingly
	 *
	 * @param	string				A sprintf-like format
	 * @param	array				An array with elements to loop through
	 * @param	string				A fully quantified exception name to throw
	 * @return	void				No value is returned
	 *
	 * @throws	mixed				Throws an exception until the errors have been cleared
	 */
	function tuxxedo_multi_error($format, Array $elements, $exception = '\Tuxxedo\Exception\Basic')
	{
		if(!$elements)
		{
			return;
		}

		throw new $exception($format, reset($elements));
	}

	/**
	 * Issues a redirect and terminates the script
	 *
	 * @param	string				The message to show to the user while redirecting
	 * @param	string				The redirect location
	 * @param	integer				Redirect timeout in seconds
	 * @return	void				No value is returned
	 */
	function tuxxedo_redirect($message, $location, $timeout = 3)
	{
		eval(page('redirect'));
		exit;
	}

	/**
	 * Issues a redirect using headers and then terminates the script
	 *
	 * @param	string				The redirect location
	 * @return	void				No value is returned
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
	 * @param	string				The error message
	 * @param	boolean				Whether to show the 'Go back' button or not
	 * @return	void				No value is returned
	 */
	function tuxxedo_error($message, $go_back = true)
	{
		eval(page('error'));
		exit;
	}

	/**
	 * Prints an error message using the current loaded 
	 * theme with an list of failed conditions which 
	 * makes it suitable for form data exceptions, this 
	 * function also terminates the script
	 *
	 * @param	string				The error message
	 * @param	array				The list of errors to display
	 * @param	boolean				Whether to show the 'Go back' button or not
	 * @return	void				No value is returned
	 */
	function tuxxedo_error_list($message, Array $formdata, $go_back = true)
	{
		$registry = Registry::init();

		if(!$registry->style)
		{
			return('');
		}

		$error_list = '';

		foreach($formdata as $error)
		{
			eval('$error_list .= "' . $registry->style->fetch('error_listbit') . '";');
		}

		eval(page('error'));
		exit;	
	}

	/**
	 * Date format function
	 *
	 * @param	integer				The timestamp to format
	 * @param	string				Optional format to use, defaults to the format defined within the options
	 * @return	string				Returns the formatted date
	 */
	function tuxxedo_date($timestamp = NULL, $format = NULL)
	{
		$registry = Registry::init();

		if(!$timestamp)
		{
			$timestamp = (defined('TIMENOW_UTC') ? TIMENOW_UTC : time());
		}

		if($format === NULL)
		{
			$format = $registry->datastore->options['date_format'];
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
	 * @param	string				The template name to print
	 * @return	void				No value is returned
	 */
	function page($template)
	{
		$registry = Registry::init();

		if(!$registry->style)
		{
			return('');
		}

		return(
			'global $header, $footer;' . 
			'echo("' . $registry->style->fetch($template) . '");'
			);
	}

	/**
	 * Wrapper function for printing a page content 
	 * from a variable. This function is mainly for 
	 * views that have layout mode activated.
	 *
	 * @param	string				The template contents to print
	 * @return	string				Returns a string for eval()'ing the content
	 */
	function page_print($content)
	{
		return('echo("' . $content . '");');
	}
?>