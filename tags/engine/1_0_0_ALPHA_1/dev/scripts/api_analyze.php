<?php
	$engine_path	= realpath(__DIR__ . '/../../library/');
	$files 		= analyze(new DirectoryIterator($engine_path), Array('database', 'datamanagers'));
	$datamap	= Array();

	echo('<h1>Lexical analyze of engine API</h1>');

	foreach($files as $real_file)
	{
		$file = substr(str_replace($engine_path, '', $real_file), 1);

		printf('<h3>%s</h3>', $file);

		$context 		= new stdClass;
		$context->current 	= false;

		$datamap[$file]		= Array(
						'classes'	=> Array(), 
						'interfaces'	=> Array(), 
						'constants'	=> Array(), 
						'functions'	=> Array()
						);

		$tokens			= $tokens_copy = token_get_all(file_get_contents($real_file));

		foreach($tokens as $index => $token)
		{
			if(!is_array($token))
			{
				$token = Array(0, $token);
			}

			switch($token[0])
			{
				case(T_INTERFACE):
				case(T_CLASS):
				{
					if(($name = lexical_scan($tokens_copy, $index, T_STRING)) == false)
					{
						continue;
					}

					$type					= ($token[0] == T_CLASS ? 'class' : 'interface');
					$type_multiple				= ($token[0] == T_CLASS ? 'classes' : 'interfaces');

					$context->current 			= $token[0];
					$context->type				= $type;
					$context->type_multiple			= $type_multiple;
					$context->{$type}			= $name;

					$datamap[$file][$type_multiple][$name]	= Array(
											'constants'	=> Array(), 
											'properties'	=> Array(), 
											'methods'	=> Array()
											);

					printf('%s (%s)<br />', strtoupper($type), $name);
				}
				break;
				case(T_FUNCTION):
				{
					if(($function = lexical_scan($tokens_copy, $index, T_STRING)) == false)
					{
						continue;
					}

					if($context->current == T_CLASS || $context->current == T_INTERFACE)
					{
						$datamap[$file][$context->type_multiple][$context->{$context->type}]['methods'][] = $function;

						printf('- METHOD (%s)<br />', $function);
					}
					else
					{
						$datamap[$file]['functions'][] = $function;

						printf('FUNCTION (%s)<br />', $function);
					}
				}
				break;
				case(T_STRING):
				{
					if($context->current !== false)
					{
						continue;
					}

					if(strtolower($token[1]) == 'define')
					{
						if(($const = lexical_scan($tokens_copy, $index, T_CONSTANT_ENCAPSED_STRING)) == false)
						{
							continue;
						}

						$datamap[$file]['constants'][] = $const = substr($const, 1, strlen($const) - 2);

						printf('GLOBAL CONSTANT (%s)<br />', $const);
					}
				}
				break;
				case(T_CONST):
				{
					if(($const = lexical_scan($tokens_copy, $index, T_STRING)) == false)
					{
						continue;
					}

					if($context->current !== false)
					{
						$datamap[$file][$context->type_multiple][$context->{$context->type}]['constants'][] = $const;

						printf('- CONSTANT (%s)<br />', $const);
					}
					else
					{
						$datamap[$file]['constants'][] = $const;

						printf('GLOBAL CONSTANT (%s)<br />', $const);
					}
				}
				break;
				case(T_VARIABLE):
				{
					if($context->current === false || sizeof($datamap[$file][$context->type_multiple][$context->{$context->type}]['methods']))
					{
						continue;
					}

					$property 										= substr($token[1], 1);
					$datamap[$file][$context->type_multiple][$context->{$context->type}]['properties'][]	= $property;

					printf('- PROPERTY (%s)<br />', $property);
				}
				break;
			}
		}
	}

	file_put_contents(__DIR__ . '/../api/dump.serialized', serialize($datamap));


	function analyze(DirectoryIterator $iterator, $scan_dirs = Array())
	{
		$files = $extra = Array();

		$iterator->rewind();

		foreach($iterator as $entry)
		{
			if($entry->isDot())
			{
				continue;
			}

			if($entry->isDir())
			{
				if(!in_array(strtolower($entry->getBaseName()), $scan_dirs))
				{
					continue;
				}

				foreach(new DirectoryIterator($entry->getPathName()) as $sub)
				{
					if(!$sub->isFile() || strtolower(pathinfo($path = $sub->getPathName(), PATHINFO_EXTENSION)) != 'php')
					{
						continue;
					}

					$extra[] = realpath($path);
				}
			}
			elseif(strtolower(pathinfo($path = $entry->getPathName(), PATHINFO_EXTENSION)) == 'php')
			{
				$files[] = realpath($path);
			}
		}

		$files = array_merge($files, $extra);

		return($files);
	}

	function lexical_scan(Array $tokens, $start_index, $token)
	{
		$inc			= 0;
		$searching_for_token 	= is_numeric($token);

		while(isset($tokens[$start_index + $inc++]))
		{
			$t = $tokens[$start_index + $inc - 1];

			if(is_array($t) && $searching_for_token && $t[0] === $token)
			{
				return($t[1]);
			}
			elseif($t === $token)
			{
				return($start_index + $inc);
			}
		}

		return(false);
	}
?>