CREATE TRIGGER trigger_after_create_gokabam_api_data_group_members
  AFTER INSERT ON gokabam_api_data_group_members
  FOR EACH ROW
  BEGIN
    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


    #calculate elements md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_data_group_members  WHERE group_id = NEW.group_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_data_groups SET md5_checksum_group_members = @crc
    WHERE id = NEW.group_id;
  END