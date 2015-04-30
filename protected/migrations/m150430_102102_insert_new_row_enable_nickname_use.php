<?php

class m150430_102102_insert_new_row_enable_nickname_use extends CDbMigration
{
  public function up() {
    $this->execute("INSERT INTO `configuration`(`config_key`, `name`, `value`,
      `name_key`) VALUES ('config','Enable use of Nicknames', 0, 'enable_nickname_use');");
  }

  public function down() {
    $this->execute("DELETE FROM `configuration` WHERE name_key = 'enable_nickname_use';");
  }
}