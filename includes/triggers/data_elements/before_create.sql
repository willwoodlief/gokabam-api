CREATE TRIGGER trigger_before_create_gokabam_api_data_elements
  BEFORE INSERT ON gokabam_api_data_elements
  FOR EACH ROW
  BEGIN

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_data_elements');
    SET new.object_id := (select last_insert_id());


    if NEW.base_type_enum not in (
      'string',
      'integer',
      'number',
      'boolean',
      'object',
      'array'
    ) then
      SET @message := CONCAT('INVALID base_type_enum: ', NEW.base_type_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    if NEW.format_enum not in (
      'date',
      'date-time',
      'password',
      'byte',
      'binary',
      'email',
      'uri',
      'float',
      'double',
      'int32',
      'int8',
      'int64',
      'use_pattern'
    ) then
      SET @message := CONCAT('INVALID format_enum: ', NEW.format_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.is_nullable,' '),
            coalesce(NEW.data_min,' '),
            coalesce(NEW.data_max,' '),
            coalesce(NEW.data_multiple,' '),
            coalesce(NEW.data_precision,' '),
            coalesce(NEW.base_type_enum,' '),
            coalesce(NEW.format_enum,' '),
            coalesce(NEW.pattern,' '),
            coalesce(NEW.data_type_name,' '),
            coalesce(NEW.default_value,' '),
            coalesce(NEW.enum_values,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_element_objects,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END