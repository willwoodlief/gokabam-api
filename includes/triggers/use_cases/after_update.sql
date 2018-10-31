CREATE TRIGGER trigger_after_update_gokabam_api_use_cases
  BEFORE UPDATE ON gokabam_api_use_cases
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

    IF (NEW.md5_checksum_apis <> OLD.md5_checksum_apis) OR (NEW.md5_checksum_apis IS NULL AND OLD.md5_checksum_apis IS NOT NULL) OR (NEW.md5_checksum_apis IS NOT NULL AND OLD.md5_checksum_apis IS  NULL)
    THEN
      SET @has_api_changed := 1;
    ELSE
      SET @has_api_changed := 0;
    END IF;

    IF (NEW.md5_checksum_use_case_parts <> OLD.md5_checksum_use_case_parts) OR (NEW.md5_checksum_use_case_parts IS NULL AND OLD.md5_checksum_use_case_parts IS NOT NULL) OR (NEW.md5_checksum_use_case_parts IS NOT NULL AND OLD.md5_checksum_use_case_parts IS  NULL)
    THEN
      SET @has_parts_changed := 1;
    ELSE
      SET @has_parts_changed := 0;
    END IF;


    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_families,is_apis,is_use_case_parts,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_family_changed,@has_api_changed,@has_parts_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_families,is_apis,is_use_case_parts,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_family_changed,@has_api_changed,@has_parts_changed,@has_journals_changed);
    END IF;

    SET @edit_log_id := (select last_insert_id());




    IF (NEW.belongs_to_api_id <> OLD.belongs_to_api_id) OR (NEW.belongs_to_api_id IS NULL AND OLD.belongs_to_api_id IS NOT NULL) OR (NEW.belongs_to_api_id IS NOT NULL AND OLD.belongs_to_api_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'belongs_to_api_id',OLD.belongs_to_api_id);
    END IF;

    IF (NEW.belongs_to_api_version_id <> OLD.belongs_to_api_version_id) OR (NEW.belongs_to_api_version_id IS NULL AND OLD.belongs_to_api_version_id IS NOT NULL) OR (NEW.belongs_to_api_version_id IS NOT NULL AND OLD.belongs_to_api_version_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'belongs_to_api_version_id',OLD.belongs_to_api_version_id);
    END IF;


    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_use_case_part_connections s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.use_case_id = NEW.id;
      UPDATE gokabam_api_use_case_parts s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE s.use_case_id = NEW.id;

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1 WHERE target_object_id = NEW.object_id;
    END IF;

  END