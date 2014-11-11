<?php

class m131113_053828_configuration1 extends CDbMigration {

  public function up() {
    $this->execute('CREATE TABLE IF NOT EXISTS `configuration` (
                     `name` varchar(100) NOT NULL,
                     `value` TEXT NOT NULL,
                     `name_key` varchar(30) NOT NULL,
                      UNIQUE KEY `name` (`name`),
                      UNIQUE KEY `name_key` (`name_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');
    $this->execute("INSERT INTO `configuration` (`name`, `value`, `name_key`) VALUES
('Status of Link Submission. (Use 0 for OFF and 1 for ON)', '1', 'link_submission'),
('Status of Opinion Submission. (Use 0 for OFF and 1 for ON)', '1', 'opinion_submission'),
('Status of Proposal Submission. (Use 0 for OFF and 1 for ON)', '1', 'submission'),
('Text to display on home page when submission if Off', 'This is text', 'homepage_text')");
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