CREATE TRIGGER trigger_after_create_gokabam_api_data_elements
  AFTER INSERT ON gokabam_api_data_elements
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    DECLARE a_part_sql_id INT;


    DECLARE parts_sql_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts_sql s
                                 WHERE (s.outside_element_id = NEW.id) OR
                                       (s.table_element_id = NEW.id) OR
                                       (s.reference_table_element_id = NEW.id);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;


    #insert new object id, do that first
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'insert');


    # update the group: if group_id is set get all fellow elements, sum up the checksums, and update the parent group

    IF NEW.group_id IS NOT NULL
    THEN

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_data_elements e
      WHERE group_id = NEW.group_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_data_groups g SET md5_checksum_elements = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
      WHERE g.id = NEW.group_id;

    end if;



    # update the parent element: if parent_element_id is set get all fellow elements,
    #  sum up the checksums, and update the parent element

    IF NEW.parent_element_id IS NOT NULL
    THEN

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_data_elements e
      WHERE parent_element_id = NEW.parent_element_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_data_elements g SET md5_checksum_elements = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
      WHERE g.id = NEW.parent_element_id;

    end if;



    #now do all the sql use case parts that has this element, there are three potential places
    SET done := false;
    OPEN parts_sql_cur;
    b_loop: LOOP
      FETCH parts_sql_cur INTO a_part_sql_id;
      IF done THEN
        LEAVE b_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum,e2.md5_checksum,e3.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_use_case_parts_sql s
      LEFT JOIN gokabam_api_data_elements e1 ON e1.id = s.reference_table_element_id
      LEFT JOIN gokabam_api_data_elements e2 ON e2.id = s.table_element_id
      LEFT JOIN gokabam_api_data_elements e3 ON e3.id = s.outside_element_id
      WHERE s.id = a_part_sql_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_case_parts_sql s SET md5_checksum_elements = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
      WHERE s.id = a_part_sql_id;

    END LOOP;
    CLOSE parts_sql_cur;




  END