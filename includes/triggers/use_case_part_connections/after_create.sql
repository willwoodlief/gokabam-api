CREATE TRIGGER trigger_after_create_gokabam_api_use_case_part_connections
  AFTER INSERT ON gokabam_api_use_case_part_connections
  FOR EACH ROW
  BEGIN

    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


    #update both the child and the parent
    # the child has the sum totaled for each time its a child or a parent
    # the parent has the sum totaled for each time its a parent or a child

    #do the parent first
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off
    from gokabam_api_use_case_part_connections
    WHERE (parent_use_case_part_id = NEW.parent_use_case_part_id) AND
          (child_use_case_part_id = NEW.parent_use_case_part_id);

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts SET md5_checksum_use_case_connection = @crc
    WHERE id = NEW.parent_use_case_part_id;


    #do the child next
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off
    from gokabam_api_use_case_part_connections
    WHERE (parent_use_case_part_id = NEW.child_use_case_part_id) AND
          (child_use_case_part_id = NEW.child_use_case_part_id);

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts SET md5_checksum_use_case_connection = @crc
    WHERE id = NEW.child_use_case_part_id;


  END