<?php

class m131226_120612_addcolumn extends CDbMigration
{
	public function up()
	{
          $this->execute("ALTER TABLE `discussion` ADD `topic` TEXT NOT NULL ");
	}

	public function down()
	{
		echo "m131226_120612_addcolumn does not support migration down.\n";
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