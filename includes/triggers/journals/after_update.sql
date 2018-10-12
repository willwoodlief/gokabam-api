CREATE TRIGGER trigger_after_update_gokabam_api_journals
  BEFORE UPDATE ON gokabam_api_journals
  FOR EACH ROW
  BEGIN

    IF (NEW.md5_checksum_tags <> OLD.md5_checksum_tags) OR (NEW.md5_checksum_tags IS NULL AND OLD.md5_checksum_tags IS NOT NULL) OR (NEW.md5_checksum_tags IS NOT NULL AND OLD.md5_checksum_tags IS  NULL)
    THEN
      SET @has_tags_changed := 1;
    ELSE
      SET @has_tags_changed := 0;
    END IF;


    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,OLD.last_page_load_id,'edit',@has_tags_changed,0);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,OLD.last_page_load_id,'delete',@has_tags_changed,0);
    END IF;

    SET @edit_log_id := (select last_insert_id());

    IF (NEW.entry <> OLD.entry) OR (NEW.entry IS NULL AND OLD.entry IS NOT NULL) OR (NEW.entry IS NOT NULL AND OLD.entry IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'entry',OLD.entry);
    END IF;
  END