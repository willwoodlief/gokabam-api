CREATE TRIGGER trigger_before_create_gokabam_api_use_cases
  BEFORE INSERT ON gokabam_api_use_cases
  FOR EACH ROW
  BEGIN

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_use_cases');
    SET new.object_id := (select last_insert_id());


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.belongs_to_api_id,' '),
            coalesce(NEW.belongs_to_api_version_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_use_case_parts,' '),
            coalesce(NEW.md5_checksum_families,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END