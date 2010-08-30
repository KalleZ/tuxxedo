<?php
	/**
	 * Tuxxedo Documentation
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @package		Documentation
	 *
	 * =============================================================================
	 */


	/**
	 * Header
	 */
	section('Tuxxedo Documentation Configurator');
	echo(PHP_EOL);

	/**
	 * Except to always run in CLI
	 */
	if(PHP_SAPI != 'cli')
	{
		error('This script is meant to be executed in CLI mode');
	}

	/**
	 * Parse the arguments and declare the defaults
	 */
	$options = Array(
				'language'	=> 'en'
				);

	foreach($argv as $n => $arg)
	{
		if($n < 1)
		{
			continue;
		}

		if($arg{0} != '-' && $arg{1} != '-' || ($pos = strpos($arg, '=', 4)) === false)
		{
			error('Invalid argument, must be in the format of --option=value');
		}

		$opt 	= strtolower(substr($arg, 2, $pos - 2));
		$value	= strtolower(substr($arg, $pos + 1));

		if(!isset($options[$opt]))
		{
			error('Invalid option');
		}

		if(empty($value) || $value == $options[$opt])
		{
			continue;
		}

		$options[$opt] = $value;
	}

	/**
	 * Options parsed, show the configuration before we 
	 * continue
	 */

	section('Configuration');
	msg_config('Language', $options['language']);
	msg_config('LibXML Version', LIBXML_DOTTED_VERSION);

	/**
	 * Check configuration and validate it
	 */
	section('Checking and validating configuration');

	/**
	 * - Language directory
	 */
	msg_checking('language');

	define('TRANSLATION_DIRECTORY', './translations/' . $options['language'] . '/');

	if(!is_dir(TRANSLATION_DIRECTORY))
	{
		msg_result_failed('Translation directory does not exists');
	}

	msg_result_ok();

	/**
	 * - Core language/translation files
	 */
	msg_validating('language');

	foreach(Array('preface', 'translation', 'translators') as $xml_file)
	{
		if(!is_file(TRANSLATION_DIRECTORY . $xml_file . '.xml'))
		{
			msg_result_failed('Unable to find required translation file (' . $xml_file . '.xml)');
		}
	}


	foreach(Array('bookinfo') as $xml_file)
	{
		if(($tmp = is_file(TRANSLATION_DIRECTORY . $xml_file . '.xml')) !== false && $options['language'] != 'en')
		{
			msg_result_failed('Translation contains a file thats not meant to be translated (' . $xml_file . '.xml)');
		}
		elseif($options['language'] == 'en' && !$tmp)
		{
			msg_result_failed('Translation contains a file thats not meant to be translated (' . $xml_file . '.xml)');
		}
	}

	$english_files = $translation_files = recursive_glob('./translations/en/', Array('xml', 'ent'));

	if($options['language'] != 'en')
	{
		$translation_files = recursive_glob(TRANSLATION_DIRECTORY, Array('xml', 'ent'));

		if(($diff = array_diff($english_files, $translation_files)) !== false && sizeof($diff))
		{
			$first_file = current($diff);

			msg_result_failed('Translation contains a file thats not within the english translation (' . $first_file . ')');
		}
		elseif(($diff = array_diff($translation_files, $english_files)) !== false && sizeof($diff))
		{
			$translation_files = array_merge($translation_files, $diff);
		}
	}

	msg_result_ok();


	function recursive_glob($path, Array $extensions)
	{
		if(!$extensions || !sizeof($extensions))
		{
			return(Array());
		}

		if($path{strlen($path) - 1} != '/' && $path{strlen($path) - 1} != '\\')
		{
			$path .= DIRECTORY_SEPARATOR;
		}

		$glob = glob($path . '*');

		if(!sizeof($glob))
		{
			return(Array());
		}

		$retval = Array();

		foreach($glob as $item)
		{
			if(is_dir($item))
			{
				$retval = array_merge($retval, recursive_dir($item, $extensions));
			}
			else
			{
				$ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

				if(in_array($ext, $extensions))
				{
					$retval[] = realpath($item);
				}
			}
		}

		return($retval);
	}

	function section($name)
	{
		echo(
			PHP_EOL . 
			' ' . $name . PHP_EOL . 
			' ' . str_repeat('-', strlen($name)) . PHP_EOL
			);
	}

	function msg_config($setting, $value)
	{
		echo(str_pad(' ' . $setting . ':', 45));
		msg_result($value);
	}


	function msg_checking($task)
	{
		echo(str_pad(' Checking ' . $task . '...', 45));
	}

	function msg_validating($task)
	{
		echo(str_pad(' Validating ' . $task . '...', 45));
	}

	function msg_result($result)
	{
		echo($result . PHP_EOL);
	}

	function msg_result_ok()
	{
		echo('[ OK ]' . PHP_EOL);
	}

	function msg_result_failed($reason)
	{
		echo('[ FAILED ]' . PHP_EOL);

		error($reason);
	}

	function error($reason)
	{
		echo(
			PHP_EOL . 
			PHP_EOL . 
			' Error, something went wrong: ' . PHP_EOL . 
			' ----------------------------' . PHP_EOL . 
			' ' . $reason . 
			PHP_EOL
			);

		exit;
	}
?>