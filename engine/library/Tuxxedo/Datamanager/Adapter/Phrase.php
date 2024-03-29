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
	 * @subpackage		Library
	 *
	 * =============================================================================
	 */


	/**
	 * Datamanagers adapter namespace, this contains all the different 
	 * datamanager handler implementations to comply with the standard 
	 * adapter interface, and with the plugins for hooks.
	 *
	 * @author		Kalle Sommer Nielsen	<kalle@tuxxedo.net>
	 * @author		Ross Masters 		<ross@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 */
	namespace Tuxxedo\Datamanager\Adapter;


	/**
	 * Aliasing rules
	 */
	use Tuxxedo\Datamanager\Adapter;
	use Tuxxedo\Datamanager\Hooks;
	use Tuxxedo\Exception;
	use Tuxxedo\Registry;

	/**
	 * Include check
	 */
	\defined('\TUXXEDO_LIBRARY') or exit;


	/**
	 * Datamanager for phrases
	 *
	 * @author		Kalle Sommer Nielsen <kalle@tuxxedo.net>
	 * @version		1.0
	 * @package		Engine
	 * @subpackage		Library
	 * @since		1.2.0
	 */
	class Phrase extends Adapter implements Hooks\Cache, Hooks\Resetable
	{
		/**
		 * Datamanager name
		 *
		 * @var		string
		 */
		const DM_NAME			= 'phrase';

		/**
		 * Identifier name for the datamanager
		 *
		 * @var		string
		 */
		const ID_NAME			= 'id';

		/**
		 * Table name for the datamanager
		 *
		 * @var		string
		 */
		const TABLE_NAME		= 'phrases';


		/**
		 * Fields for validation of phrases
		 *
		 * @var		array
		 */
		protected $fields		= [
							'id'			=> [
											'type'		=> parent::FIELD_PROTECTED, 
											'validation'	=> parent::VALIDATE_IDENTIFIER
											], 
							'title'			=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidPhraseTitle']
											], 
							'translation'		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_STRING
											], 
							'defaulttranslation'	=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_STRING
											],
							'changed'		=> [
											'type'		=> parent::FIELD_OPTIONAL, 
											'validation'	=> parent::VALIDATE_BOOLEAN, 
											'default'	=> false
											],  
							'languageid' 		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidLanguageId']
											], 
							'phrasegroup'		=> [
											'type'		=> parent::FIELD_REQUIRED, 
											'validation'	=> parent::VALIDATE_CALLBACK, 
											'callback'	=> [__CLASS__, 'isValidPhrasegroup']
											]
							];


		/**
		 * Constructor, fetches a new phrase based on its id if set
		 *
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The phrase id
		 * @param	integer				Additional options to apply on the datamanager
		 * @param	\Tuxxedo\Datamanager\Adapter	The parent datamanager if any
		 *
		 * @throws	\Tuxxedo\Exception\Basic	Throws an exception if the phrase id is set and it failed to load for some reason
		 * @throws	\Tuxxedo\Exception\SQL		Throws a SQL exception if a database call fails
		 */
		public function __construct(Registry $registry, $identifier = NULL, $options = parent::OPT_DEFAULT, Adapter $parent = NULL)
		{
			if($identifier !== NULL)
			{
				$phrase = $registry->db->query('
								SELECT 
									* 
								FROM 
									`' . \TUXXEDO_PREFIX . 'phrases` 
								WHERE 
									`id` = %d
								LIMIT 1', $identifier);

				if(!$phrase || !$phrase->getNumRows())
				{
					throw new Exception('Invalid phrase id passed to datamanager');
				}

				$this->data 		= $phrase->fetchAssoc();
				$this->identifier 	= $identifier;

				$phrase->free();
			}

			parent::init($registry, $options, $parent);
		}

		/**
		 * Checks whether a language id is valid or not
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	integer				The language id
		 * @return	boolean				True if the language exists, otherwise false
		 */
		public static function isValidLanguageId(Adapter $dm, Registry $registry, $languageid = NULL)
		{
			return($languageid === NULL || $registry->datastore->languages && isset($registry->datastore->languages[$languageid]));
		}

		/**
		 * Checks whether a title is valid or not for the phrase
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The phrase title
		 * @return	boolean				True if the phrase title is valid, otherwise false
		 */
		public static function isValidPhraseTitle(Adapter $dm, Registry $registry, $title = NULL)
		{
			$query = $registry->db->query('
							SELECT 
								`id`
							FROM 
								`' . \TUXXEDO_PREFIX . 'phrases` 
							WHERE 
									`languageid` = %d 
								AND 
									`title` = \'%s\' 
							LIMIT 1', $dm->data['languageid'], $registry->db->escape($title));

			return(!$query || !$query->getNumRows());
		}

		/**
		 * Checks whether a phrasegroup is valid or not for the phrase
		 *
		 * @param	\Tuxxedo\Datamanager\Adapter	The current datamanager adapter
		 * @param	\Tuxxedo\Registry		The Registry reference
		 * @param	string				The phrasegroup title
		 * @return	boolean				True if the phrase group is valid, otherwise false
		 */
		public static function isValidPhrasegroup(Adapter $dm, Registry $registry, $phrasegroup = NULL)
		{
			$query = $registry->db->query('
							SELECT 
								`id` 
							FROM 
								`' . \TUXXEDO_PREFIX . 'phrasegroups` 
							WHERE 
									`languageid` = %d 
								AND 
									`title` = \'%s\'', $dm->data['languageid'], $registry->db->escape($phrasegroup));

			return($query && $query->getNumRows());
		}

		/**
		 * Recaches the phrase statistics
		 *
		 * @return	boolean				Returns true if the datastore was updated with success, otherwise false
		 */
		public function rebuild()
		{
			if($this->context == parent::CONTEXT_DELETE || $this->context == parent::CONTEXT_SAVE)
			{
				$pid = $this->registry->db->query('
									SELECT 
										`id` 
									FROM 
										`' . \TUXXEDO_PREFIX . 'phrasegroups` 
									WHERE 
										`languageid` = %d', $this->data['languageid']);

				if(!$pid || !$pid->getNumRows())
				{
					return(false);
				}

				foreach($pid as $p)
				{
					Adapter::factory('phrasegroup', $p['id'])->save();
				}
			}

			return(true);
		}

		/**
		 * Resets the data to its default values while keeping the 
		 * identifier intact
		 *
		 * @return	boolean				Returns true on successful reset, otherwise false
		 */
		public function reset()
		{
			if(($this->options & parent::OPT_LOAD_ONLY) || $this->identifier === NULL)
			{
				return(false);
			}

			$this->data['changed']		= false;
			$this->data['translation'] 	= $this->data['defaulttranslation'];

			return($this->save());
		}
	}
?>