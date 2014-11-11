<?php

class m131113_051908_configuration extends CDbMigration {

  public function up() {
    $this->execute('CREATE TABLE IF NOT EXISTS `configuration` (
                     `name` varchar(100) NOT NULL,
                     `value` TEXT NOT NULL,
                     `name_key` varchar(30) NOT NULL,
                      UNIQUE KEY `name` (`name`),
                      UNIQUE KEY `name_key` (`name_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');
  }

  public function down() {
    $this->execute('DROP TABLE configuration');
  }

  /*
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
   */
}