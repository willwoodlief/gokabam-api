
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
 * @property {GKA_Touch|null} initial_touch
 * @property {GKA_Touch|null} recent_touch
 * @property {string}  md5_checksum
 */

class KabamRoot {

    /**
     * @param {GKA_Root}root
     */
    constructor(root) {
        if (root) {
            this.kid = root.kid;
            this.status = root.status;
            this.delete = root.delete;
            this.parent = root.parent;
            this.pass_through = root.pass_through;
            this.words = root.words.slice();
            this.journals = root.journals.slice();
            this.tags = root.tags.slice();
            this.initial_touch = root.initial_touch;
            this.recent_touch = root.recent_touch;
            this.md5_checksum = root.md5_checksum;
        } else {
            this.kid = null;
            this.status = null;
            this.delete = 0;
            this.parent = null;
            this.pass_through = null;
            this.words = [];
            this.journals = [];
            this.tags = [];
            this.initial_touch = null;
            this.recent_touch = null;
            this.md5_checksum = null;
        }
    }

    clean() {
        this.words = [];
        this.journals = [];
        this.tags = [];
    }
}

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



class KabamVersion extends KabamRoot {

    /**
     * @param {GKA_Version|GKA_Root} version
     */
    constructor(version) {

        super(version);
        if (version) {
            this.website_url = version.website_url;
            this.post_id = version.post_id;
            this.git_repo_url = version.git_repo_url;
            this.git_tag = version.git_tag;
            this.git_commit_id = version.git_commit_id;
            this.text = version.text;
        } else {
            this.website_url = null;
            this.post_id = null;
            this.git_repo_url = null;
            this.git_tag = null;
            this.git_commit_id = null;
            this.text = null;
        }
    }

    clean() {
        super.clean();
    }
}

/**
 * Typedef for GKA_Word, it inherits from GKA_Root, the parent can be anything but tags and words
 * @typedef {GKA_Root} GKA_Word
 * @property {string} type
 * @property {string} language
 * @property {string} text
 */

class KabamWord extends KabamRoot {

    /**
     * @param {GKA_Word|GKA_Root} word
     */
    constructor(word) {

        super(word);
        if (word) {
            this.type = word.type;
            this.language = word.language;
            this.text = word.text;
        } else {
            this.type = null;
            this.language = null;
            this.text = null;
        }
    }

    clean() {
        super.clean();
    }
}


/**
 * Typedef for GKA_Journal, it inherits from GKA_Root, the parent can be anything but journals
 * @typedef {GKA_Root} GKA_Journal
 * @property {string} text
 */

class KabamJournal extends KabamRoot {

    /**
     * @param {GKA_Journal|GKA_Root} journal
     */
    constructor(journal) {

        super(journal);
        if (journal) {
            this.text = journal.text;
        } else {
            this.text = null;
        }
    }

    clean() {
        super.clean();
    }
}


/**
 * Typedef for GKA_Tag, it inherits from GKA_Root, the parent can be anything but tags and words
 * @typedef {GKA_Root} GKA_Tag
 * @property {string} text
 * @property {string} value
 */

class KabamTag extends KabamRoot {

    /**
     * @param {GKA_Tag|GKA_Root} tag
     */
    constructor(tag) {

        super(tag);
        if (tag) {
            this.text = tag.text;
            this.value = tag.value;
        } else {
            this.text = null;
            this.value = null;
        }
    }

    clean() {
        super.clean();
    }
}



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

 * @property {integer|null} min             , error if set to non zero for object or boolean
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
 *  @class
 * @augments KabamRoot
 */
class KabamElement extends KabamRoot {

    /**
     *
     * @param {GKA_Element | GKA_Root} element
     */
    constructor(element) {

        super(element);
        if (element) {
            this.text = element.text;
            this.value = element.value;
            this.format = element.format;
            this.pattern = element.pattern;
            this.is_nullable =  element.is_nullable;
            this.is_optional = element.is_optional;
            if (element.enum_values) {
                this.enum_values = element.enum_values.slice();
            } else {
                this.enum_values = [];
            }

            this.default_value = element.default_value;

            this.min = element.min;
            this.max = element.max;
            this.multiple = element.multiple;
            this.precision = element.precision;
            this.rank = element.rank;
            this.radio_group = element.radio_group;
            this.elements = element.elements.slice();

        } else {
            this.text = null;
            this.value = null;
            this.format = null;
            this.pattern = null;
            this.is_nullable =  null;
            this.is_optional = null;
            this.enum_values = [];
            this.default_value = v;
            this.min = null;
            this.max = null;
            this.multiple = null;
            this.precision = null;
            this.rank = null;
            this.radio_group = null;
            this.elements = [];

        }
    }

    clean() {
        super.clean();
        this.elements = [];
    }
}



/**
 * Typedef for GKA_DataExample, it inherits from GKA_Root
 * These are owned by GKA_DataGroup
 * @see GKA_Everything
 * @see GKA_DataGroup
 *
 * @typedef {GKA_Root} GKA_DataExample
 * @property {object|null} text            , an example object
 */

class KabamDataExample extends KabamRoot {

    /**
     * @param {GKA_DataExample|GKA_Root} example
     */
    constructor(example) {

        super(example);
        if (example) {
            this.text = example.text;
        } else {
            this.text = null;
        }
    }

    clean() {
        super.clean();
    }
}



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


class KabamDataGroup extends KabamRoot {

    /**
     * @param {GKA_DataGroup|GKA_Root} group
     */
    constructor(group) {

        super(group);
        if (group) {
            this.type = group.type;
            if (group.examples) {
                this.examples = group.examples.slice();
            } else {
                this.examples = [];
            }

            if (group.elements) {
                this.examples = group.elements.slice();
            } else {
                this.elements = [];
            }
        } else {
            this.type = null;
            this.elements = [];
            this.examples = [];
        }
    }

    clean() {
        super.clean();
        this.elements = [];
        this.examples = [];
    }
}


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


class KabamHeader extends KabamRoot {

    /**
     * @param {GKA_Header|GKA_Root} header
     */
    constructor(header) {

        super(header);
        if (header) {
            this.name = header.name;
            this.value = header.value;
            if (header.data_groups) {
                this.data_groups = header.data_groups.slice();
            } else {
                this.data_groups = [];
            }
        } else {
            this.name = null;
            this.value = null;
            this.data_groups = [];
        }
    }

    clean() {
        super.clean();
        this.data_groups = [];
    }

}

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

class KabamOutput extends KabamRoot {

    /**
     * @param {GKA_Output|GKA_Root} output
     */
    constructor(output) {

        super(output);
        if (output) {
            this.http_code = output.http_code;

            if (output.data_groups) {
                this.data_groups = output.data_groups.slice();
            } else {
                this.data_groups = [];
            }

            if (output.headers) {
                this.headers = output.headers.slice();
            } else {
                this.headers = [];
            }

        } else {
            this.http_code = null;
            this.headers = [];
            this.data_groups = [];
        }
    }

    clean() {
        super.clean();
        this.headers = [];
        this.data_groups = [];
    }
}


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



class KabamInput extends KabamRoot {

    /**
     * @param {GKA_Input|GKA_Root} input
     */
    constructor(input) {

        super(input);
        if (input) {
            this.origin = input.origin;
            this.properties = input.properties;


            if (input.data_groups) {
                this.data_groups = input.data_groups.slice();
            } else {
                this.data_groups = [];
            }

        } else {
            this.origin = null;
            this.properties = null;
            this.data_groups = [];
        }
    }

    clean() {
        super.clean();
        this.data_groups = [];
    }
}



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
 * @property {GKA_Kid[]} use_cases         , 0 or more GKA_Use_Case
 */


class KabamApi extends KabamRoot {

    /**
     * @param {GKA_API|GKA_Root} api
     */
    constructor(api) {

        super(api);
        if (api) {
            this.text = api.text;
            this.method = api.method;

            if (api.inputs) {
                this.inputs = api.inputs.slice();
            } else {
                this.inputs = [];
            }

            if (api.outputs) {
                this.outputs = api.outputs.slice();
            } else {
                this.outputs = [];
            }

            if (api.use_cases) {
                this.use_cases = api.use_cases.slice();
            } else {
                this.use_cases = [];
            }

            if (api.headers) {
                this.headers = api.headers.slice();
            } else {
                this.headers = [];
            }

        } else {
            this.text = null;
            this.method = null;
            this.inputs = [];
            this.outputs = [];
            this.headers = [];
            this.use_cases = [];
        }
    }

    clean() {
        super.clean();
        this.inputs = [];
        this.outputs = [];
        this.headers = [];
        this.use_cases = [];
    }
}



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


class KabamFamily extends KabamRoot {

    /**
     * @param {GKA_Family|GKA_Root} family
     */
    constructor(family) {

        super(family);
        if (family) {
            this.text = family.text;

            if (family.apis) {
                this.apis = family.apis.slice();
            } else {
                this.apis = [];
            }

            if (family.headers) {
                this.headers = family.headers.slice();
            } else {
                this.headers = [];
            }

        } else {
            this.text = null;
            this.apis = [];
            this.headers = [];

        }
    }

    clean() {
        super.clean();
        this.apis = [];
        this.headers = [];
    }
}


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


class KabamApiVersion extends KabamRoot {

    /**
     * @param {GKA_API_Version|GKA_Root} api_version
     */
    constructor(api_version) {

        super(api_version);
        if (api_version) {
            this.text = api_version.text;

            if (api_version.families) {
                this.families = api_version.families.slice();
            } else {
                this.families = [];
            }

            if (api_version.headers) {
                this.headers = api_version.headers.slice();
            } else {
                this.headers = [];
            }

            if (api_version.use_cases) {
                this.use_cases = api_version.use_cases.slice();
            } else {
                this.use_cases = [];
            }

        } else {
            this.text = null;
            this.families = [];
            this.headers = [];
            this.use_cases = [];
        }
    }

    clean() {
        super.clean();
        this.families = [];
        this.headers = [];
        this.use_cases = [];
    }
}

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


class KabamSqlPart extends KabamRoot {

    /**
     * @param {GKA_SQL_Part|GKA_Root} sql_part
     */
    constructor(sql_part) {

        super(sql_part);
        if (sql_part) {
            this.text = sql_part.text;
            this.sql_part_enum = sql_part.sql_part_enum;
            this.db_element = sql_part.db_element;
            this.reference_db_element = sql_part.reference_db_element;
            this.outside_element = sql_part.outside_element;
            this.rank = sql_part.rank;

        } else {
            this.text = null;
            this.sql_part_enum = null;
            this.db_element = null;
            this.reference_db_element =null;
            this.outside_element = null;
            this.rank = null;
        }
    }

    clean() {
        super.clean();
    }
}


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


class KabamPartConnection extends KabamRoot {

    /**
     * @param {GKA_Use_Part_Connection|GKA_Root} sql_connection
     */
    constructor(sql_connection) {

        super(sql_connection);
        if (sql_connection) {
            this.source_part = sql_connection.source_part;
            this.destination_part = sql_connection.destination_part;
            this.rank = sql_connection.rank;


        } else {
            this.source_part = null;
            this.destination_part = null;
            this.rank = null;
        }
    }

    clean() {
        super.clean();
    }
}

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



class KabamUsePart extends KabamRoot {

    /**
     * @param {GKA_Use_Part|GKA_Root} use_part
     */
    constructor(use_part) {

        super(use_part);
        if (use_part) {
            this.ref_id = use_part.ref_id;
            this.in_api = use_part.in_api;

            if (use_part.in_data_groups) {
                this.in_data_groups = use_part.in_data_groups.slice();
            } else {
                this.in_data_groups = [];
            }

            if (use_part.out_data_groups) {
                this.out_data_groups = use_part.out_data_groups.slice();
            } else {
                this.out_data_groups = [];
            }

            if (use_part.sql_parts) {
                this.sql_parts = use_part.sql_parts.slice();
            } else {
                this.sql_parts = [];
            }

            if (use_part.source_connections) {
                this.source_connections = use_part.source_connections.slice();
            } else {
                this.source_connections = [];
            }

        } else {
            this.ref_id = null;
            this.in_api = null;

        }
    }

    clean() {
        super.clean();
        this.in_data_groups = [];
        this.out_data_groups = [];
        this.sql_parts = [];
        this.source_connections = [];
    }
}



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


class KabamUseCase extends KabamRoot {

    /**
     * @param {GKA_Use_Case|GKA_Root} use_case
     */
    constructor(use_case) {

        super(use_case);
        if (use_case) {

            if (use_case.use_parts) {
                this.use_parts = use_case.use_parts.slice();
            } else {
                this.use_parts = [];
            }

            if (use_case.connections) {
                this.connections = use_case.connections.slice();
            } else {
                this.connections = [];
            }

        } else {

            this.use_parts = [];
            this.connections = [];
        }
    }

    clean() {
        super.clean();
        this.use_parts = [];
        this.connections = [];
    }
}


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


/**
 * Everything
 * @class
 * @param {GKA_Everything|KabamEverything} everything
 */
function KabamEverything(everything)  {


    if (everything) {

        this.server = jQuery.extend(true, {}, everything.server);
        this.pass_through_data = everything.pass_through_data;
        this.api_action = everything.api_action;
        this.begin_timestamp = everything.begin_timestamp;
        this.end_timestamp = everything.end_timestamp;
        this.message = everything.message;
        this.is_valid = everything.is_valid;
        this.exception_info = everything.exception_info;
        this.deleted_kids = everything.deleted_kids.slice();

        this.library = {};

        let props = [
                        'words',
                        'tags',
                        'journals',
                        'versions',
                        'api_versions',
                        'families',
                        'apis',
                        'headers',
                        'inputs',
                        'outputs',
                        'sql_parts',
                        'use_part_connections',
                        'use_parts',
                        'use_cases',
                        'data_groups',
                        'table_groups',
                        'examples',
                        'elements',
                        'users'
        ];



        for(let prop_index = 0; prop_index < props.length; prop_index++ ) {
            let prop = props[prop_index];
            this[prop] = [];
            for(let i = 0; i < everything[prop].length; i++) {
                let kid = everything[prop][i];
                if (!everything.library.hasOwnProperty(kid)) {throw new Error("Everything has a reference of "+ kid + " in "+ prop +" that is not in library")}
                let oh = everything.library[kid];
                let node = null;
                switch(prop) {
                    case 'words': {
                        node = new KabamWord(oh);
                        break;
                    }
                    case 'tags': {
                        node =  new KabamTag(oh);
                        break;
                    }
                    case 'journals': {
                        node =  new KabamJournal(oh);
                        break;
                    }
                    case 'versions': {
                        node =  new KabamVersion(oh);
                        break;
                    }
                    case 'api_versions': {
                        node =  new KabamApiVersion(oh);
                        break;
                    }
                    case 'families': {
                        node =  new KabamFamily(oh);
                        break;
                    }
                    case 'apis': {
                        node =  new KabamApi(oh);
                        break;
                    }
                    case 'headers': {
                        node =  new KabamHeader(oh);
                        break;
                    }
                    case 'inputs': {
                        node =  new KabamInput(oh);
                        break;
                    }
                    case 'outputs': {
                        node =  new KabamOutput(oh);
                        break;
                    }
                    case 'sql_parts': {
                        node =  new KabamSqlPart(oh);
                        break;
                    }
                    case 'use_part_connections': {
                        node =  new KabamPartConnection(oh);
                        break;
                    }
                    case 'use_parts': {
                        node =  new KabamUsePart(oh);
                        break;
                    }
                    case 'use_cases': {
                        node =  new KabamUseCase(oh);
                        break;
                    }
                    case 'data_groups': {
                        node =  new KabamDataGroup(oh);
                        break;
                    }
                    case 'table_groups': {
                        node =  new KabamDataGroup(oh);
                        break;
                    }
                    case  'examples': {
                        node =  new KabamDataExample(oh);
                        break;
                    }
                    case 'elements': {
                        node =  new KabamElement(oh);
                        break;
                    }
                    case  'users': {
                        node =  oh; //no class as we never write anything with it
                        break;
                    }
                    default: {
                        throw new Error("Case does not include what is in property array");
                    }
                }

                this.library[kid] = node;
                this[prop].push(kid);
            }
        }


    }
    else {

        this.server = null;
        this.pass_through_data = null;
        this.api_action = null;
        this.begin_timestamp = null;
        this.end_timestamp = null;
        this.message = null;
        this.is_valid = null;
        this.exception_info = null;


        this.words = [] ;
        this.tags = [] ;
        this.journals = [] ;
        this.versions = [] ;
        this.api_versions = [] ;
        this.families = [] ;
        this.apis = [] ;
        this.headers = [] ;
        this.inputs = [] ;
        this.outputs = [] ;
        this.sql_parts = [] ;
        this.use_part_connections = [] ;
        this.use_parts = [] ;
        this.use_cases = [] ;
        this.data_groups = [] ;
        this.table_groups = [] ;
        this.examples = [] ;
        this.elements = [] ;
        this.library = {} ;
        this.deleted_kids = [] ;
        this.users = [] ;
    }


    this.remember_changed_deleted = [];
    this.remember_changed_inserted = [];
    this.remember_changed_updated = [];

    /**
     * returns an array of ids that were deleted after this data was cached
     * if null passed will not recompute, but return previous answer
     * @param {KabamEverything|null} other
     * @return {GKA_Kid[]}
     */
    this.get_changed_deleted = function(other) {

        if (other == null) {
            return this.remember_changed_deleted;
        }

        //see if the other's delete list has items that are not deleted here
        // do not count other deleted items that are not know about

        //get the intersection of other's delete list and array of this kids
        let other_delete_list = other.deleted_kids;
        let our_existing_list = [];
        for(let kid in  this.library ) {
            if (this.library.hasOwnProperty(kid)) {
                our_existing_list.push(kid);
            }
        }
        let intersection = other_delete_list.filter(x => our_existing_list.includes(x));
        this.remember_changed_deleted = intersection;
        return intersection;
    };

    /**
     * returns an array of ids that were inserted after this data was cached
     * if null passed will not recompute, but return previous answer
     * @param {KabamEverything|null}  other
     * @return {GKA_Kid[]}
     */
    this.get_changed_inserted = function(other) {
        if (other == null) {
            return this.remember_changed_inserted;
        }

        //get the array of kids from the other library, get our array of kids here,
        // and return the kids that are in the other but not in this


        let our_list = [];
        for(let kid in  this.library ) {
            if (this.library.hasOwnProperty(kid)) {
                our_list.push(kid);
            }
        }

        let their_list = [];
        for(let kid in  other.library ) {
            if (other.library.hasOwnProperty(kid)) {
                their_list.push(kid);
            }
        }

        let in_their_list_but_not_ours = their_list.filter(x => !our_list.includes(x));
        this.remember_changed_inserted = in_their_list_but_not_ours;
        return in_their_list_but_not_ours;
    };

    /**
     * returns an array of ids that were changed (md5 difference) after this data was cached
     * if null passed will not recompute, but return previous answer
     * @param {KabamEverything|null} other
     * @return {GKA_Kid[]}
     */
    this.get_changed_updated = function(other) {
        if (other == null) {
            return this.remember_changed_updated;
        }

        //get the intersection of the other's library and this library
        //for each thing in common, compare the md5 and put in list if different

        let our_list = [];
        for(let kid in  this.library ) {
            if (this.library.hasOwnProperty(kid)) {
                our_list.push(kid);
            }
        }

        let their_list = [];
        for(let kid in  other.library ) {
            if (other.library.hasOwnProperty(kid)) {
                their_list.push(kid);
            }
        }

        this.remember_changed_updated = [];
        let intersection = our_list.filter(x => their_list.includes(x));
        for(let i = 0; i < intersection.length; i++) {
            let kid = intersection[i];
            let our_md5 = this.library[kid].md5_checksum;
            let their_md5 = other.library[kid].md5_checksum;
            if (our_md5 !== their_md5) {
                this.remember_changed_updated.push(kid);
            }
        }

        return this.remember_changed_updated;

    };

    /**
     * Woo needs to be derived from KabamRoot or should implement the user property
     * returns the copy made
     * @param {*} woo
     * @param {boolean} b_twist, default false
     * @return {*}
     */
    this.add_root = function(woo,b_twist) {
        if (!woo) {return null;}
        if (!b_twist) {b_twist = false;}
        if (this.library.hasOwnProperty(woo.kid)) {
            throw new Error("Cannot add " + woo.kid + " as its already in the library");
        }
        let ret = null;
        let what_the_hell_is_this = woo.constructor.name;
        switch (what_the_hell_is_this) {
            case 'KabamWord': {
                ret = new KabamWord(woo);
                if (b_twist) {
                    this.words.push(ret);
                } else {
                    this.words.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;
            }
            case 'KabamTag': {
                ret = new KabamTag(woo);
                if (b_twist) {
                    this.tags.push(ret);
                } else {
                    this.tags.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamJournal': {
                ret = new KabamJournal(woo);
                if (b_twist) {
                    this.journals.push(ret);
                } else {
                    this.journals.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;
            }
            case 'KabamVersion': {
                ret = new KabamVersion(woo);
                if (b_twist) {
                    this.versions.push(ret);
                } else {
                    this.versions.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;
            }
            case 'KabamApiVersion': {
                ret = new KabamApiVersion(woo);
                if (b_twist) {
                    this.api_versions.push(ret);
                } else {
                    this.api_versions.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;
            }
            case 'KabamFamily': {
                ret = new KabamFamily(woo);
                if (b_twist) {
                    this.families.push(ret);
                } else {
                    this.families.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamApi': {
                ret = new KabamApi(woo);
                if (b_twist) {
                    this.apis.push(ret);
                } else {
                    this.apis.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamHeader': {
                ret = new KabamHeader(woo);
                if (b_twist) {
                    this.headers.push(ret);
                } else {
                    this.headers.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamInput': {
                ret = new KabamInput(woo);
                if (b_twist) {
                    this.inputs.push(ret);
                } else {
                    this.inputs.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamOutput': {
                ret = new KabamOutput(woo);
                if (b_twist) {
                    this.outputs.push(ret);
                } else {
                    this.outputs.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamSqlPart': {
                ret = new KabamSqlPart(woo);
                if (b_twist) {
                    this.sql_parts.push(ret);
                } else {
                    this.sql_parts.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamPartConnection': {
                ret = new KabamPartConnection(woo);
                if (b_twist) {
                    this.use_part_connections.push(ret);
                } else {
                    this.use_part_connections.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamUsePart': {
                ret = new KabamUsePart(woo);
                if (b_twist) {
                    this.use_parts.push(ret);
                } else {
                    this.use_parts.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;
            }
            case 'KabamUseCase': {
                ret = new KabamUseCase(woo);
                if (b_twist) {
                    this.use_cases.push(ret);
                } else {
                    this.use_cases.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamDataGroup': {
                ret = new KabamDataGroup(woo);
                if (b_twist) {
                    if (woo.type === 'database_table') {
                        this.table_groups.push(ret);
                    } else {
                        this.data_groups.push(ret);
                    }
                } else {
                    if (woo.type === 'database_table') {
                        this.table_groups.push(woo.kid);
                    } else {
                        this.data_groups.push(woo.kid);
                    }
                    this.library[woo.kid] = ret;
                }

                break;

            }

            case  'KabamDataExample': {
                ret = new KabamDataExample(woo);
                if (b_twist) {
                    this.examples.push(ret);
                } else {
                    this.examples.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }
            case 'KabamElement': {
                ret = new KabamElement(woo);
                if (b_twist) {
                    this.elements.push(ret);
                } else {
                    this.elements.push(woo.kid);
                    this.library[woo.kid] = ret;
                }

                break;

            }

            default: {
                //check to see if object is a user by if property is set, as it does not have a defined class
                //but only if not twisting, else it makes no sense to add it that way, as the server never writes from there
                if (!b_twist) {
                    if (woo.hasOwnProperty('user_name')) {
                        //okay, its a user
                        this.users.push(woo.user_id); // users do not have the same structure
                        ret = this.library[woo.kid] = jQuery.extend(true, {}, woo);
                        break;
                    }
                }

                //else throw exception
                throw new Error("Add to Everything: case does not include the object being added. That was " + what_the_hell_is_this);
            }
        }
        return ret;
    }

}


