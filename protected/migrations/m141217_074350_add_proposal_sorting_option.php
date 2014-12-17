<?php

class m141217_074350_add_proposal_sorting_option extends CDbMigration {

  public function up() {
    $this->execute("INSERT INTO `configuration` (`name`, `value`, `name_key`, `display_order`)
      VALUES ('Proposal sorting', NULL , 'proposal_sorting_base', '14');");
  }

  public function down() {
    $this->execute("DELETE FROM `configuration` WHERE `configuration`.`name_key` = 'proposal_sorting_base'");
  }

}