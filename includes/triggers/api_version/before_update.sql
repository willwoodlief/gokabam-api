CREATE TRIGGER trigger_before_update_gokabam_api_api_versions
  BEFORE UPDATE ON gokabam_api_api_versions
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_version,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_families,' '),
            coalesce(NEW.md5_checksum_headers,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END