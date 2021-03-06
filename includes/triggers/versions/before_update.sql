CREATE TRIGGER trigger_before_update_gokabam_api_versions
  BEFORE UPDATE ON gokabam_api_versions
  FOR EACH ROW
  BEGIN
    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.version,' '),
            coalesce(NEW.git_commit_id,' '),
            coalesce(NEW.git_tag,' '),
            coalesce(NEW.git_repo_url,' '),
            coalesce(NEW.website_url,' '),
            coalesce(NEW.post_id,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END