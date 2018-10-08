CREATE TRIGGER trigger_after_update_gokabam_api_data_element_objects
  BEFORE UPDATE ON gokabam_api_data_element_objects
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

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'edit',@has_tags_changed,@has_words_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words)
      VALUES (NEW.object_id,NEW.last_page_load_id,'delete',@has_tags_changed,@has_words_changed);
    END IF;

    SET @edit_log_id := (select last_insert_id());

    IF (NEW.target_data_element_id <> OLD.target_data_element_id) OR (NEW.target_data_element_id IS NULL AND OLD.target_data_element_id IS NOT NULL) OR (NEW.target_data_element_id IS NOT NULL AND OLD.target_data_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'target_data_element_id',OLD.target_data_element_id);
    END IF;

    IF (NEW.description_data_element_id <> OLD.description_data_element_id) OR (NEW.description_data_element_id IS NULL AND OLD.description_data_element_id IS NOT NULL) OR (NEW.description_data_element_id IS NOT NULL AND OLD.description_data_element_id IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'description_data_element_id',OLD.description_data_element_id);
    END IF;

    IF (NEW.rank <> OLD.rank) OR (NEW.rank IS NULL AND OLD.rank IS NOT NULL) OR (NEW.rank IS NOT NULL AND OLD.rank IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'rank',OLD.rank);
    END IF;

    IF (NEW.radio_group <> OLD.radio_group) OR (NEW.radio_group IS NULL AND OLD.radio_group IS NOT NULL) OR (NEW.radio_group IS NOT NULL AND OLD.radio_group IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'radio_group',OLD.radio_group);
    END IF;

    IF (NEW.is_optional <> OLD.is_optional) OR (NEW.is_optional IS NULL AND OLD.is_optional IS NOT NULL) OR (NEW.is_optional IS NOT NULL AND OLD.is_optional IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_optional',OLD.is_optional);
    END IF;


    #calculate elements md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_data_element_objects  WHERE target_data_element_id = NEW.target_data_element_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_data_elements SET md5_checksum_element_objects = @crc
    WHERE id = NEW.target_data_element_id;


  END