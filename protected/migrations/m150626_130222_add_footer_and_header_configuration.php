<?php

class m150626_130222_add_footer_and_header_configuration extends CDbMigration {

  public function up() {
    $time = time();
    //add configuration for disabling registration link in header
    $this->execute(
      "INSERT INTO `configuration` (`config_key`, `name`, `value`, `name_key`,
      `display_order`, `last_modified`, `editor_email`) VALUES('config', 'Disable registration link',
      '1', 'disable_registration_link', 16, " . $time . ", 'pradeep@incaendo.com')"
    );
    //add configuration for attaching image on proposal
    $this->execute(
      "INSERT INTO `configuration` (`config_key`, `name`, `value`, `name_key`,
      `display_order`, `last_modified`, `editor_email`) VALUES('config', 'Attach image on Proposal. (Use 0 for OFF and 1 for ON)', '0', 'attach_img_on_proposal', '17', 
      " . $time . ", 'pradeep@incaendo.com')"
    );
    //add configuration for footer html on each page
    $this->execute(
      "INSERT INTO `configuration` (`config_key`, `name`, `value`, `name_key`,
      `display_order`, `last_modified`, `editor_email`) VALUES('config',
      'Footer html to display on each page', 'Please enter footer html in configuration',
      'footer_html', '18', " . $time . ", 'pradeep@incaendo.com')"
    );
  }

  public function down() {
    echo "m150626_130222_add_footer_and_header_configuration does not support migration down.\n";
    return false;
  }

}