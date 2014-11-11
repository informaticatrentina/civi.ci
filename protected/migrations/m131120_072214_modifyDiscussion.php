<?php

class m131120_072214_modifyDiscussion extends CDbMigration
{
	public function up()
	{
          $this->execute('ALTER TABLE discussion MODIFY COLUMN  title TEXT');
	}

	public function down()
	{
		echo "m131120_072214_modifyDiscussion does not support migration down.\n";
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