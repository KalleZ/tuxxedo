<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christiana Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */

	 /**
	  * Alias rules
	  */
	  use Tuxxedo\Mvc\Url;
	
	/**
	 * Register url and request methods
	 */
	/*Url::get('test/[char]/[numeric]',array('alias' => 'test','ext' => '.html','user' => 'hasPermission','redirect' => 'route: 404'),function(){

	});

	Url::get('test',function(){
		
	});*/

	Url::Group('random/newbie/[numeric]',array('check' => 'userPermission|userLoggedIn'),function($opt){
		Url::post(function(){

		});

		Url::get(function(){

		});

		Url::put(Array('check' => 'apiKey'),function(){

		});

		Url::delete(function(){

		});
	});

	/*Url::get('random/newbie1/(numeric)',function(){
		echo 'er nu blevet super';
	});*/

	var_dump(Url::test());