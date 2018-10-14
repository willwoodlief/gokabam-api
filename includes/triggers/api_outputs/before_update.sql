CREATE TRIGGER trigger_before_update_gokabam_api_outputs
  BEFORE UPDATE ON gokabam_api_outputs
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_id,' '),
            coalesce(NEW.http_return_code,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_headers,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END