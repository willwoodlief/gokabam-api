CREATE TRIGGER trigger_before_update_gokabam_api_data_groups
  BEFORE UPDATE ON gokabam_api_data_groups
  FOR EACH ROW
  BEGIN
    if NEW.group_type_enum not in (
      'database_table',
      'regular'
    ) then
      SET @message := CONCAT('INVALID group_type_enum: ', NEW.group_type_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.md5_checksum_elements,' '),
            coalesce(NEW.md5_checksum_group_members,' '),
            coalesce(NEW.md5_checksum_examples,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.group_type_enum,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END