CREATE TRIGGER trigger_before_update_gokabam_api_family
  BEFORE UPDATE ON gokabam_api_family
  FOR EACH ROW
  BEGIN

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.hard_code_family_name,' '),
            coalesce(NEW.md5_checksum_apis,' '),
            coalesce(NEW.md5_checksum_headers,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END