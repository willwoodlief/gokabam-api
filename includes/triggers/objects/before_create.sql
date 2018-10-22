CREATE TRIGGER trigger_before_create_gokabam_api_objects
  BEFORE INSERT ON gokabam_api_objects
  FOR EACH ROW
  BEGIN
    #dont allow bad table names
    if NEW.da_table_name not in (
      'gokabam_api_words',
      'gokabam_api_api_versions',
      'gokabam_api_versions',
      'gokabam_api_apis',
      'gokabam_api_data_elements',
      'gokabam_api_data_group_examples',
      'gokabam_api_data_groups',
      'gokabam_api_family',
      'gokabam_api_history',
      'gokabam_api_inputs',
      'gokabam_api_journals',
      'gokabam_api_output_headers',
      'gokabam_api_outputs',
      'gokabam_api_tags',
      'gokabam_api_use_case_parts',
      'gokabam_api_use_case_parts_sql',
      'gokabam_api_use_case_part_connections',
      'gokabam_api_use_cases'
    ) then
      SET @message := CONCAT('INVALID da_table_name: ', NEW.da_table_name);
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = @message;
    end if;
  END