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
	Url::get('test/{=charater}/{=numeric}',['alias' => 'test','ext' => '.html','user' => 'hasPermission','redirect' => 'route: 404'],'{1}:action',function(){
		echo 'asd eho lol';
	});

	//Url::get('test','test:index');

	/*Url::Group('random/newbie/{=numeric}',array('check' => 'userPermission|userLoggedIn'),function($opt){
		Url::post(['alias' => 'test','ext' => '.html','user' => 'hasPermission','redirect' => 'route: 404'],'controller:action',function(){

		});

		Url::get(function(){

		});

		Url::put(['check' => 'apiKey'],function(){

		});

		Url::delete(function(){

		});
	});

	Url::get('random/newbie1/{=numeric}',function(){
		echo 'er nu blevet super';
	});*/
	
	Url::loadRoute('options');