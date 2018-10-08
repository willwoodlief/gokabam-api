CREATE TRIGGER trigger_before_create_gokabam_api_api_versions
  BEFORE INSERT ON gokabam_api_api_versions
  FOR EACH ROW
  BEGIN

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_api_versions');
    SET new.object_id := (select last_insert_id());


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_version,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_families,' '),
            coalesce(NEW.md5_checksum_headers,' ')
        )
    );
  END