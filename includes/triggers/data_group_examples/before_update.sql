CREATE TRIGGER trigger_before_update_gokabam_api_data_group_examples
  BEFORE UPDATE ON gokabam_api_data_group_examples
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.json_example,' '),

            coalesce(NEW.group_id,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END