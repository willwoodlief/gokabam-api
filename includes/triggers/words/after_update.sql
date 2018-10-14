CREATE TRIGGER trigger_after_update_gokabam_api_words
  AFTER UPDATE ON gokabam_api_words
  FOR EACH ROW
  BEGIN
    DECLARE local_primary_key INT DEFAULT NULL;      --  pk of target table
    DECLARE local_table_name VARCHAR(50) DEFAULT NULL;     -- table name of target table

    IF (NEW.md5_checksum_tags <> OLD.md5_checksum_tags) OR (NEW.md5_checksum_tags IS NULL AND OLD.md5_checksum_tags IS NOT NULL) OR (NEW.md5_checksum_tags IS NOT NULL AND OLD.md5_checksum_tags IS  NULL)
    THEN
      SET @has_tags_changed := 1;
    ELSE
      SET @has_tags_changed := 0;
    END IF;


    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,0);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,0);
    END IF;

    SET @edit_log_id := (select last_insert_id());

    IF (NEW.da_words <> OLD.da_words) OR (NEW.da_words IS NULL AND OLD.da_words IS NOT NULL) OR (NEW.da_words IS NOT NULL AND OLD.da_words IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'da_words',OLD.da_words);
    END IF;

    IF (NEW.iso_639_1_language_code <> OLD.iso_639_1_language_code) OR (NEW.iso_639_1_language_code IS NULL AND OLD.iso_639_1_language_code IS NOT NULL) OR (NEW.iso_639_1_language_code IS NOT NULL AND OLD.iso_639_1_language_code IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'iso_639_1_language_code',OLD.iso_639_1_language_code);
    END IF;

    IF (NEW.word_code_enum <> OLD.word_code_enum) OR (NEW.word_code_enum IS NULL AND OLD.word_code_enum IS NOT NULL) OR (NEW.word_code_enum IS NOT NULL AND OLD.word_code_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'word_code_enum',OLD.word_code_enum);
    END IF;


    #calculate tags md5
    set @crc := '';
    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_tags  WHERE target_object_id = NEW.target_object_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    select primary_key, da_table_name INTO local_primary_key,local_table_name from gokabam_api_objects where id = NEW.target_object_id;

    IF local_table_name = 'gokabam_api_versions'
    THEN
      UPDATE gokabam_api_versions SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;



    IF local_table_name = 'gokabam_api_data_elements'
    THEN
      UPDATE gokabam_api_data_elements SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;



    IF local_table_name = 'gokabam_api_data_groups'
    THEN
      UPDATE gokabam_api_data_groups SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;


    IF local_table_name = 'gokabam_api_data_group_examples'
    THEN
      UPDATE gokabam_api_data_group_examples SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_api_versions'
    THEN
      UPDATE gokabam_api_api_versions SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;


    IF local_table_name = 'gokabam_api_family'
    THEN
      UPDATE gokabam_api_family SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_apis'
    THEN
      UPDATE gokabam_api_apis SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_inputs'
    THEN
      UPDATE gokabam_api_inputs SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_outputs'
    THEN
      UPDATE gokabam_api_outputs SET  md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_output_headers'
    THEN
      UPDATE gokabam_api_output_headers SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_cases'
    THEN
      UPDATE gokabam_api_use_cases SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_parts'
    THEN
      UPDATE gokabam_api_use_case_parts SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_part_connections'
    THEN
      UPDATE gokabam_api_use_case_part_connections SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_parts_sql'
    THEN
      UPDATE gokabam_api_use_case_parts_sql SET md5_checksum_words = @crc
      WHERE id = local_primary_key;
    END IF;





  END