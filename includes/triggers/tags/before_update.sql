CREATE TRIGGER trigger_before_update_gokabam_api_tags
  BEFORE UPDATE ON gokabam_api_tags
  FOR EACH ROW
  BEGIN

    DECLARE maybe_tag_object INT DEFAULT NULL;

    # test to see if the target is a tag
    SELECT id into maybe_tag_object
    from gokabam_api_objects
    where id = NEW.target_object_id and (da_table_name = 'gokabam_api_tags' OR da_table_name = 'gokabam_api_words');

    #dont allow tags on tags
    IF NEW.target_object_id AND (maybe_tag_object IS NOT NULL)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot Tag another Tag or Word object. Nested Tags not allowed ';
    END IF;

    #dont allow the target to change
    IF NEW.target_object_id <> OLD.target_object_id
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot Change Tag Target Once Set. Create a new tag instead';
    END IF;


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.target_object_id,' '),
            coalesce(NEW.tag_label,' '),
            coalesce(NEW.tag_value,' ')

        )
    );


  END