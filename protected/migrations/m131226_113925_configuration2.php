<?php

class m131226_113925_configuration2 extends CDbMigration
{
          public function up() {
    $this->execute("DROP TABLE IF EXISTS `configuration`");
    $this->execute("CREATE TABLE IF NOT EXISTS `configuration` (
                    `name` varchar(100) NOT NULL,
                    `value` text,
                    `name_key` varchar(30) NOT NULL,
                    `display_order` tinyint(4) NOT NULL,
                     UNIQUE KEY `name` (`name`),
                     UNIQUE KEY `name_key` (`name_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    $this->execute("INSERT INTO `configuration` (`name`, `value`, `name_key`, `display_order`) VALUES
('Maximun Characters in Opinion', '500', 'max_char_opinion', 7),
('Maximun Characters in Proposal Introduction', '500', 'max_char_intro', 6),
('Maximun Characters in Proposal Title', '50', 'max_char_title', 5),
('Status of Link Submission. (Use 0 for OFF and 1 for ON)', '1', 'link_submission', 1),
('Status of Opinion Submission. (Use 0 for OFF and 1 for ON)', '1', 'opinion_submission', 2),
('Status of Proposal Submission. (Use 0 for OFF and 1 for ON)', '1', 'submission', 3),
('Text to display on home page when submission if Off', 'La raccolta di proposte sulle Riforme Costituzionali Ã¨ chiusa. CIVICI resta comunque a disposizione per chi vuole leggere le proposte e i commenti giÃ  pubblicati. Grazie per aver partecipato!\n', 'homepage_text', 4)");
  }
	public function down()
	{
		echo "m131226_113925_configuration2 does not support migration down.\n";
		return false;
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
