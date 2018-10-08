CREATE TRIGGER trigger_before_update_gokabam_api_output_headers
  BEFORE UPDATE ON gokabam_api_output_headers
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_output_id,' '),
            coalesce(NEW.api_id,' '),
            coalesce(NEW.api_family_id,' '),
            coalesce(NEW.api_version_id,' '),
            coalesce(NEW.header_name,' '),
            coalesce(NEW.header_value,' '),
            coalesce(NEW.header_value_regex,' '),
            coalesce(NEW.out_data_group_id,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' ')
        )
    );
  END