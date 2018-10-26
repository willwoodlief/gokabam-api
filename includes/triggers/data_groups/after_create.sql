CREATE TRIGGER trigger_after_create_gokabam_api_data_groups
  AFTER INSERT ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN



    #insert new object id, do that first
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


    -- ----------------------------------------------------------------------

    set @crc := '';

    SELECT min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', s.md5_checksum)))))
               ) as discard
        INTO @off
    FROM  gokabam_api_data_groups s
    WHERE s.use_case_part_id = NEW.use_case_part_id;


    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts s SET md5_checksum_groups = @crc
    WHERE s.id = NEW.use_case_part_id;


    -- -----------------------------------------------------------------------

    set @crc := '';

    SELECT min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', s.md5_checksum)))))
               ) as discard
        INTO @off
    FROM  gokabam_api_data_groups s
    WHERE s.header_id = NEW.header_id;


    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_output_headers s SET md5_checksum_groups = @crc
    WHERE s.id = NEW.header_id;


    -- -----------------------------------------------------------------------

    set @crc := '';

    SELECT min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', s.md5_checksum)))))
               ) as discard
        INTO @off
    FROM  gokabam_api_data_groups s
    WHERE s.api_input_id = NEW.api_input_id;


    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_inputs s SET md5_checksum_groups = @crc
    WHERE s.id = NEW.api_input_id;


    -- -----------------------------------------------------------------------


    set @crc := '';

    SELECT min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', s.md5_checksum)))))
               ) as discard
        INTO @off
    FROM  gokabam_api_data_groups s
    WHERE s.api_output_id = NEW.api_output_id;


    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_outputs s SET md5_checksum_groups = @crc
    WHERE s.id = NEW.api_output_id;


    -- -----------------------------------------------------------------------




  END