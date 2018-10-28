CREATE TRIGGER trigger_after_create_gokabam_api_tags
  AFTER INSERT ON gokabam_api_tags
  FOR EACH ROW
  BEGIN
    DECLARE local_primary_key INT DEFAULT NULL;      --  pk of target table
    DECLARE local_table_name VARCHAR(50) DEFAULT NULL;     -- table name of target table
    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,'insert');

    #calculate tags md5
    set @crc := '';

    select min(
             length(@crc := sha1(concat(
                                   @crc,
                                   sha1(concat_ws('#', md5_checksum)))))
               ) as discard
        INTO @off from gokabam_api_tags  WHERE target_object_id = NEW.target_object_id;

    IF @crc = ''
    THEN
      SET @crc := NULL;
    END IF;

    select primary_key, da_table_name INTO local_primary_key,local_table_name from gokabam_api_objects where id = NEW.target_object_id;

    IF local_table_name = 'gokabam_api_versions'
    THEN
      UPDATE gokabam_api_versions SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_journals'
    THEN
      UPDATE gokabam_api_journals SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_data_elements'
    THEN
      UPDATE gokabam_api_data_elements SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;


    IF local_table_name = 'gokabam_api_data_groups'
    THEN
      UPDATE gokabam_api_data_groups SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;


    IF local_table_name = 'gokabam_api_data_group_examples'
    THEN
      UPDATE gokabam_api_data_group_examples SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_api_versions'
    THEN
      UPDATE gokabam_api_api_versions SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;


    IF local_table_name = 'gokabam_api_family'
    THEN
      UPDATE gokabam_api_family SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_apis'
    THEN
      UPDATE gokabam_api_apis SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_inputs'
    THEN
      UPDATE gokabam_api_inputs SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_outputs'
    THEN
      UPDATE gokabam_api_outputs SET  md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_output_headers'
    THEN
      UPDATE gokabam_api_output_headers SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_cases'
    THEN
      UPDATE gokabam_api_use_cases SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_parts'
    THEN
      UPDATE gokabam_api_use_case_parts SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_part_connections'
    THEN
      UPDATE gokabam_api_use_case_part_connections SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;

    IF local_table_name = 'gokabam_api_use_case_parts_sql'
    THEN
      UPDATE gokabam_api_use_case_parts_sql SET md5_checksum_tags = @crc
      WHERE id = local_primary_key;
    END IF;




  END