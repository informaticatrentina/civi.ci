<?php

class m141217_075042_alter_configuration_table extends CDbMigration
{
  public function up() {
    $this->execute("INSERT INTO `configuration`(`name`, `value`, `name_key`, 
      `display_order`) VALUES ('Text to display on proposal window when proposal 
      submission is Off','','proposal_text',14), ('Number of Columns in Proposal 
      Page', '', 'proposal_layout', 15);");
    $this->execute("ALTER TABLE `configuration` ADD `config_key` VARCHAR( 100 ) 
      NOT NULL DEFAULT 'config' FIRST ;");
    $this->execute("INSERT INTO configuration(config_key, name, name_key) VALUES
      ('homeconfig', 'Main Logo', 'main_logo'),
      ('homeconfig', 'Sub Logo', 'sub_logo'),
      ('homeconfig', 'Banner', 'banner'),
      ('homeconfig', 'Introduction Text', 'introduction_text'),
      ('homeconfig', 'Layout', 'layout')");
  }

  public function down() {
    $this->execute("DELETE FROM `pianosalute`.`configuration` WHERE 
      `configuration`.`name` = 'Text to display on proposal window when 
      proposal submission is Off';
      DELETE FROM `pianosalute`.`configuration` WHERE `configuration`.`name` = 
      'Number of Columns in Proposal Page';");
    $this->execute("ALTER TABLE `configuration` DROP `config_key`;");
    $this->execute("DELETE FROM `configuration` WHERE 
      `configuration`.`config_key` = 'homeconfig';");
  }
}