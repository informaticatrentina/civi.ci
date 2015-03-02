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
      $proposals = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active', '',
        '', '', '', '', '', '', '', array(), '', 'status,title,author,id,content,tags',
        '', '', trim('discussion,' . $discussion['id']), CIVICO);
      if (!empty($proposals)) {
        foreach ($proposals as $proposal) {
          $opinions = $aggregatorManager->getEntry(ALL_ENTRY, '', '', 'active',
            'link{' . OPINION_TAG_SCEME . '}', '', '', 1, '', '', '', '', array(),
            '', 'tags', '', '', trim('proposal,' . $proposal['id']), CIVICO);
            $proposalTags = $this->_prepareProposalTags($proposal, $opinions);
            $proposal['tags'] = $proposalTags;
            $aggregatorManager->id = $proposal['id'];
            $aggregatorManager->tags = $proposal['tags'];
            $aggregatorManager->updateProposal();
          }
        }
      }
    }


  /**
   * _prepareProposalTags
   * function is used for prepare proposal tags
   * @param array $proposalTags - tag of sinlge proposal
   * @param array $triangle - array of triangle index value
   * @return array $proposalTag - modified proposal tag
   */
  private function _prepareProposalTags($proposal, $opinions) {
    if (array_key_exists('tags', $proposal)) {
      $triangle = array();
      $opinionCount = 0;
      foreach ($opinions as $opinion) {
        if (array_key_exists('tags', $opinion)) {
          foreach ($opinion['tags'] as $tag) {
            if ($tag['scheme'] == INDEX_TAG_SCHEME) {
              if (array_key_exists($tag['weight'], $triangle)) {
                $triangle[$tag['weight']] += 1;
              } else {
                $triangle[$tag['weight']] = 1;
              }
            }
          }
        }
        if (array_key_exists('count', $opinion)) {
          $opinionCount = $opinion['count'];
        }
      }
      $countExists = 0;
      foreach ($proposal['tags'] as &$tag) {
        if ($tag['scheme'] == OPINION_COUNT_TAG_SCEME) {
          $tag['weight'] = $opinionCount;
          $countExists = 1;
        }
        if ($tag['scheme'] == TAG_SCHEME) {
          if (array_key_exists($tag['name'], $triangle)) {
            $tag['weight'] = $triangle[$tag['name']];
          } else {
            $tag['weight'] = 0;
          }
        }
      }
      if ($countExists == 0) {
        $proposal['tags'][] = array('name' => 'OpinionCount', 'scheme' => OPINION_COUNT_TAG_SCEME,
                                    'slug' => 'OpinionCount', 'weight' => $opinionCount);
      }
      return $proposal['tags'];
    }
  }

}

?>
