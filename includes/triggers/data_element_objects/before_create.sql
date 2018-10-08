CREATE TRIGGER trigger_before_create_gokabam_api_data_element_objects
  BEFORE INSERT ON gokabam_api_data_element_objects
  FOR EACH ROW
  BEGIN
    DECLARE maybe_already_used_element INT DEFAULT NULL;

    # element objects should not do recursion
    # if target_data_element_id is not null make sure it does not exist in any description_data_element_id

    # if description_data_element_id is not null make sure it does not exist in any target_data_element_id

    IF NEW.target_data_element_id  IS NOT NULL
    THEN
      SELECT id into maybe_already_used_element
      from gokabam_api_data_element_objects
      where description_data_element_id = NEW.target_data_element_id;

      #dont potential recursion
      IF maybe_already_used_element IS NOT NULL
      THEN
        SET @message := CONCAT('data element is already used as a description, cannot use as target, see element id of : ', maybe_already_used_element);
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      END IF;

      SET @proper_container_id := NULL;
      # check to make sure that the target element data id is array or object
      select id,base_type_enum into @proper_container_id from gokabam_api_data_elements e
      where  e.base_type_enum in ('object','array') AND id = NEW.target_data_element_id;

      IF @proper_container_id IS NULL
      THEN
        SET @message := CONCAT('data element needs to be base_type_enum of array or object, before having its own members. see id of ', NEW.target_data_element_id);
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      END IF;

    END IF;

    SET maybe_already_used_element := NULL;

    IF NEW.description_data_element_id  IS NOT NULL
    THEN
      SELECT id into maybe_already_used_element
      from gokabam_api_data_element_objects
      where target_data_element_id = NEW.description_data_element_id;

      #dont potential recursion
      IF maybe_already_used_element IS NOT NULL
      THEN
        SET @message := CONCAT('data element is already used as a target, cannot use as Description, see element id of : ', maybe_already_used_element);
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = @message;
      END IF;
    END IF;






    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_data_element_objects');
    SET new.object_id := (select last_insert_id());


    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.target_data_element_id,' '),
            coalesce(NEW.description_data_element_id,' '),
            coalesce(NEW.last_page_load_id,' '),
            coalesce(NEW.rank,' '),
            coalesce(NEW.radio_group,' '),
            coalesce(NEW.is_optional,' '),
            coalesce(NEW.is_deleted,' '),
            coalesce(NEW.md5_checksum_tags,' '),
            coalesce(NEW.md5_checksum_words,' ')
        )
    );
  END