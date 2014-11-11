<?php

class m130813_065114_discussion extends CDbMigration
{
	public function up()
	{
          $this->execute('CREATE TABLE IF NOT EXISTS `discussion` (
  			   `id` int(11) NOT NULL AUTO_INCREMENT,
  			   `title` varchar(100) NOT NULL,
  			   `summary` varchar(300) NOT NULL,
  			   `author` varchar(100) NOT NULL,
  			   `status` tinyint(2) NOT NULL,
  			   `proposal_status` tinyint(2) NOT NULL,
  			   `author_id` varchar(42) NOT NULL,
                           `creationDate` datetime NOT NULL,
                           `slug`  varchar(200) NOT NULL,
  			   PRIMARY KEY (`id`)
			 ) ENGINE=MyISAM');

	}

	public function down()
	{
		$this->execute('DROP TABLE discussion');

	}
}