CREATE TRIGGER trigger_after_update_gokabam_api_apis
  BEFORE UPDATE ON gokabam_api_apis
  FOR EACH ROW
  BEGIN
    DECLARE done INT DEFAULT FALSE;

    DECLARE a_use_case_part_id INT;
    DECLARE use_case_parts_cur CURSOR FOR SELECT DISTINCT s.id FROM gokabam_api_use_case_parts s
                                          WHERE (s.in_api_id = NEW.id) ;


    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

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

    IF (NEW.md5_checksum_headers <> OLD.md5_checksum_headers) OR (NEW.md5_checksum_headers IS NULL AND OLD.md5_checksum_headers IS NOT NULL) OR (NEW.md5_checksum_headers IS NOT NULL AND OLD.md5_checksum_headers IS  NULL)
    THEN
      SET @has_headers_changed := 1;
    ELSE
      SET @has_headers_changed := 0;
    END IF;

    IF (NEW.md5_checksum_inputs <> OLD.md5_checksum_inputs) OR (NEW.md5_checksum_inputs IS NULL AND OLD.md5_checksum_inputs IS NOT NULL) OR (NEW.md5_checksum_inputs IS NOT NULL AND OLD.md5_checksum_inputs IS  NULL)
    THEN
      SET @has_inputs_changed := 1;
    ELSE
      SET @has_inputs_changed := 0;
    END IF;

    IF (NEW.md5_checksum_outputs <> OLD.md5_checksum_outputs) OR (NEW.md5_checksum_outputs IS NULL AND OLD.md5_checksum_outputs IS NOT NULL) OR (NEW.md5_checksum_outputs IS NOT NULL AND OLD.md5_checksum_outputs IS  NULL)
    THEN
      SET @has_outputs_changed := 1;
    ELSE
      SET @has_outputs_changed := 0;
    END IF;

    if NEW.is_deleted = 0 THEN
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_headers,is_inputs,is_outputs,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'edit',@has_tags_changed,@has_words_changed,@has_headers_changed,@has_inputs_changed,@has_outputs_changed,@has_journals_changed);
    ELSE
      INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action,is_tags,is_words,is_headers,is_inputs,is_outputs,is_journals)
      VALUES (NEW.object_id,OLD.last_page_load_id,'delete',@has_tags_changed,@has_words_changed,@has_headers_changed,@has_inputs_changed,@has_outputs_changed,@has_journals_changed);
    END IF;


    SET @edit_log_id := (select last_insert_id());



    IF (NEW.method_call_enum <> OLD.method_call_enum) OR (NEW.method_call_enum IS NULL AND OLD.method_call_enum IS NOT NULL) OR (NEW.method_call_enum IS NOT NULL AND OLD.method_call_enum IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'method_call_enum',OLD.method_call_enum);
    END IF;

    IF (NEW.api_name <> OLD.api_name) OR (NEW.api_name IS NULL AND OLD.api_name IS NOT NULL) OR (NEW.api_name IS NOT NULL AND OLD.api_name IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'api_name',OLD.api_name);
    END IF;

    IF (NEW.is_deleted <> OLD.is_deleted) OR (NEW.is_deleted IS NULL AND OLD.is_deleted IS NOT NULL) OR (NEW.is_deleted IS NOT NULL AND OLD.is_deleted IS  NULL)
    THEN
      INSERT INTO gokabam_api_change_log_edit_history(change_log_id,da_edited_column_name,da_edited_old_column_value)
      VALUES (@edit_log_id,'is_deleted',OLD.is_deleted);
    END IF;


    #   gokabam_api_use_case_parts, in_api_id , md5_checksum_apis
    #  for each use case part find all the apis which include it, do a checksum on their md5 and update the field
    SET done := false;
    OPEN use_case_parts_cur;
    a_loop: LOOP
      FETCH use_case_parts_cur INTO a_use_case_part_id;
      IF done THEN
        LEAVE a_loop;
      END IF;

      set @crc := '';

      SELECT min(
               length(@crc := sha1(concat(
                                     @crc,
                                     sha1(concat_ws('#', e1.md5_checksum)))))
                 ) as discard
          INTO @off
      FROM  gokabam_api_use_case_parts s
              INNER JOIN gokabam_api_apis e1 ON e1.id = s.in_api_id
      WHERE s.id = a_use_case_part_id;


      IF @crc = ''
      THEN
        SET @crc := NULL;
      END IF;

      UPDATE gokabam_api_use_case_parts s SET md5_checksum_apis = @crc
      WHERE s.id = a_use_case_part_id;

    END LOOP;
    CLOSE use_case_parts_cur;

    # gokabam_api_family , (api_family_id here), md5_checksum_apis
    # for the family , find all the apis which include it, do a checksum on their md5 and update the field
    #calculate elements md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_apis  WHERE api_family_id = NEW.api_family_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    UPDATE gokabam_api_family SET md5_checksum_apis = @crc
    WHERE id = NEW.api_family_id;

    #belongs_to_api_id in gokabam_api_use_cases


    UPDATE gokabam_api_use_cases SET md5_checksum_apis = New.md5_checksum
    WHERE belongs_to_api_id = NEW.id;

    IF ((NEW.is_deleted = 1) AND (OLD.is_deleted = 0)) OR ((NEW.is_deleted = 0) AND (OLD.is_deleted = 1)) THEN
      -- update delete status of dependents

      UPDATE gokabam_api_inputs s SET s.is_deleted = NEW.is_deleted WHERE s.api_id = NEW.id;
      UPDATE gokabam_api_outputs s SET s.is_deleted = NEW.is_deleted WHERE s.api_id = NEW.id;
      UPDATE gokabam_api_use_cases s SET s.is_deleted = NEW.is_deleted WHERE s.belongs_to_api_id = NEW.id;

      UPDATE gokabam_api_output_headers s SET s.is_deleted = NEW.is_deleted WHERE s.api_id = NEW.id;

      UPDATE gokabam_api_words SET is_deleted = NEW.is_deleted WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_tags SET is_deleted = NEW.is_deleted WHERE target_object_id = NEW.object_id;
      UPDATE gokabam_api_journals SET is_deleted = NEW.is_deleted WHERE target_object_id = NEW.object_id;
    END IF;


  END