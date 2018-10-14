CREATE TRIGGER trigger_after_update_gokabam_api_data_elements
  BEFORE UPDATE ON gokabam_api_data_elements
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    DECLARE a_part_sql_id INT;

    DECLARE parts_sql_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts_sql s
                                     WHERE (s.outside_element_id = NEW.id) OR
                                           (s.table_element_id = NEW.id) OR
                                           (s.reference_table_element_id = NEW.id);

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

    IF (NEW.md5_checksum_journals <> OLD.md5_checksum_journals) OR (NEW.md5_checksum_journals IS NULL AND OLD.md5_checksum_journals IS NOT NULL) OR (NEW.md5_checksum_journals IS NOT NULL AND OLD.md5_checksum_journals IS  NULL)
    THEN
      SET @has_journals_changed := 1;
    ELSE
      SET @has_journals_changed := 0;
    END IF;


    IF (NEW.md5_checksum_elements <> OLD.md5_checksum_elements) OR (NEW.md5_checksum_elements IS NULL AND OLD.md5_checksum_elements IS NOT NULL) OR (NEW.md5_checksum_elements IS NOT NULL AND OLD.md5_checksum_elements IS  NULL)
    THEN
      SET @has_objects_changed := 1;
    ELSE
      SET @has_objects_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());

    IF (NEW.is_nullable <> OLD.is_nullable) OR (NEW.is_nullable IS NULL AND OLD.is_nullable IS NOT NULL) OR (NEW.is_nullable IS NOT NULL AND OLD.is_nullable IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_nullable',OLD.is_nullable);
    END IF;

    IF (NEW.data_min <> OLD.data_min) OR (NEW.data_min IS NULL AND OLD.data_min IS NOT NULL) OR (NEW.data_min IS NOT NULL AND OLD.data_min IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'data_min',OLD.data_min);
    END IF;

    IF (NEW.data_max <> OLD.data_max) OR (NEW.data_max IS NULL AND OLD.data_max IS NOT NULL) OR (NEW.data_max IS NOT NULL AND OLD.data_max IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'data_max',OLD.data_max);
    END IF;

    IF (NEW.data_multiple <> OLD.data_multiple) OR (NEW.data_multiple IS NULL AND OLD.data_multiple IS NOT NULL) OR (NEW.data_multiple IS NOT NULL AND OLD.data_multiple IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'data_multiple',OLD.data_multiple);
    END IF;

    IF (NEW.data_precision <> OLD.data_precision) OR (NEW.data_precision IS NULL AND OLD.data_precision IS NOT NULL) OR (NEW.data_precision IS NOT NULL AND OLD.data_precision IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'data_precision',OLD.data_precision);
    END IF;

    IF (NEW.base_type_enum <> OLD.base_type_enum) OR (NEW.base_type_enum IS NULL AND OLD.base_type_enum IS NOT NULL) OR (NEW.base_type_enum IS NOT NULL AND OLD.base_type_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'base_type_enum',OLD.base_type_enum);
    END IF;

    IF (NEW.format_enum <> OLD.format_enum) OR (NEW.format_enum IS NULL AND OLD.format_enum IS NOT NULL) OR (NEW.format_enum IS NOT NULL AND OLD.format_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'format_enum',OLD.format_enum);
    END IF;

    IF (NEW.pattern <> OLD.pattern) OR (NEW.pattern IS NULL AND OLD.pattern IS NOT NULL) OR (NEW.pattern IS NOT NULL AND OLD.pattern IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'pattern',OLD.pattern);
    END IF;

    IF (NEW.data_type_name <> OLD.data_type_name) OR (NEW.data_type_name IS NULL AND OLD.data_type_name IS NOT NULL) OR (NEW.data_type_name IS NOT NULL AND OLD.data_type_name IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'data_type_name',OLD.data_type_name);
    END IF;

    IF (NEW.default_value <> OLD.default_value) OR (NEW.default_value IS NULL AND OLD.default_value IS NOT NULL) OR (NEW.default_value IS NOT NULL AND OLD.default_value IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'default_value',OLD.default_value);
    END IF;

    IF (NEW.enum_values <> OLD.enum_values) OR (NEW.enum_values IS NULL AND OLD.enum_values IS NOT NULL) OR (NEW.enum_values IS NOT NULL AND OLD.enum_values IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'enum_values',OLD.enum_values);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF (NEW.is_optional <> OLD.is_optional) OR (NEW.is_optional IS NULL AND OLD.is_optional IS NOT NULL) OR (NEW.is_optional IS NOT NULL AND OLD.is_optional IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_optional',OLD.is_optional);
    END IF;

    IF (NEW.radio_group <> OLD.radio_group) OR (NEW.radio_group IS NULL AND OLD.radio_group IS NOT NULL) OR (NEW.radio_group IS NOT NULL AND OLD.radio_group IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'radio_group',OLD.radio_group);
    END IF;

    IF (NEW.rank <> OLD.rank) OR (NEW.rank IS NULL AND OLD.rank IS NOT NULL) OR (NEW.rank IS NOT NULL AND OLD.rank IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'rank',OLD.rank);
    END IF;

    IF (NEW.parent_element_id <> OLD.parent_element_id) OR (NEW.parent_element_id IS NULL AND OLD.parent_element_id IS NOT NULL) OR (NEW.parent_element_id IS NOT NULL AND OLD.parent_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'parent_element_id',OLD.parent_element_id);
    END IF;

    IF (NEW.group_id <> OLD.group_id) OR (NEW.group_id IS NULL AND OLD.group_id IS NOT NULL) OR (NEW.group_id IS NOT NULL AND OLD.group_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'group_id',OLD.group_id);
    END IF;


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

      UPDATE gokabam_api_data_groups g SET md5_checksum_elements = @crc
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

      UPDATE gokabam_api_data_elements g SET md5_checksum_elements = @crc
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

      UPDATE gokabam_api_use_case_parts_sql s SET md5_checksum_elements = @crc
      WHERE s.id = a_part_sql_id;

    END LOOP;
    CLOSE parts_sql_cur;


  END