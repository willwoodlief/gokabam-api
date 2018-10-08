CREATE TRIGGER trigger_before_create_gokabam_api_use_case_parts_sql
  BEFORE INSERT ON gokabam_api_use_case_parts_sql
  FOR EACH ROW
  BEGIN

    if NEW.sql_part_enum not in (
      'select','from','joins','where','limit','offset','ordering'
    ) then
      SET @message := CONCAT('INVALID sql_part_enum: ', NEW.sql_part_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_use_case_parts_sql');
    SET new.object_id := (select last_insert_id());

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.use_case_part_id,' '),
            coalesce(NEW.sql_part_enum,' '),
            coalesce(NEW.table_element_id,' '),
            coalesce(NEW.reference_table_element_id,' '),
            coalesce(NEW.outside_element_id,' '),
            coalesce(NEW.ranking,' '),
            coalesce(NEW.constant_value,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_elements,' '),
            coalesce(NEW.md5_checksum_groups,' ')

        )
    );
  END