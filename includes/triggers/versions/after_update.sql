CREATE TRIGGER trigger_after_update_gokabam_api_versions
  BEFORE UPDATE ON gokabam_api_versions
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

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_journals_changed);
    END IF;

    SET @edit_log_id := (select last_insert_id());

    IF (NEW.version <> OLD.version) OR (NEW.version IS NULL AND OLD.version IS NOT NULL) OR (NEW.version IS NOT NULL AND OLD.version IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'version',OLD.version);
    END IF;


    IF (NEW.git_commit_id <> OLD.git_commit_id) OR (NEW.git_commit_id IS NULL AND OLD.git_commit_id IS NOT NULL) OR (NEW.git_commit_id IS NOT NULL AND OLD.git_commit_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'git_commit_id',OLD.git_commit_id);
    END IF;

    IF (NEW.git_tag <> OLD.git_tag) OR (NEW.git_tag IS NULL AND OLD.git_tag IS NOT NULL) OR (NEW.git_tag IS NOT NULL AND OLD.git_tag IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'git_tag',OLD.git_tag);
    END IF;

    IF (NEW.post_id <> OLD.post_id) OR (NEW.post_id IS NULL AND OLD.post_id IS NOT NULL) OR (NEW.post_id IS NOT NULL AND OLD.post_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'post_id',OLD.post_id);
    END IF;

    IF (NEW.git_repo_url <> OLD.git_repo_url) OR (NEW.git_repo_url IS NULL AND OLD.git_repo_url IS NOT NULL) OR (NEW.git_repo_url IS NOT NULL AND OLD.git_repo_url IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'git_repo_url',OLD.git_repo_url);
    END IF;

    IF (NEW.website_url <> OLD.website_url) OR (NEW.website_url IS NULL AND OLD.website_url IS NOT NULL) OR (NEW.website_url IS NOT NULL AND OLD.website_url IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'website_url',OLD.website_url);
    END IF;


    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
    END IF;


  END