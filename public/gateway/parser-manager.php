<?php

namespace gokabam_api;


require_once PLUGIN_PATH . 'public/gateway/api-typedefs.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/version.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/tag.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/word.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/journal.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/family.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/api-version.parser.php';
require_once    PLUGIN_PATH.'public/gateway/parsers/header.parser.php';



/**
 * @param KidTalk $kid_talk
 * @param MYDB $mydb
 * @param GKA_Everything $everything

 * @return void
 * @throws ApiParseException
 * @throws JsonException
 * @throws SQLException
 */
class ParserManager {

	/**
	 * @var MYDB $mydb
	 */
	public $mydb = null;

	/**
	 * @var KidTalk $kid_talk
	 */
	public $kid_talk = null;

	/**
	 * @var GKA_Everything $everything
	 */
	public $everything = null;

	/**
	 * PK of the the page load generated for this call and passed to all inserts and updates
	 * @var int $last_load_id
	 */
	public $last_load_id = 0;

	/**
	 * @var int $current_version_id;
	 */
	public $current_version_id = 0;

	/**
	 * @var GKA_Root[] $processed_roots
	 *  all parsers add what they do to this, its used to finalize the data before sending it back out
	 */
	public $processed_roots = [];

	/**
	 * @var array $processed_array
	 */
	public $processed_array = [];

	/**
	 * @var GKA_Kid|null $parent_kid
	 */
	public $parent_kid = null;

	/**
	 * @var ParserManager|null
	 */
	public $parser_master = null;

	protected static $map = [
		'versions'      =>	"gokabam_api\\ParseVersion",
		'tags'          =>  "gokabam_api\\ParseTag",
		'words'         =>  "gokabam_api\\ParseWord",
		'journals'      =>  "gokabam_api\\ParseJournal",
		'api_versions'  =>  "gokabam_api\\ParseApiVersion",
		'families'      =>  "gokabam_api\\ParseFamily",
		'headers'      =>   "gokabam_api\\ParseHeader",
		'elements'      =>  "gokabam_api\\ParseElement"
	];

	/**
	 * ParserManager constructor.
	 *
	 * @param KidTalk $kid_talk
	 * @param MYDB $mydb
	 * @param GKA_Everything|null $everything
	 * @param integer $last_load_id
	 * @param array $info
	 * @param GKA_Kid|null $parent_kid
	 * @param ParserManager|null $parser_master
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	public function __construct($kid_talk, $mydb,$everything ,$last_load_id,$info,$parent_kid=null,$parser_master=null) {
		global $GokabamGoodies;
		$this->current_version_id = $GokabamGoodies->get_current_version_id();
		$this->everything         = $everything;
		$this->kid_talk           = $kid_talk;
		$this->mydb               = $mydb;
		$this->parent_kid         = $parent_kid;
		$this->processed_roots    = [];
		$this->processed_array    = [];
		$this->last_load_id       = $last_load_id;
		$this->parser_master = $parser_master;
		if (empty($last_load_id)) {
			throw new ApiParseException("Expected a page load id");
		}
		$this->start_parse($info);
		$this->finalize_processed_roots();
	}

	/**
	 * @param GKA_Root $root
	 */
	public function add_to_finalize_roots($root) {
		if ($this->parser_master) {
			$this->parser_master->add_to_finalize_roots($root);
		} else {
			$this->processed_roots[] = $root;
		}
	}

	/** @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * @param $parser_name
	 * @param array $info
	 * @param null|object $parent
	 * @return mixed[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	public function call_parser($parser_name,$info,$parent=null) {
		if (array_key_exists($parser_name,self::$map)) {
			$callable = self::$map[$parser_name];
			$ret = call_user_func_array( $callable . "::parse", [$this,$info,$parent] );
			if ($ret===false) {
				throw new \InvalidArgumentException("Cannot find a method of [$callable] [parse], for the parsing of $parser_name");
			} else {
				return $ret;
			}
		} else {
			throw new ApiParseException("$parser_name is not found in the ParserManager List");
		}
	}

	protected function finalize_processed_roots() {
		foreach ($this->processed_roots as $root) {
			$root->kid = $root->kid->kid; //change the object back to a string
			if ($root->parent) {
				$root->parent = $root->parent->kid; //change the object back to a string
			}
		}
	}

	/**
	 * //go down all parsable keys that may exist in info
	 * @param array $info
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 * @return void
	 */
	protected function start_parse($info) {

		//top level entries do not have parents, so always pass null as parent in this method

		//deep copy info
		$copy_string = JsonHelper::toString($info);
		$copy = JsonHelper::fromString($copy_string);
		$map_keys = array_keys(self::$map);
		foreach ($map_keys as $key) {
			if (array_key_exists($key,$info)) {
				$results =  $this->call_parser($key,$info[$key],$this->parent_kid);
				$copy[$key] = $results;
				if ($this->everything) {
					$this->everything->$key = $results;
				}

			}
		}
		$this->processed_array = $copy;

	}
}
/*
 *
 When updating or inserting new things into the database, things have to be done in order.
 Sometimes parents or children need to be done first . So there needs to be some organization
 about which jobs are carried out.

 The parsers will take each node they do, and create a job. The jobs will sometimes need other jobs done first

data group (A)
	data element (B)
	data element (C)
		data element (D) (goes with C)
HERE, the order is ABCD but will need to revisit after D,C,B is all created and
  they will need info about their parent ids

A is created , then B, then C, Then D
then C needs to revisit with D, and C needs to revisit with A, then B needs to revisit with A
then A needs to do cleanup

the jobs modify the part of the structure, and will convert the kids, when everything is done
and after all things are created and updated,
need to switch out the kids in the final data structure

if a parser launches a parser for a subbranch, then both parsers know of each other
and can put in the proper job entries

JOB
	first step put on stack
	when first step is completed, this is called again,
	 and will decide to put next thing on stack
	it has pointers to the parent and child data structures and can decide then

but
API (A)
	INPUT (B)
	OUTPUT (C)
		header (D)
	header (E)

HERE, the order is AB CD E and bcde all need their parent info

for each type of parser, key the factory to a lookup table for the table:
create the class, and it will return when that segment is done

parser_factory($list,$parent_node)
  for each node in list
		this_($node,$parent_node)
		when return do connections between child and parent
 */