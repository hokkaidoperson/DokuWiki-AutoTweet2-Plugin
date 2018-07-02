<?php

$meta['apiKey'] = array('string');
$meta['apiSecret'] = array('string');
$meta['accessToken'] = array('string');
$meta['accessTokenSecret'] = array('string');
$meta['subjectOfTweet'] = array('multicheckbox', '_choices' => array('edit', 'revert', 'create', 'delete', 'minor'), '_other' => 'never');
$meta['template'] = array('');
$meta['blacklist'] = array('string');
