CREATE TRIGGER trigger_after_create_gokabam_api_family
  AFTER INSERT ON gokabam_api_family
  FOR EACH ROW
  BEGIN
    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'insert');


    #calculate elements md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_family  WHERE api_version_id = NEW.api_version_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_api_versions SET md5_checksum_families = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
    WHERE id = NEW.api_version_id;


  END