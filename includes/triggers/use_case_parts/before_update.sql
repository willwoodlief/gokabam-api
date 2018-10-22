CREATE TRIGGER trigger_before_update_gokabam_api_use_case_parts
  BEFORE UPDATE ON gokabam_api_use_case_parts
  FOR EACH ROW
  BEGIN

    if (NEW.in_data_group_id IS NOT NULL) AND (NEW.in_api_id IS NOT NULL)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot use both input api and in group';
    end if;

    # make sure groups of type database do not make it into here
    IF NEW.out_data_group_id IS NOT NULL THEN
      #make sure it has no nested elements
      SET @ele := NULL;
      select id INTO @ele from gokabam_api_data_groups g
      WHERE g.id = NEW.out_data_group_id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', 'database_table ', '/', NEW.out_data_group_id, ' cannot be in use cases');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;

    # make sure groups of type database do not make it into here
    IF NEW.in_data_group_id IS NOT NULL THEN
      #make sure it has no nested elements
      SET @ele := NULL;
      select id INTO @ele from gokabam_api_data_groups g
      WHERE g.id = NEW.in_data_group_id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', 'database_table ', '/', NEW.in_data_group_id, ' cannot be in use cases');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;


    if (NEW.use_case_id <> OLD.use_case_id)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot change parent use case once created. Delete this to remove';
    end if;

    SET @maybe_duplicate := NULL ;
    # test to see if duplicate rank
    SELECT id into @maybe_duplicate
    from gokabam_api_use_case_parts
    where use_case_id = NEW.use_case_id and rank = NEW.rank limit 1;

    #dont allow duplicate ranks
    IF @maybe_duplicate IS NOT  NULL
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot have identical ranks in use case parts which belong to same use case ';
    END IF;


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.use_case_id,' '),
            coalesce(NEW.in_data_group_id,' '),
            coalesce(NEW.in_api_id,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.rank,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_sql_parts),
            coalesce(NEW.md5_checksum_use_case_connection,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END