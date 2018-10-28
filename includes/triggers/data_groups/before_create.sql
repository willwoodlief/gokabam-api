CREATE TRIGGER trigger_before_create_gokabam_api_data_groups
  BEFORE INSERT ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;



    if NEW.group_type_enum not in (
      'database_table',
      'regular'
    ) then
      SET @message := CONCAT('INVALID group_type_enum: ', NEW.group_type_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    -- only one parent allowed
    IF (NEW.api_output_id IS NOT NULL) AND (NEW.api_input_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: output and input parent both set';
    end if;

    IF (NEW.api_output_id IS NOT NULL) AND (NEW.header_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: output and header parent both set';
    end if;

    IF (NEW.api_output_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: output and use part parent both set';
    end if;


    IF (NEW.api_input_id IS NOT NULL) AND (NEW.header_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: input and header parent both set';
    end if;

    IF (NEW.api_input_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: input and use part parent both set';
    end if;

    IF (NEW.header_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Data Groups cannot have more than one parent: header and use part parent both set';
    end if;


    -- make sure that if group group_type_enum is type database_table then there is no parent set
    IF NEW.group_type_enum = 'database_table' THEN
      IF ( (NEW.header_id IS NOT NULL) OR
           (NEW.api_input_id IS NOT NULL) OR
           (NEW.use_case_part_id IS NOT NULL) OR
           (NEW.api_output_id IS NOT NULL)
      ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Data Groups cannot have a parent when their type is database_table';
      end if;
    end if;


    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_data_groups');
    SET new.object_id := (select last_insert_id());




    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.md5_checksum_elements,' '),
            coalesce(NEW.md5_checksum_examples,' '),

            coalesce(NEW.group_type_enum,' '),
            coalesce(NEW.is_data_direction_in,' '),
            coalesce(NEW.use_case_part_id,' '),
            coalesce(NEW.header_id,' '),
            coalesce(NEW.api_input_id,' '),
            coalesce(NEW.api_output_id,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END