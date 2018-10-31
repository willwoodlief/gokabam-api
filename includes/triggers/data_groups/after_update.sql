CREATE TRIGGER trigger_after_update_gokabam_api_data_groups
  BEFORE UPDATE ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN

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
      SET @has_elements_changed := 1;
    ELSE
      SET @has_elements_changed := 0;
    END IF;



    IF (NEW.md5_checksum_examples <> OLD.md5_checksum_examples) OR (NEW.md5_checksum_examples IS NULL AND OLD.md5_checksum_examples IS NOT NULL) OR (NEW.md5_checksum_examples IS NOT NULL AND OLD.md5_checksum_examples IS  NULL)
    THEN
      SET @has_examples_changed := 1;
    ELSE
      SET @has_examples_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_elements,is_examples,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_elements_changed,@has_examples_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_elements,is_examples,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_elements_changed,@has_examples_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());





    IF (NEW.group_type_enum <> OLD.group_type_enum) OR (NEW.group_type_enum IS NULL AND OLD.group_type_enum IS NOT NULL) OR (NEW.group_type_enum IS NOT NULL AND OLD.group_type_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'group_type_enum',OLD.group_type_enum);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF (NEW.is_data_direction_in <> OLD.is_data_direction_in) OR (NEW.is_data_direction_in IS NULL AND OLD.is_data_direction_in IS NOT NULL) OR (NEW.is_data_direction_in IS NOT NULL AND OLD.is_data_direction_in IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_data_direction_in',OLD.is_data_direction_in);
    END IF;

    IF (NEW.header_id <> OLD.header_id) OR (NEW.header_id IS NULL AND OLD.header_id IS NOT NULL) OR (NEW.header_id IS NOT NULL AND OLD.header_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'header_id',OLD.header_id);
    END IF;

    IF (NEW.api_input_id <> OLD.api_input_id) OR (NEW.api_input_id IS NULL AND OLD.api_input_id IS NOT NULL) OR (NEW.api_input_id IS NOT NULL AND OLD.api_input_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_input_id',OLD.api_input_id);
    END IF;

    IF (NEW.api_output_id <> OLD.api_output_id) OR (NEW.api_output_id IS NULL AND OLD.api_output_id IS NOT NULL) OR (NEW.api_output_id IS NOT NULL AND OLD.api_output_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_output_id',OLD.api_output_id);
    END IF;

    IF (NEW.use_case_part_id <> OLD.use_case_part_id) OR (NEW.use_case_part_id IS NULL AND OLD.use_case_part_id IS NOT NULL) OR (NEW.use_case_part_id IS NOT NULL AND OLD.use_case_part_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'use_case_part_id',OLD.use_case_part_id);
    END IF;

    IF NEW.is_downside_deleted <> 1 THEN
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

      UPDATE gokabam_api_use_case_parts s SET md5_checksum_groups = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
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

      UPDATE gokabam_api_output_headers s SET md5_checksum_groups = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
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

      UPDATE gokabam_api_inputs s SET md5_checksum_groups = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
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

      UPDATE gokabam_api_outputs s SET md5_checksum_groups = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
      WHERE s.id = NEW.api_output_id;


      -- -----------------------------------------------------------------------
    END IF;


    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_data_elements s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.group_id = NEW.id;
      UPDATE gokabam_api_data_group_examples s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.group_id = NEW.id;

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
    END IF;

  END