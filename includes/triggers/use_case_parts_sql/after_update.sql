CREATE TRIGGER trigger_after_update_gokabam_api_use_case_parts_sql
  BEFORE UPDATE ON gokabam_api_use_case_parts_sql
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

    IF (NEW.md5_checksum_elements <> OLD.md5_checksum_elements) OR (NEW.md5_checksum_elements IS NULL AND OLD.md5_checksum_elements IS NOT NULL) OR (NEW.md5_checksum_elements IS NOT NULL AND OLD.md5_checksum_elements IS  NULL)
    THEN
      SET @has_elements_changed := 1;
    ELSE
      SET @has_elements_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_elements)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_elements_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_elements)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_elements_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());




    IF (NEW.use_case_part_id <> OLD.use_case_part_id) OR (NEW.use_case_part_id IS NULL AND OLD.use_case_part_id IS NOT NULL) OR (NEW.use_case_part_id IS NOT NULL AND OLD.use_case_part_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'use_case_part_id',OLD.use_case_part_id);
    END IF;

    IF (NEW.sql_part_enum <> OLD.sql_part_enum) OR (NEW.sql_part_enum IS NULL AND OLD.sql_part_enum IS NOT NULL) OR (NEW.sql_part_enum IS NOT NULL AND OLD.sql_part_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'sql_part_enum',OLD.sql_part_enum);
    END IF;

    IF (NEW.table_element_id <> OLD.table_element_id) OR (NEW.table_element_id IS NULL AND OLD.table_element_id IS NOT NULL) OR (NEW.table_element_id IS NOT NULL AND OLD.table_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'table_element_id',OLD.table_element_id);
    END IF;

    IF (NEW.reference_table_element_id <> OLD.reference_table_element_id) OR (NEW.reference_table_element_id IS NULL AND OLD.reference_table_element_id IS NOT NULL) OR (NEW.reference_table_element_id IS NOT NULL AND OLD.reference_table_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'reference_table_element_id',OLD.reference_table_element_id);
    END IF;

    IF (NEW.outside_element_id <> OLD.outside_element_id) OR (NEW.outside_element_id IS NULL AND OLD.outside_element_id IS NOT NULL) OR (NEW.outside_element_id IS NOT NULL AND OLD.outside_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'outside_element_id',OLD.outside_element_id);
    END IF;

    IF (NEW.ranking <> OLD.ranking) OR (NEW.ranking IS NULL AND OLD.ranking IS NOT NULL) OR (NEW.ranking IS NOT NULL AND OLD.ranking IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'ranking',OLD.ranking);
    END IF;


    IF (NEW.constant_value <> OLD.constant_value) OR (NEW.constant_value IS NULL AND OLD.constant_value IS NOT NULL) OR (NEW.constant_value IS NOT NULL AND OLD.constant_value IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'constant_value',OLD.constant_value);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;


    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_use_case_parts_sql  WHERE use_case_part_id = NEW.use_case_part_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts SET md5_checksum_sql_parts = @crc
    WHERE id = NEW.use_case_part_id;


  END