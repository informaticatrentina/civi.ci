<?php

class m141130_052632_add_additional_info_field_in_admin_panel extends CDbMigration {

  public function up() {
    $this->execute("INSERT INTO `configuration` (`name` , `value` , `name_key`,
      `display_order`) VALUES ('Getting additional user information. (Use 0 for OFF and 1 for ON)', 
      0, 'additional_information_status', '12')"
    );
    $this->execute("INSERT INTO `configuration` (`name`, `value`, `name_key`, `display_order`)
      VALUES ('QUestion for collecting user data', 'age, gender, education_level, citizenship, work, public_authority ',
      'user_additional_info_question', '13')"
    );
  }

  public function down() {
    echo "m141130_052632_add_additional_info_field_in_admin_panel does not support migration down.\n";
    return false;
  }

}