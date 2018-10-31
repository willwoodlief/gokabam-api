CREATE TRIGGER trigger_after_update_gokabam_api_journals
  BEFORE UPDATE ON gokabam_api_journals
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

    IF (NEW.entry <> OLD.entry) OR (NEW.entry IS NULL AND OLD.entry IS NOT NULL) OR (NEW.entry IS NOT NULL AND OLD.entry IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'entry',OLD.entry);
    END IF;

    IF NEW.is_downside_deleted <> 1 THEN
      #calculate tags md5
      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_journals  WHERE target_object_id = NEW.target_object_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      select primary_key, da_table_name INTO local_primary_key,local_table_name from gokabam_api_objects where id = NEW.target_object_id;

      IF local_table_name = 'gokabam_api_versions'
      THEN
        UPDATE gokabam_api_versions SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;


      IF local_table_name = 'gokabam_api_data_elements'
      THEN
        UPDATE gokabam_api_data_elements SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;


      IF local_table_name = 'gokabam_api_data_groups'
      THEN
        UPDATE gokabam_api_data_groups SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;


      IF local_table_name = 'gokabam_api_data_group_examples'
      THEN
        UPDATE gokabam_api_data_group_examples SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_api_versions'
      THEN
        UPDATE gokabam_api_api_versions SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;


      IF local_table_name = 'gokabam_api_family'
      THEN
        UPDATE gokabam_api_family SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_apis'
      THEN
        UPDATE gokabam_api_apis SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_inputs'
      THEN
        UPDATE gokabam_api_inputs SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_outputs'
      THEN
        UPDATE gokabam_api_outputs SET  md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_output_headers'
      THEN
        UPDATE gokabam_api_output_headers SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_use_cases'
      THEN
        UPDATE gokabam_api_use_cases SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_use_case_parts'
      THEN
        UPDATE gokabam_api_use_case_parts SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_use_case_part_connections'
      THEN
        UPDATE gokabam_api_use_case_part_connections SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

      IF local_table_name = 'gokabam_api_use_case_parts_sql'
      THEN
        UPDATE gokabam_api_use_case_parts_sql SET md5_checksum_journals = @crc
        WHERE id = local_primary_key;
      END IF;

    END IF;


    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      
    END IF;


  END