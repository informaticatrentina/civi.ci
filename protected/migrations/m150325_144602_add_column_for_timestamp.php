<?php

class m150325_144602_add_column_for_timestamp extends CDbMigration {

  public function up() {
    $this->execute("ALTER TABLE `configuration` ADD `last_modified` INT( 10 ) NOT NULL ,
      ADD `editor_email` VARCHAR( 50 ) NOT NULL ");
  }

  public function down() {
    $this->execute("ALTER TABLE `configuration` DROP `last_modified`");
    $this->execute("ALTER TABLE `configuration` DROP `editor_email`");
  }

}
