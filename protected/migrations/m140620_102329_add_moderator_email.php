<?php

class m140620_102329_add_moderator_email extends CDbMigration {

  public function up() {
    $this->execute("INSERT INTO `configuration` (`name` , `value` , `name_key` ,
      `display_order`)VALUES ('Moderator\'s email address (Comma seperated email id)', 
      NULL, 'moderators_email', '11')");
  }

  public function down() {
    echo "m140620_102329_add_moderator_email does not support migration down.\n";
    return false;
  }

}