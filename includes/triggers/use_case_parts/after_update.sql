CREATE TRIGGER trigger_after_update_gokabam_api_use_case_parts
  BEFORE UPDATE ON gokabam_api_use_case_parts
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


    IF (NEW.md5_checksum_apis <> OLD.md5_checksum_apis) OR (NEW.md5_checksum_apis IS NULL AND OLD.md5_checksum_apis IS NOT NULL) OR (NEW.md5_checksum_apis IS NOT NULL AND OLD.md5_checksum_apis IS  NULL)
    THEN
      SET @has_api_changed := 1;
    ELSE
      SET @has_api_changed := 0;
    END IF;


    IF (NEW.md5_checksum_sql_parts <> OLD.md5_checksum_sql_parts) OR (NEW.md5_checksum_sql_parts IS NULL AND OLD.md5_checksum_sql_parts IS NOT NULL) OR (NEW.md5_checksum_sql_parts IS NOT NULL AND OLD.md5_checksum_sql_parts IS  NULL)
    THEN
      SET @has_sql_changed := 1;
    ELSE
      SET @has_sql_changed := 0;
    END IF;

    IF (NEW.md5_checksum_use_case_connection <> OLD.md5_checksum_use_case_connection) OR (NEW.md5_checksum_use_case_connection IS NULL AND OLD.md5_checksum_use_case_connection IS NOT NULL) OR (NEW.md5_checksum_use_case_connection IS NOT NULL AND OLD.md5_checksum_use_case_connection IS  NULL)
    THEN
      SET @has_connection_changed := 1;
    ELSE
      SET @has_connection_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_groups,is_apis,is_sql_parts,is_use_case_part_connection,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_groups_changed,@has_api_changed,@has_sql_changed,@has_connection_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action,is_tags,is_words,is_groups,is_apis,is_sql_parts,is_use_case_part_connection,is_journals)
      VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_groups_changed,@has_api_changed,@has_sql_changed,@has_connection_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());


    IF (NEW.use_case_id <> OLD.use_case_id) OR (NEW.use_case_id IS NULL AND OLD.use_case_id IS NOT NULL) OR (NEW.use_case_id IS NOT NULL AND OLD.use_case_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'use_case_id',OLD.use_case_id);
    END IF;


    IF (NEW.in_api_id <> OLD.in_api_id) OR (NEW.in_api_id IS NULL AND OLD.in_api_id IS NOT NULL) OR (NEW.in_api_id IS NOT NULL AND OLD.in_api_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'in_api_id',OLD.in_api_id);
    END IF;



    IF (NEW.rank <> OLD.rank) OR (NEW.rank IS NULL AND OLD.rank IS NOT NULL) OR (NEW.rank IS NOT NULL AND OLD.rank IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'rank',OLD.rank);
    END IF;



    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;

    IF NEW.is_downside_deleted <> 1 THEN

      set @crc := '';

      select min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', md5_checksum)))))
                 ) as discard
          INTO @off from gokabam_api_use_case_parts  WHERE use_case_id = NEW.use_case_id;

      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_cases SET md5_checksum_apis = @crc, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  )))
      WHERE id = NEW.use_case_id;
    END IF;

    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_data_groups s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.use_case_part_id = NEW.id;
      UPDATE gokabam_api_use_case_parts_sql s SET s.is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.use_case_part_id = NEW.id;
      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE target_object_id = NEW.object_id;
    END IF;

    if NEW.is_deleted = 1 AND OLD.is_deleted = 0 then
      -- delete the connection forever
      UPDATE gokabam_api_use_case_part_connections s SET s.is_deleted = 1, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.parent_use_case_part_id = NEW.id;
      UPDATE gokabam_api_use_case_part_connections s SET s.is_deleted = 1, is_downside_deleted = 1, touched_page_load_id = IF(NEW.touched_page_load_id IS  NULL, NEW.last_page_load_id, IF (NEW.last_page_load_id IS NULL , NULL, IF (NEW.touched_page_load_id > NEW.last_page_load_id,NEW.touched_page_load_id,NEW.last_page_load_id  ))) WHERE s.child_use_case_part_id = NEW.id;
    end if;

  END