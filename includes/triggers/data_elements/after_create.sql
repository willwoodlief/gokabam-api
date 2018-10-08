CREATE TRIGGER trigger_after_create_gokabam_api_data_elements
  AFTER INSERT ON gokabam_api_data_elements
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE a_group_id INT;
    DECLARE a_part_sql_id INT;
    DECLARE cur CURSOR FOR SELECT DISTINCT group_id FROM gokabam_api_data_group_members WHERE data_element_id = NEW.id;

    DECLARE parts_sql_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts_sql s
                                 WHERE (s.outside_element_id = NEW.id) OR
                                       (s.table_element_id = NEW.id) OR
                                       (s.reference_table_element_id = NEW.id);

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;


    #insert new object id, do that first
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');


#     gokabam_api_use_case_parts_sql
#     outside_element_id
#     reference_table_element_id
#     table_element_id


    #calculate elements md5
    #0 or more data groups can be using this element
    # get all the data groups members, and for each member checksum all the elements in it
    # and put that one answer into md5_checksum_elements of the group

    OPEN cur;
    ins_loop: LOOP
      FETCH cur INTO a_group_id;
      IF done THEN
        LEAVE ins_loop;
      END IF;

      set @crc := '';

        SELECT min(
                 length(@crc := sha1(concat(
                                       @crc,
                                       sha1(concat_ws('#', e.md5_checksum)))))
                   ) as discard
            INTO @off
        FROM  gokabam_api_data_group_members m
        INNER JOIN gokabam_api_data_elements e ON e.id = m.data_element_id
        WHERE group_id = a_group_id;


        IF @crc = ''
        THEN
          SET @crc := NULL;
        END IF;

        UPDATE gokabam_api_data_groups g SET md5_checksum_elements = @crc
        WHERE g.id = a_group_id;

    END LOOP;
    CLOSE cur;


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

      UPDATE gokabam_api_use_case_parts_sql s SET md5_checksum_elements = @crc
      WHERE s.id = a_part_sql_id;

    END LOOP;
    CLOSE parts_sql_cur;




  END