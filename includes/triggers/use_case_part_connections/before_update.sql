CREATE TRIGGER trigger_before_update_gokabam_api_use_case_part_connections
  BEFORE UPDATE ON gokabam_api_use_case_part_connections
  FOR EACH ROW
  BEGIN

    SET @in_same_group := NULL ;
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
      SET MESSAGE_TEXT = 'Child and Parent Must belong to the same group ';
    END IF;


    #cannot be a child of itself
    if (NEW.child_use_case_part_id = NEW.parent_use_case_part_id)
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Connection Cannot be a child of itself';
    end if;


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