<?php

class m131227_084334_insert_configuration extends CDbMigration {

  public function up() {
    $this->execute("INSERT INTO `configuration` (`name` , `value` , `name_key` ,
      `display_order`)VALUES ('Threshold value of opinions to post furthur proposals', 
      '10', 'threshold_opinion_count', '8')");
  }

  public function down() {
    echo "m131227_084334_insert_configuration does not support migration down.\n";
    return false;
  }
}