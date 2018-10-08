CREATE TRIGGER trigger_after_create_gokabam_api_data_group_examples
  AFTER INSERT ON gokabam_api_data_group_examples
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    #gokabam_api_use_case_parts /  in_data_group_example_id / md5_checksum_examples
    DECLARE a_use_case_part_id INT;
    DECLARE use_case_parts_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts s
                                          WHERE (s.in_data_group_example_id = NEW.id)  ;

      # gokabam_api_data_groups found in group_id of gokabam_api_data_group_examples


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
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_use_case_parts s
      INNER JOIN gokabam_api_data_group_examples e1 ON e1.id = s.in_data_group_example_id
      WHERE s.id = a_use_case_part_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_case_parts s SET md5_checksum_examples = @crc
      WHERE s.id = a_use_case_part_id;

    END LOOP;
    CLOSE use_case_parts_cur;




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

    UPDATE gokabam_api_data_groups s SET md5_checksum_examples = @crc
    WHERE s.id = a_use_case_part_id;




  END