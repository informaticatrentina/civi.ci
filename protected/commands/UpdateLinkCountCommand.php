<?php

/**
 * UpdateLinkCountCommand
 * 
 * This class is ised to update proposal's link count.
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class UpdateLinkCountCommand extends CConsoleCommand {

  public function run($args) {
    $discussion = new Discussion();
    $discussions = $discussion->getDiscussionDetail();
    foreach ($discussions as $discussion) {
      $aggregatorManager = new AggregatorManager();
      $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', '', '', '', '', '', '', '', '', array(), '', 'status,title,author,id,content,tags', '', '', trim('discussion,' . $discussion['id']), CIVICO);
      if (!empty($proposals)) {
        foreach ($proposals as $proposal) {
          $links = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . LINK_TAG_SCEME . '}', '', '', 2, '', '', '', '', array(), '', 'status,author,id,content,tags', '', '', trim('proposal,' . $proposal['id']), CIVICO);
          if (array_key_exists('tags', $proposal)) {
            $tags = $proposal['tags'];
            $exists = 0;
            $newtags = array();
            foreach ($tags as $tag) {
              $tagss = array();
              $tagss = $tag;
              if ($tag['scheme'] == LINK_COUNT_TAG_SCEME) {
                $tagss['weight'] = $links[0]['count'];
                $exists = 1;
              }
              $newtags[] = $tagss;
            }
            if ($exists !== 1) {
              $newtags[] = array('name' => 'LinkCount', 'scheme' => LINK_COUNT_TAG_SCEME, 'slug' => 'LinkCount', 'weight' => $links[0]['count']);
            }
            $proposal['tags'] = $newtags;
            $aggregatorManager->id = $proposal['id'];
            $aggregatorManager->tags = $proposal['tags'];
            $aggregatorManager->updateProposal();
          } else {
            $proposal['tags'] = array();
            $proposal['tags'] = array('name' => 'LinkCount', 'scheme' => LINK_COUNT_TAG_SCEME, 'slug' => 'LinkCount', 'weight' => $links[0]['count']);
            $aggregatorManager->id = $proposal['id'];
            $aggregatorManager->tags = $proposal['tags'];
            $aggregatorManager->updateProposal();
          }
        }
      }
    }
  }

}

?>
