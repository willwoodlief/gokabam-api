CREATE TRIGGER trigger_before_update_gokabam_api_inputs
  BEFORE UPDATE ON gokabam_api_inputs
  FOR EACH ROW
  BEGIN
    if NEW.origin_enum not in (
      'url','query','body','header'
    ) then
      SET @message := CONCAT('INVALID origin_enum: ', NEW.origin_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.origin_enum,' '),
            coalesce(NEW.regex_string,' '),
            coalesce(NEW.api_id,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END