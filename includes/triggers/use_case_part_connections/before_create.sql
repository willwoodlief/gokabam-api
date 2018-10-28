CREATE TRIGGER trigger_before_create_gokabam_api_use_case_part_connections
  BEFORE INSERT ON gokabam_api_use_case_part_connections
  FOR EACH ROW
  BEGIN


    SET @in_same_group := NULL ;

    if NEW.is_deleted <> 0 then
      SET @message := CONCAT('Delete must be 0 for new objects. It was: ', NEW.is_deleted);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;

    # test to see if child and parent share the same group
    SELECT the_child.id into @in_same_group
    from gokabam_api_use_case_part_connections c
           inner join gokabam_api_use_case_parts the_child on the_child.id = NEW.child_use_case_part_id
           inner join gokabam_api_use_case_parts the_parent on the_parent.id = NEW.parent_use_case_part_id
    where the_child.use_case_id = the_parent.use_case_id limit 1;

    #dont allow duplicate ranks
    IF @in_same_group IS NULL
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Connection Child and Parent Must belong to the same group ';
    END IF;

    #cannot be a child of itself
    if (NEW.child_use_case_part_id = NEW.parent_use_case_part_id)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Connection Cannot be a child of itself';
    end if;

    #insert new object id
    INSERT INTO gokabam_api_objects(da_table_name) values ('gokabam_api_use_case_part_connections');
    SET new.object_id := (select last_insert_id());





    #calculate md5
    SET NEW.md5_checksum := SHA1(
        CONCAT(
            coalesce(NEW.use_case_id,' '),
            coalesce(NEW.parent_use_case_part_id,' '),
            coalesce(NEW.child_use_case_part_id,' '),
            coalesce(NEW.rank,' '),

            coalesce(NEW.md5_checksum_journals,' '),
            coalesce(NEW.is_deleted,' ')
        )
    );
  END