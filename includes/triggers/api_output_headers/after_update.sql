CREATE TRIGGER trigger_after_update_gokabam_api_output_headers
  BEFORE UPDATE ON gokabam_api_output_headers
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

    IF (NEW.md5_checksum_groups <> OLD.md5_checksum_groups) OR (NEW.md5_checksum_groups IS NULL AND OLD.md5_checksum_groups IS NOT NULL) OR (NEW.md5_checksum_groups IS NOT NULL AND OLD.md5_checksum_groups IS  NULL)
    THEN
      SET @has_groups_changed := 1;
    ELSE
      SET @has_groups_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_groups,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_groups_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_groups,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_groups_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());


    IF (NEW.api_version_id <> OLD.api_version_id) OR (NEW.api_version_id IS NULL AND OLD.api_version_id IS NOT NULL) OR (NEW.api_version_id IS NOT NULL AND OLD.api_version_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_version_id',OLD.api_version_id);
    END IF;

    IF (NEW.api_family_id <> OLD.api_family_id) OR (NEW.api_family_id IS NULL AND OLD.api_family_id IS NOT NULL) OR (NEW.api_family_id IS NOT NULL AND OLD.api_family_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_family_id',OLD.api_family_id);
    END IF;


    IF (NEW.api_id <> OLD.api_id) OR (NEW.api_id IS NULL AND OLD.api_id IS NOT NULL) OR (NEW.api_id IS NOT NULL AND OLD.api_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_id',OLD.api_id);
    END IF;

    IF (NEW.api_output_id <> OLD.api_output_id) OR (NEW.api_output_id IS NULL AND OLD.api_output_id IS NOT NULL) OR (NEW.api_output_id IS NOT NULL AND OLD.api_output_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_output_id',OLD.api_output_id);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF (NEW.header_name <> OLD.header_name) OR (NEW.header_name IS NULL AND OLD.header_name IS NOT NULL) OR (NEW.header_name IS NOT NULL AND OLD.header_name IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'header_name',OLD.header_name);
    END IF;

    IF (NEW.header_value <> OLD.header_value) OR (NEW.header_value IS NULL AND OLD.header_value IS NOT NULL) OR (NEW.header_value IS NOT NULL AND OLD.header_value IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'header_value',OLD.header_value);
    END IF;



    IF NEW.is_downside_deleted <> 1 THEN
      #### family #####################3
      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_output_headers  WHERE api_family_id = NEW.api_family_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_family SET md5_checksum_headers = @crc
      WHERE id = NEW.api_family_id;


      ##### version ###################

      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_output_headers  WHERE api_version_id = NEW.api_version_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_api_versions SET md5_checksum_headers = @crc
      WHERE id = NEW.api_version_id;


      ##### api ###################

      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_output_headers  WHERE api_id = NEW.api_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_apis SET md5_checksum_headers = @crc
      WHERE id = NEW.api_id;



      ##### output ###################

      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_output_headers  WHERE api_output_id = NEW.api_output_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_outputs SET md5_checksum_headers = @crc
      WHERE id = NEW.api_output_id;
    END IF;

    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents
      UPDATE gokabam_api_data_groups s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.header_id = NEW.id;

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
    END IF;

  END