CREATE TRIGGER trigger_after_create_gokabam_api_versions
  AFTER INSERT ON gokabam_api_versions
  FOR EACH ROW
  BEGIN
    #insert new object id
    UPDATE gokabam_api_objects SET primary_key = NEW.id WHERE id = NEW.object_id;

    INSERT INTO gokabam_api_change_log(target_object_id,page_load_id,touched_page_load_id,edit_action)
    VALUES (NEW.object_id,NEW.last_page_load_id,NEW.touched_page_load_id,'insert');
  END