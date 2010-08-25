<?php
	/**
	 * TODO:
	 *
	 *  - Generate constant list
	 *  - Generate function list
	 *  - Integrate namespaces
	 *  - Fix file paths to work correct, as dash seems to be special
	 */


	is_file('./dumps/serialized.dump') or die('The \'serialized.dump\' file was not found');

	if(!is_dir('./output/'))
	{
		mkdir('./output/') or die('Unable to make output directory');
	}

	if(!is_dir('./output/google_wiki/'))
	{
		mkdir('./output/google_wiki/') or die('Unable to make google wiki output directory');
	}

	define('FLAG_CLASS',		1);
	define('FLAG_INTERFACE', 	2);
	define('FLAG_CONSTANT', 	3);
	define('FLAG_PROPERTY',		4);
	define('FLAG_METHOD', 		5);
	define('FLAG_FUNCTION', 	6);

	$global_context = Array(
				'constants'	=> Array(), 
				'functions'	=> Array()
				);

	foreach($datamap = unserialize(file_get_contents('./dumps/serialized.dump')) as $file => $api)
	{
		foreach(Array('interfaces', 'classes') as $type)
		{
			if(sizeof($api[$type]))
			{
				foreach($api[$type] as $name => $info)
				{
					generate_class_or_interface($file, $name, $info, ($type == 'interfaces' ? FLAG_INTERFACE : FLAG_CLASS));
				}
			}
		}

		foreach(Array('constants', 'functions') as $type)
		{
			if(sizeof($api[$type]))
			{
				$global_context[$type] = array_merge($global_context[$type], $api[$type]);
			}
		}
	}

	generate_global_context($global_context);
	generate_toc($datamap, $global_context);

	function generate_class_or_interface($file, $name, $api, $type)
	{
		$type_name = ($type == FLAG_INTERFACE ? 'interface' : 'class');

		printf('Generating API for %s: %s<br />', strtolower($type_name), $name);

		$skel = new Skeleton('class_interface');

		$skel->replace('type', ucwords($type_name));
		$skel->replace('name', $name);
		$skel->replace('file', $file);
		$skel->replace('toc', 'api_engine');

		$skel->replace('constants', generate_members($name, $api, FLAG_CONSTANT));
		$skel->replace('properties', generate_members($name, $api, FLAG_PROPERTY));
		$skel->replace('methods', generate_members($name, $api, FLAG_METHOD, $type));

		$skel->save(canonical_name($name, $type_name));
	}

	function generate_members($parent, $api, $flag, $parent_type = NULL)
	{
		static $member_types, $member_skeletons;

		if(!$member_types)
		{
			$member_types 		= Array(
							FLAG_CONSTANT	=> 'constants', 
							FLAG_PROPERTY	=> 'properties', 
							FLAG_METHOD	=> 'methods'
							);

			$member_skeletons	= Array(
							FLAG_CONSTANT	=> 'constant', 
							FLAG_PROPERTY	=> 'property', 
							FLAG_METHOD	=> 'method'
							);
		}

		if(($num = sizeof($api[$member_types[$flag]])) === 0)
		{
			return('');
		}

		printf('- Generating %s files, total: %d<br />', $member_types[$flag], sizeof($api[$member_types[$flag]]));

		$data = '';

		foreach($api[$member_types[$flag]] as $member)
		{
			$skel = new Skeleton($member_skeletons[$flag]);

			switch($flag)
			{
				case(FLAG_CONSTANT):
				{
					$skel->replace('constant', $member);
				}
				break;
				case(FLAG_PROPERTY):
				{
					$skel->replace('property', $member);
				}
				break;
				case(FLAG_METHOD):
				{
					$skel->replace('method', $parent . '::' . $member . '()');
					$skel->replace('method_canonical_name', canonical_name($parent . '--' . $member));

					generate_function_or_method(NULL, $member, FLAG_METHOD, $parent, $parent_type);
				}
				break;
			}

			$data .= $skel->get();
		}

		$skel = new Skeleton($member_types[$flag]);
		$skel->replace('members', $data);

		return($skel->get());
	}

	function generate_function_or_method($file, $name, $type, $parent = NULL, $parent_type = NULL)
	{
		if($type == FLAG_FUNCTION)
		{
			printf('Generating API for global function: %s<br />', $name);

			$skel = new Skeleton('function');
			$skel->replace('file', $file);
		}
		else
		{
			printf('-- Generating method file: %s<br />', $name);

			$skel = new Skeleton('class_interface_method');
			$skel->replace('class', $parent);
			$skel->replace('class_canonical_name', canonical_name($parent, ($parent_type == FLAG_INTERFACE ? 'interface' : 'class')));
			$skel->replace('method_canonical_name', $parent . '::' . $name);
			$skel->replace('name', $name);

			$name = canonical_name($parent . '--' . $name);
		}

		$skel->replace('toc', 'api_engine');
		$skel->save($name);
	}

	function generate_global_context($global_context)
	{
echo 'generating global context elements<br />';
	}

	function generate_toc($datamap, $global_context)
	{
		printf('Generating table of contents');

		$toc		= new Skeleton('toc');
		$globals 	= new Skeleton('toc_globals');

		$globals->replace('title', '');

		foreach(Array('constants', 'functions') as $element)
		{
			if(sizeof($global_context[$element]))
			{
				$matched	= true;
				$item_skel 	= new Skeleton('toc_globals_item');

				$item_skel->replace('title', $element);
				$item_skel->replace('title_canonical_name', canonical_name($element));
			}

			$globals->replace($element, (isset($item_skel) ? $item_skel->get() : ''));

			unset($item_skel);
		}

		$toc->replace('globals', (isset($matched) ? $globals->get() : ''));

		foreach(Array('interfaces', 'classes') as $element)
		{
			${$element} = Array();

			foreach($datamap as $file => $objects)
			{
				if(!sizeof($objects[$element]))
				{
					continue;
				}

				foreach($objects[$element] as $name => $object)
				{
					${$element}[$name] = $object;
				}
			}

			if(!sizeof(${$element}))
			{
				$toc->replace($element, '');
			}

			ksort(${$element});

			$category 		= new Skeleton('toc_classes_interfaces');
			$category_contents	= '';

			foreach(${$element} as $name => $contents)
			{
				$item_skel = new Skeleton('toc_classes_interfaces_item');

				$item_skel->replace('title', $name);
				$item_skel->replace('title_canonical_name', canonical_name($name, ($element == 'interfaces' ? 'interface' : 'class')));

				$category_contents .= $item_skel->get();
			}

			$category->replace('type', ucwords($element));


			$category->replace('items', $category_contents);

			$toc->replace($element, $category->get());
		}

		$toc->save('api_engine');
	}

	function canonical_name($name, $prefix = '')
	{
		return('api_engine_' . (!empty($prefix) ? $prefix . '_' : '') . strtolower($name));
	}


	class Skeleton
	{
		protected $contents;

		protected $replacements		= Array();


		public function __construct($skeleton_name)
		{
			$this->contents = file_get_contents('./skeletons/google_wiki/' . $skeleton_name . '.skel');
		}

		public function replace($what, $with)
		{
			$this->replacements['{TUXX::' . strtoupper($what) . '}'] = $with;
		}

		public function get()
		{
			return(str_replace(array_keys($this->replacements), array_values($this->replacements), $this->contents));
		}

		public function save($file)
		{
			file_put_contents('./output/google_wiki/' . $file . '.wiki', $this->get());
		}
	}
?>