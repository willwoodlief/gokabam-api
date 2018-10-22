CREATE TRIGGER trigger_before_update_gokabam_api_use_cases
  BEFORE UPDATE ON gokabam_api_use_cases
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.belongs_to_api_id,' '),
            coalesce(NEW.belongs_to_api_version_id,' '),

            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_use_case_parts,' '),
            coalesce(NEW.md5_checksum_families,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END