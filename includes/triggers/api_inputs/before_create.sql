CREATE TRIGGER trigger_before_create_gokabam_api_inputs
  BEFORE INSERT ON gokabam_api_inputs
  FOR EACH ROW
  BEGIN

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    if NEW.origin_enum not in (
      'url','query','body','header'
    ) then
      SET @message := CONCAT('INVALID origin_enum: ', NEW.origin_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;


    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_inputs');
    SET new.object_id := (select last_insert_id());


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



