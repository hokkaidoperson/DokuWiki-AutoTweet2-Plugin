<?php
 
$lang['apiKey'] = 'API Key you got';
$lang['apiSecret'] = 'API Secret you got (keep it a secret)';
$lang['accessToken'] = 'Access Token you got';
$lang['accessTokenSecret'] = 'Access Token Secret you got (keep it a secret)';
$lang['subjectOfTweet'] = 'Subjects of the auto-tweet';
$lang['template'] = 'Template of the message<br>REPLACEMENT:<br>###WIKITITLE###→The title of this wiki<br>###PAGETITLE###→The title of the edited page<br>###TYPE###→The type of editing ("edited", "reverted", "created", "deleted", or "edited (minor change)" will be shown)<br>###SUMMARY###→The summary of editing<br>###EDITOR###→Who changed the wiki (Follows the config "<a href="#config___showuseras">showuseras</a>")<br>###PAGEURL###→The URL leading to the edited page (if there is not ###PAGEURL###, the URL will be automatically added to the last of the template)';
$lang['guestIP'] = 'What to replace ###EDITOR### of the template with if the editor was not logging in';
$lang['blacklist'] = 'Pages that will not be subjects of auto-tweet (separate by vertical bars "|")<br>e.g.:"playground:playground|start|wiki:syntax"';
$lang['debug'] = 'If the editor is a manager, show the debug information (a body (JSON) and a response header) when the plugin is run (Use this option to investigate causes when the plugin doesn\'t work well.)';
$lang['subjectOfTweet_edit']    = 'edited';
$lang['subjectOfTweet_revert']    = 'reverted';
$lang['subjectOfTweet_create']    = 'created';
$lang['subjectOfTweet_delete']    = 'deleted';
$lang['subjectOfTweet_minor']    = 'edited (minor change)';
$lang['guestIP_o_0']  ='Don\'t show the IP address, replace with the null text';
$lang['guestIP_o_alt']  ='Replace with the alternative text ("Hidingip" plugin required.)';
$lang['guestIP_o_show']    ='Show the IP address';
