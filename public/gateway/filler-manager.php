<?php

namespace gokabam_api;


require_once PLUGIN_PATH . 'public/gateway/api-typedefs.php';
require_once PLUGIN_PATH . 'lib/ErrorLogger.php';

class FillException extends \Exception {}

class FillerManager {

	/**
	 * @var GKA_Everything $everything
	 */
	protected $everything = null;

	/**
	 * @var MYDB $mydb
	 */
	protected $mydb = null;

	/**
	 * @var KidTalk $kid_talk
	 */
	protected $kid_talk = null;



	/**
	 * @var GKA_Root[] $processed_roots
	 *  all fillers add everything created to this, the fill function adds its argument to this
	 */
	protected $processed_roots = [];


	/**
	 * @var array $map_of_processed_roots - hash of what has been filled, with the string kid as the key
	 */
	protected $map_of_processed_roots = [];


	/**
	 * @var null|integer $start_ts
	 */
	protected $start_ts = null;

	/**
	 * @var null|integer $end_ts
	 */
	protected $end_ts = null;
	/**
	 * FillerManager constructor.
	 *
	 * @param GoKabamGoodies $GokabamGoodies
	 * @param integer $start_ts
	 * @param integer $end_ts
	 */
	public  function __construct($GokabamGoodies,$start_ts,$end_ts) {

		$this->kid_talk = $GokabamGoodies->get_kid_talk();
		$this->mydb = $GokabamGoodies->get_mydb();
		$this->start_ts = $start_ts;
		$this->end_ts = $end_ts;
		$this->everything = new GKA_Everything();
	}

	/**
	 * @param boolean $b_show_deleted default false , if true fills in deleted_kids
	 * @return GKA_Everything
	 * @throws ApiParseException
	 * @throws SQLException
	 * @throws FillException
	 */
	public function get_everything($b_show_deleted = false) {
		$this->finalize_processed_roots();
		if ($b_show_deleted) {
			$this->everything->deleted_kids = $this->get_deleted_array();
		}
		return $this->everything;
	}

	/**
	 * clears out everything and processed roots
	 * queries the database for all the top level objects (version, api_version, data groups that are tables
	 * and calls fill for each id and table name put into a kid
	 * @throws SQLException
	 * @throws FillException
	 * @throws ApiParseException
	 */
	public function do_all() {

		$first_ts_param = $this->start_ts;
		$last_ts_param = $this->end_ts;

		if (is_null($first_ts_param)) {
			$first_ts_param = 0;
		}

		if (is_null($last_ts_param)) {
			$last_ts_param = 999999999999999;
		}

		$roots = [];

		//get versions
		$res = $this->mydb->execSQL("
			SELECT 
				o.id,o.object_id
			FROM gokabam_api_versions o
			INNER JOIN gokabam_api_page_loads p_last ON p_last.id = o.last_page_load_id
			
			WHERE o.is_deleted = 0  AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['ii',$first_ts_param,$last_ts_param],
			MYDB::RESULT_SET,
			"@sey@versions.do_all.filler-manager.php"
		);

		if (empty($res))  {$res = [];}
		foreach($res as $row) {
			$xxx = new GKA_Kid();
			$xxx->primary_id = $row->id;
			$xxx->table = 'gokabam_api_versions';
			$xxx->object_id = $row->object_id;
			$this->kid_talk->fill_kids_in($xxx);
			$roots[] = $xxx;
		}


		//get api versions
		$res = $this->mydb->execSQL("
			SELECT 
				o.id,o.object_id
			FROM gokabam_api_api_versions o
			INNER JOIN gokabam_api_page_loads p_last ON p_last.id = o.last_page_load_id
			
			WHERE o.is_deleted = 0  AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['ii',$first_ts_param,$last_ts_param],
			MYDB::RESULT_SET,
			"@sey@api-versions.do_all.filler-manager.php"
		);

		if (empty($res))  {$res = [];}
		foreach($res as $row) {
			$xxx = new GKA_Kid();
			$xxx->primary_id = $row->id;
			$xxx->table = 'gokabam_api_api_versions';
			$xxx->object_id = $row->object_id;
			$this->kid_talk->fill_kids_in($xxx);
			$roots[] = $xxx;
		}


		//get db tables
		$res = $this->mydb->execSQL("
			SELECT 
				o.id,o.object_id
			FROM gokabam_api_data_groups o
			INNER JOIN gokabam_api_page_loads p_last ON p_last.id = o.last_page_load_id
			
			WHERE o.is_deleted = 0  and o.group_type_enum = 'database_table' AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['ii',$first_ts_param,$last_ts_param],
			MYDB::RESULT_SET,
			"@sey@api-versions.do_all.filler-manager.php"
		);

		if (empty($res))  {$res = [];}
		foreach($res as $row) {
			$xxx = new GKA_Kid();
			$xxx->primary_id = $row->id;
			$xxx->table = 'gokabam_api_api_versions';
			$xxx->object_id = $row->object_id;
			$this->kid_talk->fill_kids_in($xxx);
			$roots[] = $xxx;
		}

		//do the fill
		foreach ($roots as $root) {
			$this->fill($root);
		}
	}


	/**
	 * Placeholder until we get user stuff, if ever
	 * @param int|string|null $user_id
	 *
	 * @return mixed
	 */
	public function get_user_id($user_id) {
		return $user_id;
		//todo make this a pretty id, and fill in user structure below at final processing of everything
	}

	/**
	 * @param GKA_Root $root
	 * @param object $data
	 * @return void
	 * @throws FillException
	 * @throws SQLException
	 */
	public function root_fill_helper($root,$data) {
		$last = new GKA_Touch();
		$last->version = $data->last_version;
		$last->user_id = $this->get_user_id($data->last_user);
		$last->ts = $data->last_ts;

		$first = new GKA_Touch();
		$first->version = $data->initial_version;
		$first->user_id = $this->get_user_id($data->initial_user);
		$first->ts = $data->last_ts;

		$root->initial_touch = $first;
		$root->recent_touch = $last;
		$root->delete = intval($data->is_deleted);
		$root->status = true; //automatic


		$root->md5_checksum = $data->md5_checksum;

		//fill in kid structs with primary ids of journals, tags, and words
		$res = $this->mydb->execSQL("
				SELECT 
					tag.id,
					tag.object_id,
					'tag' as what
				FROM gokabam_api_tags tag 
				WHERE tag.target_object_id = ? AND tag.is_deleted = 0
			UNION
				SELECT 
				word.id,
				word.object_id,
				'word' as what
				FROM gokabam_api_words word 
				WHERE word.target_object_id = ? AND word.is_deleted = 0
			UNION
				SELECT 
					journ.id,
					journ.object_id,
					'journ' as what
				FROM gokabam_api_journals journ 
				WHERE journ.target_object_id = ? AND journ.is_deleted = 0",
			['iii',$data->object_id,$data->object_id,$data->object_id],
			MYDB::RESULT_SET,
			"@sey@lionstigersbears.root_fill_helper.filler-manager.php"
		);

		if (!empty($res)) {
			foreach ($res as $row) {
				switch ($row->what) {
					case 'tag': {
						$pos = new GKA_Kid();
						$pos->object_id = $row->object_id;
						$pos->primary_id = $row->id;
						$pos->table = 'gokabam_api_tags';
						$root->tags[] = $pos;
						break;
					}
					case 'word': {
						$pos = new GKA_Kid();
						$pos->object_id = $row->object_id;
						$pos->primary_id = $row->id;
						$pos->table = 'gokabam_api_words';
						$root->words[] = $pos;
						break;
					}
					case 'journ': {
						$pos = new GKA_Kid();
						$pos->object_id = $row->object_id;
						$pos->primary_id = $row->id;
						$pos->table = 'gokabam_api_journals';
						$root->journals[] = $pos;
						break;
					}
					default: {
						throw new FillException("Logic error in getting lions tigers and bears oh my");
					}
				}
			}
		}

	}

	/**
	 * fills in an object, the Kid has to be set enough to pull the object from the db
	 *
	 * what happens:
	 *    gets something, converts it to the proper object if its in a kid string or kid structure
	 *    finds the filler from the plugins
	 *    plugin fills the object
	 *    in the filled object's each public root property or element in an public array property:
	 *          will call fill on this
	 *
	 *   each object that gets called here will be added to the everything and the kids squished
	 *
	 *   when the fill plugin does its thing, parents are always referenced by kids and not root objects
	 *
	 * @param GKA_Root|GKA_Kid|string $root
	 * @param boolean $b_child, default false
	 * @return GKA_Root|null <p>
	 * if this root is deleted, or not in date range set by constructor, will be null
	 * else will return one of the derived classes
	 * </p>
	 * @throws SQLException
	 * @throws FillException
	 * @throws ApiParseException
	 */
	public function fill($root,$b_child = false) {

		if (empty($root)) {
			return $root;
		}

		//convert from string or kid
		if (is_string($root)) {
			$root = $this->kid_talk->convert_parent_string_kid($root);
		}
		if (strcmp(get_class($root),'gokabam_api\GKA_Kid') === 0 ) {
			$this->kid_talk->fill_kids_in($root); //here root is a kid,
			// afterwards this call root is an object which holds kid
			$root = $this->reconstitute($root);
		}
		//make sure kid if filled in
		$this->kid_talk->fill_kids_in($root->kid);

		//see if already processed, sometimes a thing will have multiple references
		if (array_key_exists($root->kid->kid,$this->map_of_processed_roots)) {
			return $this->map_of_processed_roots[$root->kid->kid];
		}

		//get the class type
		$what = explode('\\',get_class($root));
		if (sizeof($what) !== 2) {
			throw new \InvalidArgumentException("Got {get_class($root)} but was expecting a single namespace");

		}
		$the_class = $what[1];
		$the_class_lower = strtolower($the_class);
		$filler_class_file = PLUGIN_PATH. "public/gateway/fillers/$the_class_lower.filler.php";
		if (!file_exists($filler_class_file)) {
			throw new \InvalidArgumentException("Cannot find a filler class for $the_class");
		}
		require_once $filler_class_file;
		$filler_class_name= "Fill_".$the_class;
		$first_ts_param = $this->start_ts;
		$last_ts_param = $this->end_ts;
		if ($b_child) {
			$first_ts_param = 0;
			$last_ts_param = 999999999999999;
		}

		if (is_null($first_ts_param)) {
			$first_ts_param = 0;
		}

		if (is_null($last_ts_param)) {
			$last_ts_param = 999999999999999;
		}

		$ret = call_user_func_array( $filler_class_name . "::fill", [$root,$this,$this->mydb,$first_ts_param,$last_ts_param] );
		if ($ret===false) {
			throw new \InvalidArgumentException("Cannot find a method of [$filler_class_name] [fill], for the filling of $the_class");
		}
		if (is_null($ret)) {
			return null;
		}

		//add for post processing
		// add to post processing array to reduce kids, and add to the everything correctly
		$this->processed_roots[] = $root;
		$this->map_of_processed_roots[$root->kid->kid] = $root;

		//go fill any new objects
		//when this returns all the sub processing is done, and the children are created
		foreach ($ret as $key => $top_node) {

			if (empty($top_node)) {
				continue;
			}
			if ($key === 'parent') {
				continue;
			}

			if (
				 is_a($top_node,"gokabam_api\GKA_Root" ) ||
				 is_a($top_node,"gokabam_api\GKA_Kid" )
			)  {
				$this->fill($top_node,true);
			}

			if ( is_array($top_node)) {
				foreach ($top_node as $what) {
					if (
						is_a($what,"gokabam_api\GKA_Root" ) ||
						is_a($what,"gokabam_api\GKA_Kid" )
					)  {
						$this->fill($what,true);
					}
				}
			}


		}
		return $ret;

	}

	/**
	 * @param GKA_Kid $kid
	 * @return GKA_Root
	 * @throws FillException
	 */
	protected function reconstitute($kid) {

		/**
		 * @type GKA_Root $ret
		 */
		$ret = null;

		switch ($kid->table) {
			case 'gokabam_api_api_versions': {
				$ret = new GKA_API_Version();
				break;
			}
			case 'gokabam_api_apis': {
				$ret = new GKA_API();
				break;
			}

			case 'gokabam_api_data_elements': {
				$ret = new GKA_Element();
				break;
			}
			case 'gokabam_api_data_group_examples': {
				$ret = new GKA_DataExample();
				break;
			}

			case 'gokabam_api_data_groups': {
				$ret = new  GKA_DataGroup();
				break;
			}
			case 'gokabam_api_family': {
				$ret = new  GKA_Family();
				break;
			}
			case 'gokabam_api_inputs': {
				$ret = new  GKA_Input();
				break;
			}
			case 'gokabam_api_journals': {
				$ret = new  GKA_Journal();
				break;
			}
			case 'gokabam_api_objects': {
				throw new FillException("Cannot fill a raw object");
			}
			case 'gokabam_api_output_headers': {
				$ret = new GKA_Header();
				break;
			}
			case 'gokabam_api_outputs': {
				$ret = new GKA_Output();
				break;
			}
			case 'gokabam_api_tags': {
				$ret = new GKA_Tag();
				break;
			}
			case 'gokabam_api_use_case_part_connections': {
				$ret = new GKA_Use_Part_Connection();
				break;
			}
			case 'gokabam_api_use_case_parts': {
				$ret = new GKA_Use_Part();
				break;
			}
			case 'gokabam_api_use_case_parts_sql': {
				$ret = new GKA_SQL_Part();
				break;
			}
			case 'gokabam_api_use_cases': {
				$ret = new GKA_Use_Case();
				break;
			}
			case 'gokabam_api_versions': {
				$ret = new GKA_Version();
				break;
			}
			case 'gokabam_api_words': {
				$ret = new GKA_Word();
				break;
			}
			default: {
				throw new FillException("Cannot find object from kid table");
			}

		}
		$ret->kid = $kid;
		return $ret;
	}




	/**
	 * @param GKA_Root $root
	 * @throws FillException
	 */
	protected function add_to_everything($root) {
		// all nodes come through here, so if top nodes, put in top arrays of elements
		// always put everything into the library keyed by kid, if not already included
		//some things should have their immediate children referenced to a kid string
		//  the children will be added in their own time, after this but exist on their own in the processed roots
		//
		// switch out elements for sql parts
		//              api for use parts

		//switch on the class name
		//get the class type
		$what = explode('\\',get_class($root));
		if (sizeof($what) !== 2) {
			throw new \InvalidArgumentException("Got {get_class($root)} but was expecting a single namespace");

		}
		$the_class = $what[1];
		switch ($the_class) {
			case 'GKA_Word': {
				//words are unique to their objects, but we reference the word in the words array
				// so replace words on all the other objects
				$this->everything->words[] = $root->kid;
				break;
			}
			case 'GKA_Tag': {
				//tags are unique to their objects, but we reference the tag in the tags array
				// so replace tags on all the other objects
				$this->everything->tags[] = $root->kid;
				break;
			}
			case 'GKA_Journal': {
				//journals are unique to their objects, but reference this in the journals array so flatten
				// they have only words and tags as dependencies
				$this->everything->journals[] = $root->kid;
				break;
			}
			case 'GKA_Element': {

				/**
				 * @var GKA_Element $element
				 */
				$element = $what;
				//flatten anything in the elements array
				foreach ($element->elements as $key => $value) {
					$element->elements[$key] = $value->kid;
				}
				$this->everything->elements[] = $root->kid;
				break;
			}
			case 'GKA_DataGroup': {
				/**
				 * @var GKA_DataGroup $group
				 */
				$group = $what;
				//flatten anything in the elements array
				foreach ($group->elements as $key => $value) {
					$group->elements[$key] = $value->kid;
				}

				//flatten anything in the examples array
				foreach ($group->examples as $key => $value) {
					$group->examples[$key] = $value->kid;
				}

				if ($group->type === 'database_table') {
					$this->everything->table_groups[] = $group->kid;
				} else {
					$this->everything->data_groups[] = $group->kid;
				}
				break;
			}
			case 'GKA_DataExample': {
				//data examples do not have anything to flatten
				$this->everything->examples[] = $root->kid;
				break;
			}
			case 'GKA_Header': {
				/**
				 * @var GKA_Header $header
				 */
				$header = $what;
				//flatten anything in the data groups array
				foreach ($header->data_groups as $key => $value) {
					$header->data_groups[$key] = $value->kid;
				}
				$this->everything->headers[] = $root->kid;
				break;
			}
			case 'GKA_Output': {
				/**
				 * @var GKA_Output $output
				 */
				$output = $what;
				//flatten anything in the data groups array
				foreach ($output->data_groups as $key => $value) {
					$output->data_groups[$key] = $value->kid;
				}

				foreach ($output->headers as $key => $value) {
					$output->headers[$key] = $value->kid;
				}

				$this->everything->outputs[] = $root->kid;
				break;
			}
			case 'GKA_Input': {
				/**
				 * @var GKA_Input $input
				 */
				$input = $what;
				//flatten anything in the data groups array
				foreach ($input->data_groups as $key => $value) {
					$input->data_groups[$key] = $value->kid;
				}
				$this->everything->inputs[] = $root->kid;
				break;
			}
			case 'GKA_API': {
				/**
				 * @var GKA_API $api
				 */
				$api = $what;

				foreach ($api->inputs as $key => $value) {
					$api->inputs[$key] = $value->kid;
				}

				foreach ($api->outputs as $key => $value) {
					$api->outputs[$key] = $value->kid;
				}

				foreach ($api->headers as $key => $value) {
					$api->headers[$key] = $value->kid;
				}

				foreach ($api->use_cases as $key => $value) {
					$api->use_cases[$key] = $value->kid;
				}
				$this->everything->apis[] = $root->kid;
				break;
			}
			case 'GKA_Family': {
				/**
				 * @var GKA_Family $family
				 */
				$family = $what;

				foreach ($family->apis as $key => $value) {
					$family->apis[$key] = $value->kid;
				}

				foreach ($family->headers as $key => $value) {
					$family->headers[$key] = $value->kid;
				}
				$this->everything->families[] = $root->kid;
				break;
			}
			case 'GKA_API_Version': {
				/**
				 * @var GKA_API_Version $api_version
				 */
				$api_version = $what;

				foreach ($api_version->headers as $key => $value) {
					$api_version->headers[$key] = $value->kid;
				}

				foreach ($api_version->families as $key => $value) {
					$api_version->families[$key] = $value->kid;
				}
				$this->everything->api_versions[] = $root;
				break;
			}
			case 'GKA_SQL_Part': {
				/**
				 * notice hear we are dealing with the kids here, and assume the plugin set up them correctly
				 * @var GKA_SQL_Part $sql_part
				 */
				$sql_part = $what;
				$sql_part->outside_element_kid = $sql_part->outside_element_kid->kid;
				$sql_part->reference_db_element_kid = $sql_part->reference_db_element_kid->kid;
				$sql_part->db_element_kid = $sql_part->db_element_kid->kid;
				$this->everything->sql_parts[] = $root->kid;
				break;
			}
			case 'GKA_Use_Part': {
				/**
				 * @var GKA_Use_Part $use_part
				 */
				$use_part = $what;
				$use_part->in_api_kid = $use_part->in_api_kid->kid;
				foreach ($use_part->in_data_groups as $key => $value) {
					$use_part->in_data_groups[$key] = $value->kid;
				}

				foreach ($use_part->out_data_groups as $key => $value) {
					$use_part->out_data_groups[$key] = $value->kid;
				}

				foreach ($use_part->sql_parts as $key => $value) {
					$use_part->sql_parts[$key] = $value->kid;
				}
				$this->everything->use_parts[] = $root->kid;
				break;
			}
			case 'GKA_Use_Part_Connection': {
				/**
				 * @var GKA_Use_Part_Connection $use_part_connection
				 */
				$use_part_connection = $what;
				$use_part_connection->source_part_kid = $use_part_connection->source_part_kid->kid;
				$use_part_connection->destination_part_kid = $use_part_connection->destination_part_kid->kid;
				$this->everything->use_part_connections[] = $root->kid;
				break;
			}
			case 'GKA_Use_Case': {
				/**
				 * @var GKA_Use_Case $use_case
				 */
				$use_case = $what;

				foreach ($use_case->use_parts as $key => $value) {
					$use_case->use_parts[$key] = $value->kid;
				}

				foreach ($use_case->connections as $key => $value) {
					$use_case->connections[$key] = $value->kid;
				}

				$this->everything->use_cases[] = $root->kid;
				break;
			}
			case 'GKA_Version': {
				//version does not have any dependencies other than words tags and journals
				$this->everything->versions[] = $root->kid;
				break;
			}
			default : {
				throw new FillException("No switch case for $the_class");
			}
		}

		//replace journals, tags, and words for any that have them with the kid string
		foreach ($root->words as $key => $value) {
			$root->words[$key] = $value->kid;
		}

		foreach ($root->tags as $key => $value) {
			$root->tags[$key] = $value->kid;
		}

		foreach ($root->journals as $key => $value) {
			$root->journals[$key] = $value->kid;
		}

		//fill in the everything library
		// unless is a api version
		if (!array_key_exists($root->kid,$this->everything->library)) {
			if ($the_class !== 'GKA_API_Version') {
				$this->everything->library[$root->kid] = $root;
			}

		}




	}

	/**
	 * @throws ApiParseException
	 * @throws SQLException
	 * @throws FillException
	 */
	protected function finalize_processed_roots() {

		foreach ($this->processed_roots as $root) {

			//if any property is a kid
			foreach ($root as $property_key => $property_value) {

				if (
				 	    is_object($property_value) &&
				        ( strcmp(get_class($property_value),'gokabam_api\GKA_Kid') === 0 )
				 ) {

				 	if (empty($property_value->kid)) {
				        $this->kid_talk->fill_kids_in($property_value);
				    }

				    $root->$property_key = $property_value->kid; //change the object back to a string
				 }

				 if (is_array($property_value)) {
				 	foreach ($property_value as $jam_index => $jam) {
					    if (
						    is_object($jam) &&
						    ( strcmp(get_class($jam),'gokabam_api\GKA_Kid') === 0 )
					    ) {

						    if (empty($jam->kid)) {
							    $this->kid_talk->fill_kids_in($jam);
						    }

						    $property_value[$jam_index] = $jam->kid; //change the object back to a string
					    }
				    }
				    $root->$property_key = $property_value;
				 }

			}

		}

		//need to have all children processed first on the kid
		foreach ($this->processed_roots as $root) {

			$this->add_to_everything($root);
		}

		$this->processed_roots = []; //reset

	}

	/**
	 * @return array
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	protected function get_deleted_array() {
		$first_ts_param = $this->start_ts;
		$last_ts_param = $this->end_ts;

		if (is_null($first_ts_param)) {
			$first_ts_param = 0;
		}

		if (is_null($last_ts_param)) {
			$last_ts_param = 999999999999999;
		}

		$res = $this->mydb->execSQL("
			SELECT 
				o.id as object_id,
				o.primary_key,
				o.da_table_name
			FROM gokabam_api_objects o
			INNER JOIN gokabam_api_change_log g ON g.target_object_id = o.id 
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = g.page_load_id
			
			WHERE g.edit_action = 'delete'  AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['iii',$first_ts_param,$last_ts_param],
			MYDB::RESULT_SET,
			"@sey@get_deleted_array.filler-manager.php"
		);
		$ret = [];
		if (empty($res))  {return $ret;}
		foreach($res as $row) {
			$xxx = new GKA_Kid();
			$xxx->primary_id = $row->primary_key;
			$xxx->table = $row->da_table_name;
			$xxx->object_id = $row->object_id;
			$this->kid_talk->fill_kids_in($xxx);
			$ret[] = $xxx;
		}
		return $ret;
	}

}
