CREATE TRIGGER trigger_before_create_gokabam_api_outputs
  BEFORE INSERT ON gokabam_api_outputs
  FOR EACH ROW
  BEGIN


    # make sure groups of type database do not make it into here
    IF NEW.out_data_group_id IS NOT NULL THEN
      #make sure it has no nested elements
      SET @ele := NULL;
      select id INTO @ele from gokabam_api_data_groups g
      WHERE g.id = NEW.out_data_group_id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', 'database_table ', '/', NEW.out_data_group_id, ' cannot be in outputs');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_outputs');
    SET new.object_id := (select last_insert_id());


    #calculate md5


    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_id,' '),
            coalesce(NEW.http_return_code,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_headers,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END



