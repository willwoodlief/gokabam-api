CREATE TRIGGER trigger_before_create_gokabam_api_journals
  BEFORE INSERT ON gokabam_api_journals
  FOR EACH ROW
  BEGIN
    DECLARE maybe_journal_or_tag_object INT DEFAULT NULL;

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    # test to see if the target is a tag or journal
    SELECT id into maybe_journal_or_tag_object
    from gokabam_api_objects
    where id = NEW.target_object_id and (da_table_name = 'gokabam_api_journals' OR da_table_name = 'gokabam_api_tags' OR da_table_name = 'gokabam_api_words');

    #dont allow Journals on Journals
    IF NEW.target_object_id AND (maybe_journal_or_tag_object IS NOT NULL)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot Have a Journal On Another Tag or Journal or Word object. Nested Journals not allowed ';
    END IF;
    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_journals');
    SET new.object_id := (select last_insert_id());

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.target_object_id,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.entry,' '),
            coalesce(NEW.is_deleted,' ')

        )
    );
  END