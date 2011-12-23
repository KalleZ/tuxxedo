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
	 * @package		DevTools
	 *
	 * =============================================================================
	 */


	/**
	 * Aliasing rules
	 */
	use DevTools\Style;
	use Tuxxedo\Input;
	use Tuxxedo\Registry;


	/**
	 * Widget hook function - styles
	 *
	 * @param	\Devtools\Style		The Devtools style object
	 * @param	\Tuxxedo\Registry	The registry reference
	 * @param	string			The template name of the widget
	 * @return	string			Returns the compiled sidebar widget
	 */
	function widget_hook_styles(Style $style, Registry $registry, $widget)
	{
		$style->cache(Array($widget));

		$buffer 	= '';
		$styleid	= $registry->input->get('style', Input::TYPE_NUMERIC);

		foreach($registry->datastore->styleinfo as $value => $info)
		{
			$name 		= $info['name'];
			$selected	= ($styleid == $value);

			eval('$buffer .= "' . $style->fetch('option') . '";');
		}

		$default 	= ($styleid == $registry->options->style_id);
		$valid		= isset($registry->datastore->styleinfo[$styleid]);

		eval('$buffer = "' . $style->fetch($widget) . '";');

		return($buffer);
	}

	/**
	 * Widget hook function - sessions
	 *
	 * @param	\Devtools\Style		The Devtools style object
	 * @param	\Tuxxedo\Registry	The registry reference
	 * @param	string			The template name of the widget
	 * @return	string			Returns the compiled sidebar widget
	 */
	function widget_hook_sessions(Style $style, Registry $registry, $widget)
	{
		$style->cache(Array($widget));

		$buffer		= '';
		$refresh_values = Array(
					0	=> 'Disabled', 
					5	=> '5 Seconds', 
					10	=> '10 Seconds', 
					15	=> '15 Seconds', 
					30	=> '30 Seconds', 
					60	=> '1 Minute'
					);

		if(isset($_POST['autorefresh']) && isset($refresh_values[$registry->input->post('autorefresh', Input::TYPE_NUMERIC)]))
		{
			$registry->cookie->set('__devtools_session_autorefresh', $registry->input->post('autorefresh', Input::TYPE_NUMERIC));
		}
		elseif(!isset($registry->cookie['__devtools_session_autorefresh']))
		{
			$registry->cookie->set('__devtools_session_autorefresh', 0);
		}

		foreach($refresh_values as $value => $name)
		{
			$selected = ($registry->cookie['__devtools_session_autorefresh'] == $value);

			eval('$buffer .= "' . $style->fetch('option') . '";');
		}

		$refresh_timer = $registry->cookie['__devtools_session_autorefresh'];

		eval('$sidebar = "' . $style->fetch($widget) . '";');

		return($sidebar);
	}
?>