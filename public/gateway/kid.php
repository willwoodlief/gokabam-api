<?php

namespace gokabam_api;
use Hashids\Hashids;
require_once    PLUGIN_PATH.'public/gateway/api-typedefs.php';
require_once    PLUGIN_PATH.'vendor/autoload.php';

class KidTalk {
	protected static  $table_to_key_map = [
		'gokabam_api_api_versions' => 'apiversion',
		'gokabam_api_apis' => 'api',
		'gokabam_api_data_element_objects' => 'obj',
		'gokabam_api_data_elements' => 'element',
		'gokabam_api_data_group_examples' => 'example',
		'gokabam_api_data_group_members' => 'member',
		'gokabam_api_data_groups' => 'group',
		'gokabam_api_family' => 'family',
		'gokabam_api_inputs' => 'input',
		'gokabam_api_journals' => 'journal',
		'gokabam_api_objects' => 'key',
		'gokabam_api_output_headers' => 'header',
		'gokabam_api_outputs' => 'output',
		'gokabam_api_tags' => 'tag',
		'gokabam_api_use_case_part_connections' => 'connection',
		'gokabam_api_use_case_parts' => 'part',
		'gokabam_api_use_case_parts_sql' => 'sql',
		'gokabam_api_use_cases' => 'case',
		'gokabam_api_versions' => 'version',
		'gokabam_api_words' => 'word'
	];

	protected static  $key_to_table_map = [
		'apiversion' => 'gokabam_api_api_versions',
		'api' => 'gokabam_api_apis',
		'obj'  => 'gokabam_api_data_element_objects',
		'element' => 'gokabam_api_data_elements' ,
		'example'  => 'gokabam_api_data_group_examples' ,
		'member' => 'gokabam_api_data_group_members',
		'group'=>  'gokabam_api_data_groups'  ,
		'family'=>  'gokabam_api_family'  ,
		'input'=>  'gokabam_api_inputs'  ,
		'journal'=>  'gokabam_api_journals'  ,
		'key' => 'gokabam_api_objects'  ,
		'header'=>  'gokabam_api_output_headers'  ,
		'output'=>  'gokabam_api_outputs'  ,
		'tag'=>  'gokabam_api_tags' ,
		'connection' => 'gokabam_api_use_case_part_connections'  ,
		'part' => 'gokabam_api_use_case_parts'  ,
		'sql'  => 'gokabam_api_use_case_parts_sql'  ,
		'case'=> 'gokabam_api_use_cases'  ,
		'version' => 'gokabam_api_versions'  ,
		'word' =>  'gokabam_api_words'
	];

	/**
	 * @const string HASH_ALPHABET
	 */
	const HASH_ALPHABET = 'abcdefghijkmnpqrstwxyzABCDEFGHJKLMNPRSTWXYZ123456789';


	/**
	 * @const string HASH_SALT
	 */
	const HASH_SALT = 'gokabam';

	/**
	 * @const integer HASH_LENGTH
	 */
	const HASH_LENGTH = 6;



	/**
	 * @var Hashids|null $hashids
	 */
	protected $hashids = null;

	/**
	 * @var MYDB $mydb
	 */
	protected $mydb = null;

	public function __construct( $mydb ) {

		$this->mydb = $mydb;
		//not a security tool, does not matter if salt is public
		// we hash the ids because its cool
		$this->hashids = new Hashids(
			self::HASH_SALT,
			self::HASH_LENGTH,
			self::HASH_ALPHABET
		);
	}

	/**
	 * @param string $table
	 *
	 * @return string
	 * @throws ApiParseException
	 */
	public static function get_code_for_table($table) {
		$table = trim($table);
		if ($table) {
			if (!array_key_exists($table,self::$table_to_key_map)) {
				throw  new ApiParseException("Unexpected table of [$table]");
			}
			return self::$table_to_key_map[$table];
		} else {
			throw  new ApiParseException("empty table name when trying to get code for table");
		}
	}

	/**
	 * If kid is string, then we generate the object id from it, and if we are given the table is and or object id,
	 * verify that the information is as expected
	 *
	 * if kid is an object, verify the information is accurate and fill in anything missing by the information provided
	 *
	 * if kid is null, then create the object from the information provided
	 *
	 * if there is not enough info to do something then throw a parse exception
	 * if there is a mismatch of information then also throw an exception
	 *
	 * @param GKA_Kid|string|null $kid_candidate
	 * @param string $table_expected
	 * @param integer|null $table_id
	 * @param integer|null $object_id
	 *
	 * @return GKA_Kid
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	public  function generate_or_refresh_primary_kid($kid_candidate,$table_expected,$table_id = null,$object_id = null) {

		if (empty($kid_candidate) && empty($table_id) && empty($object_id)) {
			return null;
		}
		if (!array_key_exists($table_expected,self::$table_to_key_map)) {
			throw  new ApiParseException("Unexpected table of [$table_expected]");
		}
		$encode_prefix = self::$table_to_key_map[$table_expected];
		if (empty($kid_candidate)) {

			//get and verify the object id
			if ($table_id && $object_id) {
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL("select o.id from gokabam_api_objects o INNER JOIN $table_expected t ON t.id = o.primary_key where t.id = ? and da_table_name = ? and o.id = ? ",
					['isi',$table_id,$table_expected,$object_id],MYDB::RESULT_SET,"@sey@KidTalk::generate_or_refresh_kid/verify_full_$table_expected");
				if (empty($check)) {
					throw new ApiParseException("did not find an object for {$table_expected}, primary id of {$table_id} and object id of {$object_id}");
				}

			} elseif ($table_id) {
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL("select o.id from gokabam_api_objects o INNER JOIN $table_expected t ON t.id = o.primary_key where t.id = ? and da_table_name = ?  ",
					['is',$table_id,$table_expected],MYDB::RESULT_SET,"@sey@KidTalk::generate_or_refresh_kid/verify_with_table_$table_expected");
				if (empty($check)) {
					throw new ApiParseException("did not find an object for {$table_expected}, primary id of {$table_id}");
				}
				$object_id = $check[0]->id;
			} elseif ($object_id) {
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL("select t.id as table_id from gokabam_api_objects o INNER JOIN $table_expected t ON t.id = o.primary_key where  da_table_name = ?  and o.id = ? ",
					['si',$table_expected,$object_id],MYDB::RESULT_SET,"@sey@KidTalk::generate_or_refresh_kid/verify_with_object_$table_expected");
				if (empty($check)) {
					throw new ApiParseException("did not find an object for {$table_expected}, primary id of {$table_id}");
				}
				$table_id = $check[0]->table_id;
			} else {
				throw new ApiParseException("Need either a table primary key or an object id to generate a kid");
			}

			$encoding =  $this->hashids->encode($object_id);
			if (empty($encoding)) {
				throw new ApiParseException("Could not encode {$table_expected} -> $object_id");
			}

			$kid                = new GKA_Kid();
			$kid->table         = $table_expected;
			$kid->primary_id    = $table_id;
			$kid->object_id     = $object_id;
			$kid->kid           = $encode_prefix . '_'. $encoding ;
			$kid->hint          = '';
			return $kid;
		}
		elseif (is_string($kid_candidate)) {

			//break apart the kid to the hint and the code
			if ( preg_match( /** @lang text */
				'/(?P<table>[a-z]*)_(?P<code>[[:alnum:]]*)(?P<hint>[^[:alnum:]][[:alnum:]]*)?/', $kid_candidate, $output_array ) ) {
				$table_key = $output_array['table'];
				$code      = $output_array['code'];
				$hint      = null;
				if (array_key_exists('hint',$output_array) ) {
					$hint = $output_array['hint'];
				}
				if ( ! array_key_exists( $table_key, self::$key_to_table_map ) ) {
					throw new ApiParseException( "Cannot find a table match for the kid prefix of [$table_key], the kid was [$kid_candidate]" );
				}
				$table = self::$key_to_table_map[ $table_key ];
				if ( strcmp( $table_expected, $table ) !== 0 ) {
					throw new ApiParseException( "Excepted $table_expected but got $table" );
				}
				$where              = $this->hashids->decode( $code );
				$object_primary_key = $where[0];
				//check to make sure is correct type for the prefix given
				//the mydb class caches the compiled sql so its faster after the first lookup
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL( "select t.id as table_id, o.id as object_id from gokabam_api_objects o INNER JOIN $table t ON t.id = o.primary_key where o.id = ? and da_table_name = ? ",
					[ 'is', $object_primary_key, $table ], MYDB::RESULT_SET, "@sey@KidTalk::string_to_kid/find_$table" );
				if ( empty( $check ) ) {
					throw new ApiParseException( "the kid of [$kid_candidate] is not verified as belonging to $table_key" );
				}
				$primary_id = $check[0]->table_id;
				$object_id  = $check[0]->object_id;

				$kid             = new GKA_Kid();
				$kid->table      = $table;
				$kid->primary_id = $primary_id;
				$kid->object_id  = $object_id;
				$kid->kid        = $kid_candidate;
				$kid->hint       = $hint;

				return $kid;
			}
			throw new ApiParseException( "the kid of [$kid_candidate] does not have valid format of XXX_YYYYY(HHHHH) [example] " );
		} elseif (is_object($kid_candidate)) {

			if (empty($kid_candidate->table)) {
				$table = $table_expected;
			} else {
				$table = $kid_candidate->table;
				if (strcmp($table_expected,$table) !== 0) {
					throw new ApiParseException("table in kid [$table] and the expected table [$table_expected] are different ");
				}
			}

			if ($kid_candidate->primary_id) {
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL( "select o.id from gokabam_api_objects o INNER JOIN $table t ON t.id = o.primary_key where t.id = ? and da_table_name = ? ",
					[
						'is',
						$kid_candidate->primary_id,
						$kid_candidate->table
					], MYDB::RESULT_SET, "@sey@KidTalk::kid_to_string/candidate_has_primary_$table" );
				if ( empty( $check ) ) {
					throw new ApiParseException( "did not find an object for {$kid_candidate->table}, id of {$kid_candidate->primary_id} " );
				}
				$found_object_id = $check[0]->id;

				//verify object and pk match if given
				if ( $object_id ) {
					if ( $object_id != $found_object_id ) {
						throw new ApiParseException( "object id calculated [$found_object_id] and the expected object id [$object_id] are different " );
					}
				}

				if ( $kid_candidate->object_id ) {
					if ( $kid_candidate->object_id != $found_object_id ) {
						throw new ApiParseException( "object id in kid [{$kid_candidate->object_id}] and the expected id [$object_id] are different " );
					}
				}
				$table_id =$kid_candidate->primary_id; //must be or not get here
				$object_id = $found_object_id;
			} elseif ($kid_candidate->object_id) {
				/** @noinspection SqlResolve */
				$check = $this->mydb->execSQL( "select t.id as table_id from gokabam_api_objects o INNER JOIN $table t ON t.id = o.primary_key where o.id = ? and da_table_name = ? ",
					[
						'is',
						$kid_candidate->object_id,
						$kid_candidate->table
					], MYDB::RESULT_SET, "@sey@KidTalk::kid_to_string/get_pk_from_ok_$table" );
				if ( empty( $check ) ) {
					throw new ApiParseException( "did not find an pk for {$kid_candidate->table}, object id of {$kid_candidate->object_id} " );
				}

				$found_pk_id = $check[0]->table_id;
				if ($found_pk_id) {
					if ( $kid_candidate->primary_id != $found_pk_id ) {
						throw new ApiParseException( "primary id in kid [{$kid_candidate->primary_id}] and the found pk id [$found_pk_id] are different " );
					}
				}

				$table_id = $found_pk_id;
				$object_id = $kid_candidate->object_id;

			} else {
				//not enough info in kid candidate
				return $this->generate_or_refresh_primary_kid(null,$table_expected,$table_id,$object_id);
			}
			$kid_candidate->object_id = $object_id;
			$kid_candidate->table = $table;
			$kid_candidate->primary_id = $table_id;

			$encoding = $this->hashids->encode($object_id);
			if (empty($encoding)) {
				throw new ApiParseException("Could not encode {$kid_candidate->table} -> $object_id");
			}
			$kid_candidate->kid = $encode_prefix . '_'. $encoding . $kid_candidate->hint;
			return $kid_candidate;
		} else {
			throw new ApiParseException("Kid Candidate was not null, string or object");
		}
	}

	/**
	 *
	 * This will do a one way conversion with the only checking is the code in the input string
	 * This can be used for regular string to kid conversion also
	 * @param string|null $string_kid
	 * @param GKA_Kid|null $child - for error messages only
	 * @param string|null $child_table_type - for error messages only
	 *
	 * @return GKA_Kid
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	public function convert_parent_string_kid($string_kid,$child = null,$child_table_type=null) {

		$child_table_prefix = '';
		if ($child_table_type) {
			if (!array_key_exists($child_table_type,self::$table_to_key_map)) {
				throw  new ApiParseException("Unexpected table of [$child_table_type]");
			}
			$child_table_prefix = self::$table_to_key_map[$child_table_type];
		}


		if(! is_string($string_kid)) {
			if ($child) {
				throw new ApiParseException("The parent of " .$child->kid . " is not a string");
			} else {
				throw new ApiParseException("The parent of a " .$child_table_prefix . " is not a string");
			}

		}

		$string_kid = trim($string_kid);
		if (empty($string_kid)) {

			if ($child) {
				throw new ApiParseException("The parent of " .$child->kid . " is empty");
			} else {
				throw new ApiParseException("The parent of a " .$child_table_prefix . " is not empty");
			}
		}

		//break apart the kid to the hint and the code
		if ( preg_match( /** @lang text */
			'/(?P<table>[a-z]*)_(?P<code>[[:alnum:]]*)(?P<hint>[^[:alnum:]][[:alnum:]]*)?/', $string_kid, $output_array ) ) {
			$table_key = $output_array['table'];
			$code      = $output_array['code'];
			$hint      = null;
			if (array_key_exists('hint',$output_array) ) {
				$hint = $output_array['hint'];
			}
			if ( ! array_key_exists( $table_key, self::$key_to_table_map ) ) {
				throw new ApiParseException( "Cannot find a table match for the parent prefix of [$table_key], the child was [{$child->kid}]" );
			}
			$table = self::$key_to_table_map[ $table_key ];

			$where              = $this->hashids->decode( $code );
			$object_primary_key = $where[0];
			//check to make sure is correct type for the prefix given
			//the mydb class caches the compiled sql so its faster after the first lookup
			/** @noinspection SqlResolve */
			$check = $this->mydb->execSQL(
				"select t.id as table_id, o.id as object_id 
						from gokabam_api_objects o 
						INNER JOIN $table t ON t.id = o.primary_key 
						where o.id = ? and da_table_name = ? ",
				[ 'is', $object_primary_key, $table ], MYDB::RESULT_SET, "@sey@KidTalk::convert_parent_string_kid/find_$table" );

			if ( empty( $check ) ) {
				if ($child) {
					throw new ApiParseException("the kid of [$string_kid] is not verified as belonging to $table_key, this is the parent of {$child->kid}");
				} else {
					throw new ApiParseException("the kid of [$string_kid] is not verified as belonging to $table_key, this is the parent of a " .$child_table_prefix . " is not empty");
				}
			}

			$primary_id = $check[0]->table_id;
			$object_id  = $check[0]->object_id;

			$kid             = new GKA_Kid();
			$kid->table      = $table;
			$kid->primary_id = $primary_id;
			$kid->object_id  = $object_id;
			$kid->kid        = $string_kid;
			$kid->hint       = $hint;

			return $kid;
		}

		if ($child) {
			throw new ApiParseException("the kid of [$string_kid] does not have valid format of XXX_YYYYY(HHHHH) [example], this is the parent of {$child->kid} ");
		} else {
			throw new ApiParseException("the kid of [$string_kid] does not have valid format of XXX_YYYYY(HHHHH) [example], this is the parent of a " .$child_table_prefix );
		}




	}


}