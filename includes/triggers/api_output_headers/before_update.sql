CREATE TRIGGER trigger_before_update_gokabam_api_output_headers
  BEFORE UPDATE ON gokabam_api_output_headers
  FOR EACH ROW
  BEGIN

    # make sure groups of type database do not make it into here
    IF NEW.out_data_group_id IS NOT NULL THEN
      #make sure it has no nested elements
      SET @ele := NULL;
      select id INTO @ele from gokabam_api_data_groups g
      WHERE g.id = NEW.out_data_group_id AND g.group_type_enum = 'database_table' ;

      if @ele IS not NULL THEN
        SET @message := CONCAT('INVALID: group_type_enum: ', 'database_table ', '/', NEW.out_data_group_id, ' cannot be in headers');
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      end if;
    end if;

    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.api_output_id,' '),
            coalesce(NEW.api_id,' '),
            coalesce(NEW.api_family_id,' '),
            coalesce(NEW.api_version_id,' '),
            coalesce(NEW.header_name,' '),
            coalesce(NEW.header_value,' '),
            coalesce(NEW.out_data_group_id,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_groups,' '),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END