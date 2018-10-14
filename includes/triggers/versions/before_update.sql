CREATE TRIGGER trigger_before_update_gokabam_api_versions
  BEFORE UPDATE ON gokabam_api_versions
  FOR EACH ROW
  BEGIN
    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.version,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.git_commit_id,' '),
            coalesce(NEW.git_tag,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END