/**
 * Typedef the identity of a GKA object
 * @typedef {string|null} GKA_Kid
 */


/**
 * Typedef the User Data, each edit, insert and delete is associated with a user
 * @typedef {Object} GKA_User
 * @property {GKA_Kid} user_id      unique for each user, starts with user_
 * @property {string} user_name      Describes the user, not the login name but the descriptive name
 * @property {string} user_email     How to contact the user
 * @property {integer} ts_since      Timestamp since the user started in the system
 */


/**
 * Typedef for GKA_Touch, which is a read only object sent from the server to talk about an action
 * @typedef {object} GKA_Touch
 * @property {GKA_Kid} version  this is the version that the change is connected to
 * @property {integer} ts       when this happened
 * @property  {GKA_Kid} user    the user who did this
 */

/**
 * Typedef for GKA_Root, which is a base type for a lot of the following definitions after this
 * @typedef {object} GKA_Root
 * @property {GKA_Kid} kid
 * @property {boolean} status  read only
 * @property {boolean} delete
 * @property {GKA_Kid} parent
 * @property {any} pass_through  not processed by the server, can be any information to help the script process
                                 the insert or update. Not filled in with get
 * @property {GKA_Kid[]} words          , 0 or more words
 * @property {GKA_Kid[]} journals       , 0 or more journals
 * @property {GKA_Kid[]} tags           , 0 or more tags
 * @property {GKA_Touch} initial_touch
 * @property {GKA_Touch} recent_touch
 * @property {string}  md5_checksum
 */



/**
 * Typedef for GKA_Version, it inherits from GKA_Root
 * @typedef {GKA_Root} GKA_Version
 * @property {string|null} website_url   , if there is an associated website url about this
 * @property {integer|null} post_id      , if a blog post is made about this on this wordpress
 * @property {string|null} git_repo_url  , associated git repo online
 * @property {string|null} git_tag       , associated git_tag
 * @property {string|null} git_commit_id , associated commit
 * @property {string} text               , the  internal name of this version
 */



/**
 * Typedef for GKA_Word, it inherits from GKA_Root, the parent can be anything but tags and words
 * @typedef {GKA_Root} GKA_Word
 * @property {string} type
 * @property {string} language
 * @property {string} text
 */




/**
 * Typedef for GKA_Journal, it inherits from GKA_Root, the parent can be anything but journals
 * @typedef {GKA_Root} GKA_Journal
 * @property {string} text
 */




/**
 * Typedef for GKA_Tag, it inherits from GKA_Root, the parent can be anything but tags and words
 * @typedef {GKA_Root} GKA_Tag
 * @property {string} text
 * @property {string} value
 */





/**
 * Elements belong to data groups and other elements, they are also referenced in SQL Parts
 * @see GKA_Everything
 * @see GKA_DataGroup
 * @see GKA_SQL_Part

 * Typedef for GKA_Element, it inherits from GKA_Root
 * @typedef {GKA_Root} GKA_Element
 * @property {string} text              , the element name in the code and call
 * @property {string} value             , string|integer|number|boolean|object|array
 * @property {string} format            , format can can on certain values based on the type
                                            if set with type array or object will be error
                                            format integer:
                                                'int32',
                                                'int8',
                                                'int64',
                                                'use_pattern'

                                            format number
                                                'float',
                                                'double',
                                                'use_pattern'

                                            format string:
                                                'date',
                                                'date-time',
                                                'password',
                                                'byte',
                                                'binary',
                                                'email',
                                                'uri',
                                                'use_pattern'

 * @property {string|null} pattern          , used if format says use_pattern (for string,integer and number)
                                                error if this is set to something and the type is not one of those three
                                                error if set and the format is not use_pattern

 * @property {boolean} is_nullable          , default false
 * @property {boolean} is_optional          , default false
 * @property {string[]|null} enum_values    , only with string,integer,number format : error otherwise
 * @property {string|null} default_value    , if not set , the default is always null
                                               will throw error if set for array or object

 * @property (integer|null} min             , error if set to non zero for object or boolean
                                                  type string is min character length
                                                  type integer and number the min value
                                                  type array is the min number of elements
                                                  default 0

 * @property {integer|null} max             , error if set to non zero for object or boolean
                                                 type string is max character length
                                                 type integer and number the max value
                                                 type array is the max number of elements
                                                 default 0

 * @property {float|null} multiple          , error if set for anything other than integer|number
                                                if set the values must be multiples of this

 * @property {float|null} precision         , error if set for anything other than number
 * @property {integer|null} rank            , shows display order; if not set then display is random
 * @property {string|null}  radio_group     , if given a non null value, only one thing that share the same text
                                                in the radio group can be used at that level

 * @property {GKA_Kid[]} elements           , zero or more child elements of type GKA_Element, but just the kids here
                                                 error if set for anything but array or object
                                                 to copy an element from another place,
                                                 do an insert or update with the kid of that element
 */



/**
 * Typedef for GKA_DataExample, it inherits from GKA_Root
 * These are owned by GKA_DataGroup
 * @see GKA_Everything
 * @see GKA_DataGroup
 *
 * @typedef {GKA_Root} GKA_DataExample
 * @property {object|null} text            , an example object
 */



/**
 * Typedef for GKA_DataGroup
 * Data Groups have parents of inputs, outputs,headers, use case parts
 * if type database table, then never have a parent
 * @see GKA_Everything
 * @see GKA_Input
 * @see GKA_Output
 * @see GKA_Header
 * @see GKA_Use_Part
 * @see GKA_DataExample
 * @see GKA_Element
 * @typedef {GKA_Root} GKA_DataGroup
 * @property {GKA_Kid[]} examples           , 0 or more GKA_DataExample
 * @property {GKA_Kid[]} elements           , 0 or more GKA_Element
 * @property {string|null} type             , must be empty or database_table|regular; default null means regular
 */


/**
 * Typedef for  GKA_Header
 * headers are what are sent out during api calls, they have parents of api versions, families, outputs and apis
 * @see GKA_Everything
 * @see GKA_Output
 * @see GKA_API
 * @see GKA_Family
 * @see GKA_API_Version
 * @see GKA_DataGroup
 * @typedef {GKA_Root} GKA_Header
 * @property {string} name                  , string header name
 * @property {string} value                 , the contents/value of the header can have regex groups with names
                                                that match the out data group

 * @property {GKA_Kid[] } data_groups       , 0 or more GKA_DataGroup
 */



/**
 * Typedef for GKA_Output, parents are GKA_API
 * @see GKA_Everything
 * @see GKA_API
 * @see GKA_Header
 * @see GKA_DataGroup
 * @typedef {GKA_Root} GKA_Output
 * @property {integer} http_code            , response code between 0 and 600
 * @property {GKA_Kid[]} data_groups        , 0 or more GKA_DataGroup
 * @property {GKA_Kid[]} headers            , 0 or more GKA_Header
 */




/**
 * Typedef GKA_Input, parents are GKA_API
 * @see GKA_Everything
 * @see GKA_API
 * @see GKA_DataGroup
 * @typedef {GKA_Root} GKA_Input
 * @property {string}  origin               , url,query,body,header
 * @property {string|null} properties       , based on origin
                                             url:  the properties is a regex that matches with at least part of the url
                                                    with the names of the regex groups matching elements in data group

                                             query: (no properties), the data_group defines the keys expected
                                                    (the query is the key values after the ?)

                                             body: (no properties), the data_group defines the body expected

                                             header: is a regex matching part of the header with  names of
                                                    the regex groups mapping to the properties of the data group.

 * @property {GKA_Kid[]} data_groups        , 0 or more GKA_DataGroup
 */


/**
 * Type Def for  GKA_API, their parents are families
 * @see GKA_Header
 * @see GKA_Input
 * @see GKA_Output
 * @see GKA_Use_Case
 * @see GKA_Everything
 *  - the http calls that make up the api
 *  they have different inputs, outputs, headers and use cases
 *  @typedef {GKA_Root} GKA_API
 *  @property {string} text                 , this is the name of the api call
 *  @property {string} method               , must be only get|put|post|delete|options|head|patch|trace , default get
 * @property {GKA_Kid[]} inputs             , 0 or more GKA_Input
 * @property {GKA_Kid[]} outputs            , 0 or more GKA_Output
 * @property {GKA_Kid[]} headers            , 0 or more GKA_Header
 * @property {GKA_Kid[]} user_cases         , 0 or more GKA_Use_Case
 */

/**
 * Type def for GKA_Family, parents are api versions
 * @see GKA_Everything
 * @see GKA_API_Version
 * @see GKA_API
 * @see GKA_Header
 * @typedef {GKA_Root} GKA_Family
 * @property {string} text                  , this is the internal name of the family
 * @property {GKA_Kid[]} headers            , 0 or more GKA_Header
 * @property {GKA_Kid[]} apis               , 0 or more GKA_API
 */



/**
 * Type def for GKA_API_Version these are top objects and have no parents
 * @see GKA_Everything
 * @see GKA_Use_Case
 * @see GKA_Header
 * @see GKA_Family
 * @typedef {GKA_Root} GKA_API_Version
 * @property {string} text                  , this is the internal name of the version
 * @property {GKA_Kid[]} headers            , 0 or more GKA_Header
 * @property {GKA_Kid[]} families           , 0 or more GKA_Family
 * @property {GKA_Kid[]} use_cases          , 0 or more GKA_Use_Case
 */



/**
 * Type def for  GKA_SQL_Part, their parents are use case parts
 * the selects are an extra data group output elements
 * the inputs for each section is up to two database groups, and any input element
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Element
 * @typedef {GKA_Root} GKA_SQL_Part
 * @property {string} text                  , describes what the part does, adds details about operations & constants
 * @property {string} sql_part_enum         , select,from,joins,where,limit,offset,ordering
 * @property {GKA_Kid} db_element       , of GKA_Element must be from any data group that is of database type
 * @property {GKA_Kid} reference_db_element       , GKA_Element must be from any data group that is of database type
 * @property {GKA_Kid} outside_element  , GKA_Element must be from a data group belonging to the parent of this SQL
 * @property {integer} rank                 , organizes the statements for display
 */





/**
 * Type Def for GKA_Use_Part_Connection
 * connects two use parts together, and the parent is the use case the parts belong to
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Use_Case
 * @typedef {GKA_Root} GKA_Use_Part_Connection
 * @property {GKA_Kid} source_part          , GKA_Use_Part the outputs come from
 * @property {GKA_Kid} destination_part     , GKA_Use_Part the inputs go to
 * @property {integer} rank                     , optional ranking for display purposes
 */




/**
 * Type Def for  GKA_Use_Part, parent is GKA_Use_Case
 * @see GKA_Everything
 * @see GKA_Use_Part_Connection
 * @see GKA_Use_Case
 * @see GKA_API
 * @see GKA_DataGroup
 * @see GKA_SQL_Part
 * @typedef {GKA_Root} GKA_Use_Part
 * @property {integer} ref_id                   , any number supplied to tag this, needs to be unique for the use case
 * @property {GKA_Kid} in_api               , if the input is an api
 * @property {GKA_Kid[]} in_data_groups         ,0 or 1 input GKA_DataGroup
 * @property {GKA_Kid[]} out_data_groups        , 0 or 1 output GKA_DataGroup
 * @property {GKA_Kid[]} sql_parts              ,  0 or more output GKA_SQL_Part
 * @property {GKA_Kid[]} source_connections     , 0 or more GKA_Use_Part_Connection
 */





/**
 * Type Def for GKA_Use_Case, parent is api or api version
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Use_Part_Connection
 * @see GKA_API
 * @see GKA_API_Version
 * @typedef {GKA_Root} GKA_Use_Case
 * @property {GKA_Kid[]}  use_parts              , 0 or more GKA_Use_Part
 * @property {GKA_Kid[]}  connections              , 0 or more GKA_Use_Part_Connection between the parts
 */





/**
 * Typedef for  GKA_ServerData, read only stuff added by the server on outgoing
 * @see GKA_Everything
 * @typedef {object} GKA_ServerData
 * @property {string} server_time           , human readable time
 * @property {string} server_timezone       , timezone abbreviation
 * @property {integer} server_timestamp     , the timestamp of this response
 * @property {string}  ajax_nonce           , used to refresh the nonce used to talk to wordpress ajax
 */


/**
 * Typedef for GKA_Exception_Info
 * @typedef {object} GKA_Exception_Info
 * @property {string}      hostname             , the name of the machine the exception occurred on
 * @property {string}      machine_id	        , mac address or similar id
 * @property {string}      caller_ip_address	    , the ip address of the browser caller, else will be null
 * @property {string}      branch		        , the git branch
 * @property {string}      last_commit_hash		,  sha1 hash of the last commit made on the code throwing the exception
 * @property {boolean}     is_commit_modified	, true if the code has been changed since the last commit
 * @property {array|null}  argv		            , array of arguments if this is called from the command line
 * @property {string}      request_method		, usually post or get
 * @property {array|null}  post_super           , the post vars, if set
 * @property {array|null}  get_super		    , the get vars, if set
 * @property {array|null}  cookies_super		, array of cookies, if any
 * @property {array}       server_super		    , the server array
 * @property {string}      message		        , the exception message
 * @property {string}      class_of_exception	, the name of the exception class
 * @property {string}      code_of_exception	, exception code
 * @property {string}      file_name		    , the file the exception occurred in
 * @property {string}      line		            , line number in the file of the exception
 * @property {string}      class		        , if the exception occurred inside a class, it will be listed here
 * @property {string}      function_name		, if the exception occurred inside a function, it will be listed here
 * @property {string}      trace_as_string		, the trace in an easier to read string format
 * @property {array|null}  chained		        ,  array of exceptions chained to this one, with the same info as above
 */


/**
 * Type def for GKA_Everything
 * @package gokabam_api
 * @see GKA_Element
 * @see GKA_DataGroup
 * @see GKA_DataExample
 * @see GKA_Header
 * @see GKA_Output
 * @see GKA_Input
 * @see GKA_API
 * @see GKA_Family
 * @see GKA_API_Version
 * @see GKA_Version
 * @see GKA_SQL_Part
 * @see GKA_Use_Part_Connection
 * @see GKA_Use_Part
 * @see GKA_Use_Case
 * @see GKA_Word
 * @see GKA_Tag
 * @see GKA_Journal
 * @see GKA_ServerData
 * @see GKA_Exception_Info
 * @typedef {object} GKA_Everything
 * @property {string} api_action                , update|save|report|get|init
 * @property {integer|null} begin_timestamp          , only used if set
 * @property {integer|null} end_timestamp            , only used if set
 * @property {string}  message                  , talks about the overall operation. Will be 'success' or an error message
 * @property {boolean} is_valid                 , if error then false, else true
 * @property {GKA_Exception_Info|null} exception_info   , if error then this will be filled
 * @property {GKA_Kid[]}  words                 , 0 or more GKA_Word ids
 * @property {GKA_Kid[]}  versions              , 0 or more GKA_Version ids
 * @property {GKA_Kid[]}  journals              , 0 or more GKA_Journal ids
 * @property {GKA_Kid[]}  tags                  , 0 or more GKA_Tag ids
 * @property {GKA_Kid[]}  api_versions              , 0 or more GKA_API_Version ids
 * @property {GKA_Kid[]}  families              , 0 or more GKA_Family ids
 * @property {GKA_Kid[]}  headers              , 0 or more GKA_Header ids
 * @property {GKA_Kid[]}  apis              , 0 or more GKA_API ids
 * @property {GKA_Kid[]}  inputs              , 0 or more GKA_Input ids
 * @property {GKA_Kid[]}  outputs              , 0 or more GKA_Output ids
 * @property {GKA_Kid[]}  use_cases              , 0 or more GKA_Use_Case ids
 * @property {GKA_Kid[]}  use_parts              , 0 or more GKA_Use_Part ids
 * @property {GKA_Kid[]}  use_part_connections              , 0 or more GKA_Use_Part_Connection ids
 * @property {GKA_Kid[]}  sql_parts              , 0 or more GKA_SQL_Part ids
 * @property {GKA_Kid[]}  data_groups              , 0 or more GKA_DataGroup ids (type regular)
 * @property {GKA_Kid[]}  table_groups              , 0 or more GKA_DataGroup ids (type table)
 * @property {GKA_Kid[]}  examples              , 0 or more GKA_DataExample ids
 * @property {GKA_Kid[]}  elements              , 0 or more GKA_Element ids
 * @property {object}     library               , all things are actually defined here, with their id as the property key
 * @property {all}        pass_through_data     , anything the caller wants to put here is passed back without looking at it
 * @property {GKA_ServerData|null} server
 * @property {string[]} deleted_kids        ,objects that were deleted in the time range provided, only if time used
 * @property {GKA_Kid[]} users              , 0 or more  GKA_User
 */



















