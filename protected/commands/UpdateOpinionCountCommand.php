<?php

/**
 * UpdateCommand
 * 
 * This class is ised to update proposal.
 * Copyright (c) 2013 <ahref Foundation -- All rights reserved.
 * Author: Sankalp Mishra <sankalp@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission of
  <ahref Foundation.
 */
class UpdateOpinionCountCommand extends CConsoleCommand {

  public function run($args) {
    $discussion = new Discussion();
    $discussions = $discussion->getDiscussionDetail();
    foreach ($discussions as $discussion) {
      $aggregatorManager = new AggregatorManager();
      $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', '', '', '', '', '', '', '', '', array(), '', 'status,title,author,id,content,tags', '', '', trim('discussion,' . $discussion['id']), CIVICO);
      if (!empty($proposals)) {
        foreach ($proposals as $proposal) {
          $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', 'link{' . OPINION_TAG_SCEME . '}', '', '', 2, '', '', '', '', array(), '', 'status,author,id,content,tags', '', '', trim('proposal,' . $proposal['id']), CIVICO);
          if (array_key_exists('tags', $proposal)) {
            $tags = $proposal['tags'];
            $exists = 0;
            $newtags = array();
            foreach ($tags as $tag) {
              $tagss = array();
              $tagss = $tag;
              if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
                $tagss['weight'] = $opinions[0]['count'];
                $exists = 1;
              }
              $newtags[] = $tagss;
            }
            if ($exists !== 1) {
              $newtags[] = array('name' => 'OpinionCount', 'scheme' => OPINION_COUNT_TAG_SCEME, 'slug' => 'OpinionCount', 'weight' => $opinions[0]['count']);
            }
            $proposal['tags'] = $newtags;
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
