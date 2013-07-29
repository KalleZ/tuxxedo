<?php
	/**
	 * Tuxxedo Software Engine
	 * =============================================================================
	 *
	 * @author		Christian Hoffbeck		<chhoffbeck@gmail.com>
	 * @version		1.0
	 * @copyright		Tuxxedo Software Development 2006+
	 * @license		Apache License, Version 2.0
	 * @package		Engine
	 *
	 * =============================================================================
	 */
	 
	namespace Tuxxedo;

	use Tuxxedo\Mvc\Model;
	use Tuxxedo\Mvc\View;
	
	
	abstract class Controller
	{
		/**
		 * Return data for the controller part of application.
		 * This way we are able to render the response as json, html or any other data set structure
		 * 
		 * @var 	Array	$returnData		The response data.
		 */ 
		protected $returnData	= [
						/**
						 * The view name
						 * 
						 * @var 	String	$returnData['view']
						 */
						'view'		=> 'default',
						
						/**
						 * The content type of the return application.
						 * 
						 * @var 	String $returnData['content']
						 */
						'content'	=> 'html',
						
						/**
						 * The data to use as the return data.
						 * 
						 * @var 	Array	$returnData['varName']
						 */
						'data'		=> [],
						
						/**
						 * The list of extra needed assets such as javascripts or css.
						 * 
						 * @var 	Array	$returnData['assetName']
						 */
						'assets'	=> [],
						
						/**
						 * The application return code.
						 * 
						 * @var 	Array	$returnData['status']
						 */
						'status'	=> [200,'OK'],

						/**
						 * The error explanation of the application
						 * 
						 * @var 	String	$returnData['error']
						 */
						'error'		=> ''
					];

		/**
		 * Return all response data within the array.
		 * 
		 * @return	Array			Response data array.
		 */
		protected function _getResponseData()
		{
			return($this->returnData);
		}

		/**
		 * Set content type of the application.
		 * 
		 * @param 	String $type		Type of application response.
		 * @return	Void
		 */
		protected function _setContentType($type)
		{
			$this->returnData['content']	= (String) $type;
		}

		/**
		 * Set view to load in to the application.
		 * 
		 * @param 	String	$view		Name of the view to load.
		 * @return 	Void
		 */
		protected function _setView($view)
		{
			$this->returnData['view']	= (String) $view;
		}

		/**
		 * Set http status code to return.
		 * 
		 * @param 	Integer $code		Http status code.
		 * @param 	String	$message	Message of the status code.
		 * @return	Void
		 */
		protected function _setStatus($code,$message)
		{
			$this->returnData['status'][0]	= (Integer) $code;
			$this->returnData['status'][1]	= (String) $message;
		}

		/**
		 * Set data for the view and template.
		 * If the $offset param has type of array and $value is type of boolean true,
		 * then is will merge the two array's together.
		 * 
		 * @param 	Mixed	$offset		Offset has to be either array or string.
		 * @param 	Mixed	$value		By default value is set to true.
		 */
		protected function _setData($offset,$value = true)
		{
			if((Boolean) $value === true && (Boolean) is_array($offset) === true)
			{
				$this->returnData['data']		= \array_merge($this->returnData['data'],$offset);
			}
			else
			{
				$this->returnData['data'][$offset]	= $value;
			}
		}

		/**
		 * Set costum error string.
		 * 
		 * @param 	String	$str		Error string.
		 * @return 	Void
		 */
		protected function _setError($str)
		{
			$this->returnData['error']	= (String) $str; 
		}
	}