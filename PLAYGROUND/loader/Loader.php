<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christian Hoffbeck		<chhoffbeck@gmail.com>
	 * @author		Kalle Sommer Nielsen		<kalle@php.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 * @subpackage		Loader
	 * 
	 *
	 * =============================================================================
	 */

	 /**
	  * Tuxxedo namespace
	  */
	 namespace Tuxxedo;

	 /**
	  * Default loader for engine, this will act as a single point of entry for all bundles.
	  * Any bundles will register themselves or have to be manually registered within this loader.
	  * 
	  * <code>
	  * 	$loader	= new \Tuxxedo\Loader();
	  * 	$loader->register('Tuxxedo\','library/Engine');
	  * </code>
	  * 
	  * @author		Christian Hoffbeck
	  * @author		Kalle Sommer Nielsen
	  * @version		1.0
	  */
	 class Loader
	 {
	 	/**
	 	 * A list of registered namespace routes.
	 	 * All routes is saved in the format of:
	 	 * namespace => route
	 	 * 
	 	 * @var		Array
	 	 */
	 	private $namespaces	= [];
		
		/**
	 	 * The root path of the application.
	 	 * This will be used for all non-strict saved namespaces:
	 	 * 
	 	 * @var		String
	 	 */
	 	private $root;

	 	/**
	 	 * Initialize the loader and register the instance as the autoloader autoloader.
	 	 * 
	 	 * @param	Array		A list of namespace routes to inject into the autoloader.
	 	 */ 
	 	public function __construct(Array $namespaces = NULL)
	 	{
	 		spl_autoload_register([$this,'load']);
			$this->root	=	$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
	 		if($namespaces)
	 		{
	 			$this->namespaces	= $namespaces;
	 		}
	 	}

	 	/**
	 	 * Register a new namespace and folder path.
	 	 * 
	 	 * @param	String		Namespace to register.
	 	 * @param	String		Folder path to the namespace.
		 * $param	Boolean		Strict type, this will ensure that only the registered path is used.
	 	 * @return	void
	 	 */
	 	public function register($namespace,$path,$strict=false)
	 	{
	 		$this->namespaces[$namespace]	= str_replace(['\\','/'],DS,($strict === false ? $this->root . $path : $path));
	 	}

	 	/**
	 	 * Load class, based on the registered namespace.
	 	 * If no namespace has been registered, 
	 	 * then the loader will try and use a normlaized path.
	 	 * 
	 	 * @param	String		Class name.
	 	 * @return	Boolean		Returns true if the class is found, otherwise false.
	 	 */
	 	public function load($class)
	 	{	
	 		$len	= \strlen(\strrchr($class,'\\'));
	 		$file	= \substr($class,-$len+1);
	 		$class 	= \substr($class,0,-$len);
			
			
	 		
	 		if(isset($this->namespaces[$class]))
	 		{
	 			$path	= \str_replace(['\\','//'],DIRECTORY_SEPARATOR,$this->namespaces[$class]) . \DIRECTORY_SEPARATOR;
				$file	= $path	. $file . '.php';
	 			if(\file_exists($file))
	 			{
	 				require $file;
	 				return(true);
	 			}
	 			return(false);
	 		}

	 		$namespace	= $class;
	 		$count		= \substr_count($namespace,'\\');
	 		$i		= 0;
	 		$found		= false;
	 		while($i != $count)
	 		{
	 			$namespace	= \substr($namespace,0,-\strlen(\strrchr($namespace,'\\')));
	 			if(isset($this->namespaces[$namespace]))
	 			{
	 				$found	= true;
	 				break;
	 			}
	 			++$i;
	 		}
			
	 		if($found === true)
	 		{
	 			$file	= \str_replace(['\\','//'],'/',\str_replace($namespace,$this->namespaces[$namespace],$class) . \DIRECTORY_SEPARATOR . $file . '.php');
				
				if(\file_exists($file))
				{
					require $file;
					return(true);
				}
	 		}
	 		
	 		return(false);
	 	}
	 }