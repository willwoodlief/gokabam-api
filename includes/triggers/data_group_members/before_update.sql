CREATE TRIGGER trigger_before_update_gokabam_api_data_group_members
  BEFORE UPDATE ON gokabam_api_data_group_members
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.rank,' '),
            coalesce(NEW.data_element_id,' '),
            coalesce(NEW.group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END