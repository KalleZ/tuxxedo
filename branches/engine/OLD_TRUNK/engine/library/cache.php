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

	defined('TUXXEDO') or exit;


	/**
	 * Datastore cache, this enables datastore caching for 
	 * databases. This assumes the datastore table and 
	 * everything else required for a database based 
	 * datastore is setup.
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 */
	class Tuxxedo_Datastore
	{
		/**
		 * Private instance to the Tuxxedo registry
		 *
		 * @var		Tuxxedo
		 */
		protected $tuxxedo;

		/**
		 * Holds the cached elements from the datastore
		 *
		 * @var		array
		 */
		protected $cache	= Array();


		/**
		 * Constructor
		 */
		public function __construct()
		{
			global $tuxxedo;

			$this->tuxxedo = $tuxxedo;
		}

		/**
		 * Quick reference for overloading of a loaded 
		 * element in the datastore.
		 *
		 * @param	string			The datastore element to load
		 * @return	array			An array is returned, otherwise boolean false on error
		 */
		public function __get($name)
		{
			if(isset($this->cache[$name]))
			{
				return($this->cache[$name]);
			}

			return(false);
		}

		/**
		 * Frees a datastore from the loaded cache
		 *
		 * @param	string			The datastore element to free from cache
		 * @return	void			No value is returned
		 */
		public function free($name)
		{
			if(isset($this->cache[$name]))
			{
				unset($this->cache[$name]);
			}
		}

		/**
		 * Rebuilds a datastore element if it already exists, or adds 
		 * a new entry in the datastore if no elements with that name 
		 * already exists. To delete a datastore element completely,  
		 * the data parameter must be set to NULL. If the delay 
		 * parameter is set to true, then the current cached data 
		 * will not be updated with the new data.
		 *
		 * @param	string			The datastore element
		 * @param	mixed			This can be either an array or object, if this is NULL then the datastore is deleted completely
		 * @param	boolean			Should this action be delayed until shutdown? (Defaults to true)
		 * @return	boolean			True on success, otherwise false on error
		 *
		 * @throws	Tuxxedo_Exception	Throws an exception if the query should fail (only if the delay parameter is set to false)
		 */
		public function rebuild($name, Array $data = NULL, $delay = true)
		{
			if($data === NULL)
			{
				$sql = sprintf('
						DELETE FROM 
							`' . TUXXEDO_PREFIX . 'datastore` 
						WHERE 
							`name` = \'%s\';', $this->tuxxedo->db->escape($name));
			}
			else
			{
				$sql = sprintf('
						REPLACE INTO 
							`' . TUXXEDO_PREFIX . 'datastore` 
							(
								`name`, 
								`data`
							) 
						VALUES 
							(
								\'%s\', 
								\'%s\'
							);', $this->tuxxedo->db->escape($name), $this->tuxxedo->db->escape(serialize($data)));
			}

			if($delay)
			{
				$this->tuxxedo->db->setShutdownQuery($sql);

				return(true);
			}

			$retval = $this->tuxxedo->db->query($sql);

			if($retval)
			{
				if($data === NULL)
				{
					unset($this->cache[$name]);
				}
				else
				{
					$this->cache[$name] = $data;
				}
			}

			return($retval);
		}

		/**
		 * Caches a set of elements from the datastore into 
		 * the current cache.
		 *
		 * @param	array			An array, where the values are the datastore element names
		 * @param	array			An array passed by reference, if one or more elements should happen not to be loaded, then this array will contain the names of those elements
		 * @return	boolean			True on success, otherwise false
		 *
		 * @throws	Tuxxedo_Exception	Throws an exception if the query should fail
		 */
		public function cache(Array $elements, Array &$error_buffer = NULL)
		{
			if(!sizeof($elements))
			{
				return(false);
			}

			$result = $this->tuxxedo->db->query('
								SELECT 
									`name`, 
									`data` 
								FROM 
									`' . TUXXEDO_PREFIX . 'datastore` 
								WHERE 
									`name` 
									IN
									(
										\'%s\'
									);', join('\', \'', array_map(Array($this->tuxxedo->db, 'escape'), $elements)));

			if($result === false)
			{
				if($error_buffer !== NULL)
				{
					$error_buffer = $elements;
				}

				return(false);
			}

			$loaded = Array();

			while($row = $result->fetchAssoc())
			{
				$row['data'] = @unserialize($row['data']);

				if($row['data'] !== false)
				{
					$loaded[] 			= $row['name'];
					$this->cache[$row['name']] 	= $row['data'];
				}
			}

			if($error_buffer !== NULL)
			{
				$diff = array_diff($elements, $loaded);

				if(sizeof($diff))
				{
					$error_buffer = $diff;
				}

				return(false);
			}

			return(true);
		}
	}
?>