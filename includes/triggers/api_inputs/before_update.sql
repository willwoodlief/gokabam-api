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


    # make sure groups of type database do not make it into here
    IF NEW.in_data_group_id IS NOT NULL THEN
      #make sure it has no nested elements
      SET @ele := NULL;
      select id INTO @ele from gokabam_api_data_groups g
      WHERE g.id = NEW.in_data_group_id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', 'database_table ', '/', NEW.in_data_group_id, ' cannot be in inputs');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.origin_enum,' '),
            coalesce(NEW.regex_string,' '),
            coalesce(NEW.in_data_group_id,' '),
            coalesce(NEW.api_id,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END