<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Kalle Sommer Nielsen 	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @package		Engine
	 *
	 * =============================================================================
	 */


    require('./library/bootstrap_ns.php');

    // echo$header;
	echo('Tuxxedo Engine version: ' . Tuxxedo\Version::FULL . (TUXXEDO_DEBUG ? ' (DEBUG)' : ''));
	// echo $footer;
