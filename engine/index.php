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
	 * @subpckage		Library
	 *
	 * =============================================================================
	 */


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Template\Layout;
	use Tuxxedo\Version;


	/**
	 * Precache templates
	 */
	$templates = [
			/* Index page */
			'index'
			];


	/**
	 * Bootstraper
	 */
	require('./library/bootstrap.php');

use Tuxxedo\Upload;

$u = new Upload;

if(isset($_POST['send']))
{
	$u->queue('post', 'fileselector1', 'image');
	$u->queue('url', $_POST['fileselector2'], 'image');

	$status = $u->upload();

	echo '<pre>';
	var_dump($status);
	echo '</pre>';
}

?>
<form enctype="multipart/form-data" action="<?php echo(htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES)); ?>" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo($u['size_limit']); ?>" />
    Send this file: <input name="fileselector1" type="file" /> <br />
    Send this file: <input name="fileselector2" type="text" /> <br />
    <input type="submit" name="send" value="Send File" />
</form>
<?php

//exit;
	/**
	 * Just print the engine version to show that
	 * the bootstraper was a success
	 */
	echo new Layout('index', ['version' => Version::FULL]);
?>