<?php

class m140610_124535_insert_opinion_and_link_text_in_config extends CDbMigration {

  public function up() {
    $this->execute("INSERT INTO `configuration` (`name` , `value` , `name_key` ,
      `display_order`) VALUES ('Text to display on proposal window when opinion submission is Off', 
      'Presentazione opinione è stato chiuso', 'opinion_text', '9')"
    );
    $this->execute("INSERT INTO `configuration` (`name` , `value` , `name_key` ,
      `display_order`) VALUES ('Text to display on proposal window when link submission is Off', 
      'Presentazione link è stato chiuso', 'link_text', '10')"
    );
  }

  public function down() {
    echo "m140610_124535_insert_opinion_and_link_text_in_config does not support migration down.\n";
    return false;
  }

}