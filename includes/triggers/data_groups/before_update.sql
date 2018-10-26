CREATE TRIGGER trigger_before_update_gokabam_api_data_groups
  BEFORE UPDATE ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN


    -- only one parent allowed
    IF (NEW.api_output_id IS NOT NULL) AND (NEW.api_input_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: output and input parent both set';
    end if;

    IF (NEW.api_output_id IS NOT NULL) AND (NEW.header_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: output and header parent both set';
    end if;

    IF (NEW.api_output_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: output and use part parent both set';
    end if;


    IF (NEW.api_input_id IS NOT NULL) AND (NEW.header_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: input and header parent both set';
    end if;

    IF (NEW.api_input_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: input and use part parent both set';
    end if;

    IF (NEW.header_id IS NOT NULL) AND (NEW.use_case_part_id IS NOT NULL) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Groups cannot have more than one parent: header and use part parent both set';
    end if;



    if NEW.group_type_enum not in (
      'database_table',
      'regular'
    ) then
      SET @message := CONCAT('INVALID group_type_enum: ', NEW.group_type_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    if NEW.group_type_enum = 'database_table' then
      #make sure it has no nested elements
      SET @ele := NULL;
      select b.id INTO @ele from gokabam_api_data_groups g
                         INNER JOIN gokabam_api_data_elements a ON g.id = a.group_id
                         INNER JOIN gokabam_api_data_elements b ON b.parent_element_id = a.id
      WHERE g.id = NEW.id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', NEW.group_type_enum, '/', NEW.id, ' cannot have nested elements');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;

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
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END