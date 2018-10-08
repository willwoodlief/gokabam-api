CREATE TRIGGER trigger_after_update_gokabam_api_data_group_examples
  BEFORE UPDATE ON gokabam_api_data_group_examples
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    #gokabam_api_use_case_parts /  in_data_group_example_id / md5_checksum_examples
    DECLARE a_use_case_part_id INT;
    DECLARE use_case_parts_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts s
                                          WHERE (s.in_data_group_example_id = NEW.id)  ;

    # gokabam_api_data_groups found in group_id of gokabam_api_data_group_examples


    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    IF (NEW.md5_checksum_tags <> OLD.md5_checksum_tags) OR (NEW.md5_checksum_tags IS NULL AND OLD.md5_checksum_tags IS NOT NULL) OR (NEW.md5_checksum_tags IS NOT NULL AND OLD.md5_checksum_tags IS  NULL)
    THEN
      SET @has_tags_changed := 1;
    ELSE
      SET @has_tags_changed := 0;
    END IF;

    IF (NEW.md5_checksum_words <> OLD.md5_checksum_words) OR (NEW.md5_checksum_words IS NULL AND OLD.md5_checksum_words IS NOT NULL) OR (NEW.md5_checksum_words IS NOT NULL AND OLD.md5_checksum_words IS  NULL)
    THEN
      SET @has_words_changed := 1;
    ELSE
      SET @has_words_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());



    IF (NEW.json_example <> OLD.json_example) OR (NEW.json_example IS NULL AND OLD.json_example IS NOT NULL) OR (NEW.json_example IS NOT NULL AND OLD.json_example IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'json_example',OLD.json_example);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF (NEW.group_id <> OLD.group_id) OR (NEW.group_id IS NULL AND OLD.group_id IS NOT NULL) OR (NEW.group_id IS NOT NULL AND OLD.group_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'group_id',OLD.group_id);
    END IF;



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