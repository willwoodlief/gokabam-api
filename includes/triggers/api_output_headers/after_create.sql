CREATE TRIGGER trigger_after_gokabam_api_output_headers
  AFTER INSERT ON gokabam_api_output_headers
  FOR EACH ROW
  BEGIN

    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


    #gokabam_api_api_versions  md5_checksum_headers
    #gokabam_api_family
    #gokabam_api_apis
    # gokabam_api_outputs

    #### family #####################3
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_output_headers  WHERE api_family_id = NEW.api_family_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_family SET md5_checksum_headers = @crc
    WHERE id = NEW.api_family_id;


    ##### version ###################

    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_output_headers  WHERE api_version_id = NEW.api_version_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_api_versions SET md5_checksum_headers = @crc
    WHERE id = NEW.api_version_id;


    ##### api ###################

    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_output_headers  WHERE api_id = NEW.api_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_apis SET md5_checksum_headers = @crc
    WHERE id = NEW.api_id;



    ##### output ###################

    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_output_headers  WHERE api_output_id = NEW.api_output_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_outputs SET md5_checksum_headers = @crc
    WHERE id = NEW.api_output_id;



  END