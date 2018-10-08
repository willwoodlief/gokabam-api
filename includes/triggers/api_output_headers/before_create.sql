CREATE TRIGGER trigger_before_create_gokabam_api_output_headers
  BEFORE INSERT ON gokabam_api_output_headers
  FOR EACH ROW
  BEGIN



    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_output_headers');
    SET new.object_id := (select last_insert_id());


    #calculate md5

    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_output_id,' '),
            coalesce(NEW.api_id,' '),
            coalesce(NEW.api_family_id,' '),
            coalesce(NEW.api_version_id,' '),
            coalesce(NEW.header_name,' '),
            coalesce(NEW.header_value,' '),
            coalesce(NEW.header_value_regex,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' ')
        )
    );
  END



