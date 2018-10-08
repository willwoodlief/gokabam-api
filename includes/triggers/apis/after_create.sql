CREATE TRIGGER trigger_after_create_gokabam_api_apis
  AFTER INSERT ON gokabam_api_apis
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    DECLARE a_use_case_part_id INT;
    DECLARE use_case_parts_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts s
                                          WHERE (s.in_api_id = NEW.id) ;


    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


    #   gokabam_api_use_case_parts, in_api_id , md5_checksum_apis
    #  for each use case part find all the apis which include it, do a checksum on their md5 and update the field
    SET done := false;
    OPEN use_case_parts_cur;
    a_loop: LOOP
      FETCH use_case_parts_cur INTO a_use_case_part_id;
      IF done THEN
        LEAVE a_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_use_case_parts s
      INNER JOIN gokabam_api_apis e1 ON e1.id = s.in_api_id
      WHERE s.id = a_use_case_part_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_case_parts s SET md5_checksum_apis = @crc
      WHERE s.id = a_use_case_part_id;

    END LOOP;
    CLOSE use_case_parts_cur;

    # gokabam_api_family , (api_family_id here), md5_checksum_apis
    # for the family , find all the apis which include it, do a checksum on their md5 and update the field
    #calculate elements md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_apis  WHERE api_family_id = NEW.api_family_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_family SET md5_checksum_apis = @crc
    WHERE id = NEW.api_family_id;


    #belongs_to_api_id in gokabam_api_use_cases


    UPDATE gokabam_api_use_cases SET md5_checksum_apis = New.md5_checksum
    WHERE belongs_to_api_id = NEW.id;

  END