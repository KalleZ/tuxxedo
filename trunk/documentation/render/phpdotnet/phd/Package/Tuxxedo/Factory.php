<?php
	/**
	 * Tuxxedo Software Documentation
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen 	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 * @subpckage		Documentation
	 *
	 * =============================================================================
	 */


	/**
	 * Global PhD namespace -- Tuxxedo extension, this code is based 
	 * on the default included packages within PhD.
	 *
	 * All original authors are credited within all the documentation 
	 * rendering code here from where it was base on.
	 *
	 * @author		Kalle Sommer Nielsen			<kalle@tuxxedo.net>
	 * @author		Ross Masters 				<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Documentation
	 */
	namespace phpdotnet\phd;


	/**
	 * Package factory class
	 *
	 * @author		Kalle Sommer Nielsen			<kalle@tuxxedo.net>
	 * @author		Ross Masters 				<ross@tuxxedo.net>
	 * @author		Moacir de Oliveira Miranda Júnior	<moacir@php.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Documentation
	 */
	class Package_Tuxxedo_Factory extends Format_Factory
	{
		/**
		 * Package formats
		 *
		 * @var		array
		 */
		protected $formats	= Array(
						'xhtml'		=>	'Package_Tuxxedo_ChunkedXHTML', 
						'web'		=>	'Package_Tuxxedo_Web', 
						'chm'		=>	'Package_Tuxxedo_CHM'
						);


		/**
		 * Factory constructor, declares the Tuxxedo package 
		 * namespace
		 */
		public function __construct()
		{
			parent::setPackageName('Tuxxedo');
			parent::registerOutputFormats($this->formats);
		}
	}
?>