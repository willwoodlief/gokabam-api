CREATE TRIGGER trigger_before_create_gokabam_api_words
  BEFORE INSERT ON gokabam_api_words
  FOR EACH ROW
  BEGIN

    DECLARE maybe_tag_object INT DEFAULT NULL;

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    if NEW.word_code_enum not in (
      'name',
      'title',
      'blurb',
      'description',
      'overview',
      'data'
    ) then
      SET @message := CONCAT('INVALID word_code_enum: ', NEW.word_code_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    # test to see if the target is a tag
    SELECT id into maybe_tag_object
    from gokabam_api_objects
    where id = NEW.target_object_id and (da_table_name = 'gokabam_api_tags' OR da_table_name = 'gokabam_api_words');

    #dont allow tags on tags
    IF NEW.target_object_id AND (maybe_tag_object IS NOT NULL)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot Add Words to another Word or Tag object. Nested Words not allowed ';
    END IF;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_words');
    SET new.object_id := (select last_insert_id());

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.target_object_id,' '),
            coalesce(NEW.da_words,' '),
            coalesce(NEW.word_code_enum,' '),
            coalesce(NEW.iso_639_1_language_code,' ')

        )
    );


  END