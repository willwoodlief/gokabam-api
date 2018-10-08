CREATE TRIGGER trigger_before_create_gokabam_api_data_group_members
  BEFORE INSERT ON gokabam_api_data_group_members
  FOR EACH ROW
  BEGIN

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_data_group_members');
    SET new.object_id := (select last_insert_id());


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.rank,' '),
            coalesce(NEW.data_element_id,' '),
            coalesce(NEW.group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END