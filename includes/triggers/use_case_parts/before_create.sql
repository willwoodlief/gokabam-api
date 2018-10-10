CREATE TRIGGER trigger_before_create_gokabam_api_use_case_parts
  BEFORE INSERT ON gokabam_api_use_case_parts
  FOR EACH ROW
  BEGIN


    if (NEW.in_data_group_id IS NOT NULL) AND (NEW.in_api_id IS NOT NULL)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot use both input api and in group';
    end if;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_use_case_parts');
    SET new.object_id := (select last_insert_id());




    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.use_case_id,' '),
            coalesce(NEW.in_data_group_id,' '),
            coalesce(NEW.in_api_id,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.rank,' '),
            coalesce(NEW.children,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_sql_parts)
        )
    );
  END