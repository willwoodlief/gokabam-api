CREATE TRIGGER trigger_after_update_gokabam_api_use_case_part_connections
  BEFORE UPDATE ON gokabam_api_use_case_part_connections
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
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());

    /**
    coalesce(NEW.parent_use_case_part_id,' '),
            coalesce(NEW.child_use_case_part_id,' '),
            coalesce(NEW.rank,' '),
     */


    IF (NEW.parent_use_case_part_id <> OLD.parent_use_case_part_id) OR (NEW.parent_use_case_part_id IS NULL AND OLD.parent_use_case_part_id IS NOT NULL) OR (NEW.parent_use_case_part_id IS NOT NULL AND OLD.parent_use_case_part_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'parent_use_case_part_id',OLD.parent_use_case_part_id);
    END IF;


    IF (NEW.child_use_case_part_id <> OLD.child_use_case_part_id) OR (NEW.child_use_case_part_id IS NULL AND OLD.child_use_case_part_id IS NOT NULL) OR (NEW.child_use_case_part_id IS NOT NULL AND OLD.child_use_case_part_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'child_use_case_part_id',OLD.child_use_case_part_id);
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


    #update both the child and the parent
    # the child has the sum totaled for each time its a child or a parent
    # the parent has the sum totaled for each time its a parent or a child

    #do the parent first
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off
    from gokabam_api_use_case_part_connections
    WHERE (parent_use_case_part_id = NEW.parent_use_case_part_id) AND
          (child_use_case_part_id = NEW.parent_use_case_part_id);

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts SET md5_checksum_use_case_connection = @crc
    WHERE id = NEW.parent_use_case_part_id;


    #do the child next
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off
    from gokabam_api_use_case_part_connections
    WHERE (parent_use_case_part_id = NEW.child_use_case_part_id) AND
          (child_use_case_part_id = NEW.child_use_case_part_id);

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_use_case_parts SET md5_checksum_use_case_connection = @crc
    WHERE id = NEW.child_use_case_part_id;


  END