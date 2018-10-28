CREATE TRIGGER trigger_before_update_gokabam_api_journals
  BEFORE UPDATE ON gokabam_api_journals
  FOR EACH ROW
  BEGIN
    DECLARE maybe_journal_or_tag_object INT DEFAULT NULL;

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

    #dont allow the target to change
    IF NEW.target_object_id <> OLD.target_object_id
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot Change Journal Target Once Set. Create a new journal instead';
    END IF;
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