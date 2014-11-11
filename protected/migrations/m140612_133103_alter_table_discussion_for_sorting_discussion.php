<?php

class m140612_133103_alter_table_discussion_for_sorting_discussion extends CDbMigration {

  public function up() {
    $this->execute("ALTER TABLE `discussion` ADD `sort_id` TINYINT( 2 ) NOT NULL ;");
  }

  public function down() {
    $this->execute("ALTER TABLE `discussion` DROP `sort_id` ;");
  }

}