<?php

class m140526_043948_alter_table_discussion_for_additional_header_content extends CDbMigration {

  public function up() {
    $this->execute("ALTER TABLE `discussion` ADD `additional_description` TEXT NULL ;");
  }

  public function down() {
    $this->execute("ALTER TABLE `discussion` DROP `additional_description` ;");
  }
}