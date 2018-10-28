CREATE TRIGGER trigger_before_update_gokabam_api_use_case_parts
  BEFORE UPDATE ON gokabam_api_use_case_parts
  FOR EACH ROW
  BEGIN




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