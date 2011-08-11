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
	 * @subpackage		DevTools
	 *
	 * =============================================================================
	 */


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Registry;


	/**
	 * Set the debug mode constant
	 *
	 * @var		boolean
	 */
	define('TUXXEDO_DEBUG', 	true);

	/**
	 * Sets the path to where the root script is, if the 
	 * constant CWD is defined before including this file, 
	 * then it will be used as root dir
	 *
	 * @var		string
	 */
	define('TUXXEDO_DIR', 		realpath(__DIR__ . '/../../..'));

	/**
	 * Sets the library path
	 *
	 * @var		string
	 */
	define('TUXXEDO_LIBRARY', 	realpath(__DIR__ . '/../../..') . '/library');

	require(TUXXEDO_LIBRARY . '/configuration.php');
	require(TUXXEDO_LIBRARY . '/Tuxxedo/Loader.php');
	require(TUXXEDO_LIBRARY . '/Tuxxedo/functions.php');
	require(TUXXEDO_LIBRARY . '/Tuxxedo/functions_debug.php');

	date_default_timezone_set('UTC');

	set_error_handler('tuxxedo_error_handler');
	set_exception_handler('tuxxedo_exception_handler');
	register_shutdown_function('tuxxedo_shutdown_handler');
	spl_autoload_register('\Tuxxedo\Loader::load');

	Registry::globals('error_reporting', 	true);
	Registry::globals('errors', 		Array());

	$registry = Registry::init($configuration);

	$registry->set('timezone', new DateTimeZone('UTC'));
	$registry->set('datetime', new DateTime('now', $timezone));

	/**
	 * Current time constant
	 *
	 * @var		integer
	 */
	define('TIMENOW', $datetime->getTimestamp());

	/**
	 * Set the UTC time constant
	 *
	 * @var		integer
	 */
	define('TIMENOW_UTC', TIMENOW);

	/**
	 * Set database table prefix constant
	 *
	 * @var		string
	 */
	define('TUXXEDO_PREFIX', $configuration['database']['prefix']);
?>