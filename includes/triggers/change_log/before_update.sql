CREATE TRIGGER trigger_before_update_gokabam_api_change_log
  BEFORE UPDATE ON gokabam_api_change_log
  FOR EACH ROW
  BEGIN
    IF  NOT ((NEW.edit_action = 'insert') OR (NEW.edit_action = 'edit') OR (NEW.edit_action = 'delete'))
    THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'edit_action must be insert or edit or delete ';
    END IF;
  END