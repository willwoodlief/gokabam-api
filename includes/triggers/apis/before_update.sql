CREATE TRIGGER trigger_before_update_gokabam_api_apis
  BEFORE UPDATE ON gokabam_api_apis
  FOR EACH ROW
  BEGIN
    if NEW.method_call_enum not in (
      'get','put','post','delete','options','head','patch','trace'
    ) then
      SET @message := CONCAT('INVALID method_call_enum: ', NEW.method_call_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.method_call_enum,' '),
            coalesce(NEW.api_name,' '),
            coalesce(NEW.md5_checksum_headers,' '),
            coalesce(NEW.md5_checksum_inputs,' '),
            coalesce(NEW.md5_checksum_outputs,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END