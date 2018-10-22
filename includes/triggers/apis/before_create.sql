CREATE TRIGGER trigger_before_create_gokabam_api_apis
  BEFORE INSERT ON gokabam_api_apis
  FOR EACH ROW
  BEGIN

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    if NEW.method_call_enum not in (
      'get','put','post','delete','options','head','patch','trace'
    ) then
      SET @message := CONCAT('INVALID method_call_enum: ', NEW.method_call_enum);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_apis');
    SET new.object_id := (select last_insert_id());


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.method_call_enum,' '),
            coalesce(NEW.api_name,' '),
            coalesce(NEW.md5_checksum_headers,' '),

            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' '),
            coalesce(NEW.md5_checksum_outputs),
            coalesce(NEW.md5_checksum_journals,' ')
        )
    );
  END