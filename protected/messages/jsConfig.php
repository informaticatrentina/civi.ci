<?php

return array(
    'sourcePath' => dirname(__FILE__) . '/../../',
    'messagePath' => dirname(__FILE__) . '/../messages',
    'translator' => 'Yii.t',
    'languages' => array('it_it'),
    'fileTypes' => array('js'),
    'overwrite' => true,
    'removeOld' => true,
    'exclude' => array(
        '.git',
        '.svn',
        '/framework',
        '/protected',
    ),
);