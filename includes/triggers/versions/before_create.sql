CREATE TRIGGER trigger_before_create_gokabam_api_versions
  BEFORE INSERT ON gokabam_api_versions
  FOR EACH ROW
  BEGIN
    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_versions');
    SET new.object_id := (select last_insert_id());

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.version,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.git_commit_id,' '),
            coalesce(NEW.git_tag,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END