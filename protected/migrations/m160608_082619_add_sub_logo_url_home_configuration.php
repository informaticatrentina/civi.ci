<?php

class m160608_082619_add_sub_logo_url_home_configuration extends CDbMigration {
  public function up() {
    $time = time();
    //add configuration to set URL of secondary logo in homeconfig
    $this->execute(
      "INSERT INTO `configuration` (`config_key`, `name`, `value`, `name_key`,`display_order`, `last_modified`, `editor_email`) VALUES ('homeconfig', 'Sub Logo URL', '', 'sub_logo_url', 0, " . $time . ", 'pradeep@incaendo.com')"
    );
  }

  public function down() {
    echo "m160608_082619_add_sub_logo_url_home_configuration does not support migration down.\n";
    return false;
  }
}
