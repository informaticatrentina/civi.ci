<?php
/**
 * Including local configuration file.
 */
require_once(dirname(__FILE__).'/local_config.php');
//To get commands from modules and set it at commandMap
$commands = array('emessage' => array('class' => 'application.extensions.protected.commands.EMessageCommand'));
if (defined('ENABLE_MODULES_LIST')) {
  $modules = json_decode(ENABLE_MODULES_LIST, TRUE);
  try {
    if (empty($modules)) {
      throw new Exception(Yii::t('discsussion', 'modules are not defined'));
    }
    foreach ($modules as $module) {
      $directory = dirname(__FILE__) . '/../' . 'modules/' . $module . '/commands';
      if (is_dir($directory)) {
        $directory = $directory . '/*';
      } else {
        continue;
      }
      $commandsList = glob($directory);
      if (!empty($commandsList)) {
        foreach ($commandsList as $command) {
          $commandName = pathinfo($command);
          if (array_key_exists('filename', $commandName) && !empty($commandName['filename'])) {
            $commands[strtolower($commandName['filename'])] = array(
              'class' => 'application.modules.' . $module . '.commands.' . $commandName['filename']);
          }
        }
      }
    }
  } catch (Exception $e) {
    Yii::log($e->getMessage(), 'error', 'Error in console.php module does not exist');
  }
}

return array(
  'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
  'preload' => array('log'),
  'import' => array(
    'application.extensions.protected.components.helpers.CLI',
    'application.lib.*',
    'application.models.*',
  ),
  'commandMap' => $commands,
  'components' => array(
    'db' => array(
      'class' => 'CDbConnection',
      'connectionString' => 'mysql:host='.DB_HOST.';dbname='.DB_NAME,
      'username' => DB_USER,
      'password' => DB_PASS,
      'emulatePrepare' => true,
    ),        
  ),
);
