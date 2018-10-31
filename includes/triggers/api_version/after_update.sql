CREATE TRIGGER trigger_after_update_gokabam_api_api_versions
  BEFORE UPDATE ON gokabam_api_api_versions
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

    IF (NEW.md5_checksum_families <> OLD.md5_checksum_families) OR (NEW.md5_checksum_families IS NULL AND OLD.md5_checksum_families IS NOT NULL) OR (NEW.md5_checksum_families IS NOT NULL AND OLD.md5_checksum_families IS  NULL)
    THEN
      SET @has_family_changed := 1;
    ELSE
      SET @has_family_changed := 0;
    END IF;

    IF (NEW.md5_checksum_headers <> OLD.md5_checksum_headers) OR (NEW.md5_checksum_headers IS NULL AND OLD.md5_checksum_headers IS NOT NULL) OR (NEW.md5_checksum_headers IS NOT NULL AND OLD.md5_checksum_headers IS  NULL)
    THEN
      SET @has_headers_changed := 1;
    ELSE
      SET @has_headers_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_families,is_headers,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_family_changed,@has_headers_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_families,is_headers,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_family_changed,@has_headers_changed,@has_journals_changed);
    END IF;

    SET @edit_log_id := (select last_insert_id());

    IF (NEW.api_version <> OLD.api_version) OR (NEW.api_version IS NULL AND OLD.api_version IS NOT NULL) OR (NEW.api_version IS NOT NULL AND OLD.api_version IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_version',OLD.api_version);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    #belongs_to_api_id in gokabam_api_use_cases


    UPDATE gokabam_api_use_cases SET md5_checksum_families = New.md5_checksum
    WHERE belongs_to_api_version_id = NEW.id;

    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_family s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.api_version_id = NEW.id;
      UPDATE gokabam_api_use_cases s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.belongs_to_api_version_id = NEW.id;
      UPDATE gokabam_api_output_headers s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.api_version_id = NEW.id;

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
    END IF;

  END