CREATE TRIGGER trigger_before_update_gokabam_api_data_elements
  BEFORE UPDATE ON gokabam_api_data_elements
  FOR EACH ROW
  BEGIN

    # don't allow group_id and element_id to be set to non null at the same time
    IF NEW.group_id IS NOT NULL AND NEW.parent_element_id IS NOT NULL
    THEN
      SET @message := CONCAT('group_id and parent_element_id cannot both be set at the same time: ', ' ! :-)');
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #don't allow possible recursion (where an element id is eventually a child of itself)
    # B has parent id of A , A has parent which eventually has parent of B
    # loop through the parents until null is reached or we match our immediate parent again

    IF NEW.parent_element_id IS NOT NULL
    THEN
      set @original_parent_id := NEW.parent_element_id;
      set @parent_id := -1;
      set @last_parent_id = -1;
      set @safety := 0;
      #see if the starting parent id eventually is in the child chain
      # keep looping while parent id is not null, and while it does not match the original
      # don't allow over a hundred levels of nesting
      label1: WHILE (   (@parent_id IS NOT NULL) AND (@parent_id <> @original_parent_id) AND (@safety < 101) ) DO
        IF @parent_id > 0 THEN
          SET @last_parent_id := @parent_id;
        else
          SET @last_parent_id := @original_parent_id;
        end if;

        SET @parent_id := NULL;
        SET @safety := @safety + 1;
        select parent_element_id INTO @parent_id FROM gokabam_api_data_elements WHERE id = @parent_id;
      END WHILE label1;

      IF @parent_id = @original_parent_id
      THEN
        SET @message := CONCAT('recursion detected in data_elements: the parent_element_id of ', NEW.parent_element_id ,' would become a child of itself ');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;

      IF (@parent_id IS NULL) AND (@last_parent_id IS NOT NULL) AND (@safety > 1) THEN
        #reached top and the elements are stacked at least two deep
        set @db_table_id := null;
        select g.id into @db_table_id from gokabam_api_data_elements e
        inner join gokabam_api_data_groups g ON g.id = e.group_id
        WHERE e.id = @last_parent_id AND g.group_type_enum = 'database_table' limit 1;

        IF @db_table_id IS NOT NULL THEN
          SET @message := CONCAT('data groups which are marked as database_table cannot have nested elements: group id of  ',
                                 @db_table_id ,' is marked as such ');
          SIGNAL SQLSTATE '45000'
          SET MESSAGE_TEXT = @message;
        end if;
      end if;
    else
      set @db_table_id := null;
      select g.id into @db_table_id from gokabam_api_data_elements e
                                           inner join gokabam_api_data_groups g ON g.id = e.group_id
      WHERE e.id = NEW.parent_element_id AND g.group_type_enum = 'database_table' limit 1;

      IF @db_table_id IS NOT NULL THEN
        SET @message := CONCAT('data groups which are marked as database_table cannot have nested elements: group id of  ',
                               @db_table_id ,' is marked as such ');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;


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
            coalesce(NEW.rank,' '),
            coalesce(NEW.group_id,' '),
            coalesce(NEW.parent_element_id,' '),
            coalesce(NEW.radio_group,' '),
            coalesce(NEW.is_optional,' '),
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
            coalesce(NEW.md5_checksum_elements,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.md5_checksum_elements,' ')
        )
    );
  END