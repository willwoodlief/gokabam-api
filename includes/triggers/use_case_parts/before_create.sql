CREATE TRIGGER trigger_before_create_gokabam_api_use_case_parts
  BEFORE INSERT ON gokabam_api_use_case_parts
  FOR EACH ROW
  BEGIN


    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;



    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_use_case_parts');
    SET new.object_id := (select last_insert_id());


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
            coalesce(NEW.in_api_id,' '),
            coalesce(NEW.rank,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_sql_parts),
            coalesce(NEW.md5_checksum_use_case_connection,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END