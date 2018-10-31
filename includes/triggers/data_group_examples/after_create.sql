CREATE TRIGGER trigger_after_create_gokabam_api_data_group_examples
  AFTER INSERT ON gokabam_api_data_group_examples
  FOR EACH ROW
  BEGIN

    #insert new object id, do that first
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'insert');



    #find all the examples the group uses, checksum them and update the group


    set @crc := '';

    SELECT min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', s.md5_checksum)))))
               ) as discard
        INTO @off
    FROM  gokabam_api_data_group_examples s
    WHERE s.group_id = NEW.group_id;


    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_data_groups s SET md5_checksum_examples = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
    WHERE s.id = NEW.group_id;




  END