<?php

class m130920_123636_alter_table extends CDbMigration {

  public function up() {
    $this->execute('ALTER TABLE `discussion` CHANGE `summary` `summary` TEXT NOT NULL ');
  }

  public function down() {
    $this->execute('ALTER TABLE `discussion` CHANGE `summary` `summary` VARCHAR(500) NOT NULL ');
  }

}