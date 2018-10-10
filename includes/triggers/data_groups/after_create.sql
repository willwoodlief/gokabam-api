CREATE TRIGGER trigger_after_create_gokabam_api_data_groups
  AFTER INSERT ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    DECLARE a_use_case_part_id INT;
    DECLARE a_output_header_id INT;
    DECLARE a_output_id INT;
    DECLARE a_input_id INT;

    DECLARE use_case_parts_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts s
                                     WHERE (s.in_data_group_id = NEW.id) OR
                                           (s.out_data_group_id = NEW.id) ;


    DECLARE output_header_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_output_headers s
                                          WHERE (s.out_data_group_id = NEW.id)  ;


    DECLARE output_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_outputs s
                                         WHERE (s.out_data_group_id = NEW.id)  ;


    DECLARE input_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_inputs s
                                  WHERE (s.in_data_group_id = NEW.id)  ;


    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;


    #insert new object id, do that first
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');



    #     gokabam_api_use_case_parts
    #  in_data_group_id
    #          out_data_group_id
    #  for each of 0 to many use case parts that use this group, find it, add in all the groups and update
    #         updates md5_checksum_groups

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
                                     sha1(concat_ws('#', e1.md5_checksum,e2.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_use_case_parts s
              LEFT JOIN gokabam_api_data_groups e1 ON e1.id = s.in_data_group_id
              LEFT JOIN gokabam_api_data_groups e2 ON e2.id = s.out_data_group_id

      WHERE s.id = a_use_case_part_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_case_parts s SET md5_checksum_groups = @crc
      WHERE s.id = a_use_case_part_id;

    END LOOP;
    CLOSE use_case_parts_cur;




#     gokabam_api_output_headers
#        out_data_group_id
#       updates md5_checksum_groups

    SET done := false;
    OPEN output_header_cur;
    b_loop: LOOP
      FETCH output_header_cur INTO a_output_header_id;
      IF done THEN
        LEAVE b_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_output_headers s
      INNER JOIN gokabam_api_data_groups e1 ON e1.id = s.out_data_group_id
      WHERE s.id = a_output_header_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_output_headers s SET md5_checksum_groups = @crc
      WHERE s.id = a_output_header_id;

    END LOOP;
    CLOSE output_header_cur;


#     gokabam_api_outputs
#     out_data_group_id
#     md5_checksum_groups


    SET done := false;
    OPEN output_cur;
    c_loop: LOOP
      FETCH output_cur INTO a_output_id;
      IF done THEN
        LEAVE c_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_outputs s
              INNER JOIN gokabam_api_data_groups e1 ON e1.id = s.out_data_group_id
      WHERE s.id = a_output_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_outputs s SET md5_checksum_groups = @crc
      WHERE s.id = a_output_id;

    END LOOP;
    CLOSE output_cur;

#     gokabam_api_inputs
#     in_data_group_id
#     md5_checksum_groups

    SET done := false;
    OPEN input_cur;
    d_loop: LOOP
      FETCH input_cur INTO a_input_id;
      IF done THEN
        LEAVE d_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_inputs s
              INNER JOIN gokabam_api_data_groups e1 ON e1.id = s.in_data_group_id
      WHERE s.id = a_input_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_inputs s SET md5_checksum_groups = @crc
      WHERE s.id = a_input_id;

    END LOOP;
    CLOSE input_cur;





  END