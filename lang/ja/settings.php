<?php

$lang['apiKey'] = '取得したAPIキー';
$lang['apiSecret'] = '取得したAPIシークレット（外部に漏れてはいけません）';
$lang['accessToken'] = '取得したアクセストークン';
$lang['accessTokenSecret'] = '取得したアクセストークンシークレット（外部に漏れてはいけません）';
$lang['subjectOfTweet'] = 'オートツイートの対象';
$lang['template'] = 'メッセージのテンプレート<br>置換される文字列:<br>###WIKITITLE###→このWikiのタイトル<br>###PAGETITLE###→編集したページのタイトル<br>###TYPE###→編集のタイプ（「編集」「復元」「作成」「削除」「編集（小変更）」のいずれかが表示されます）<br>###SUMMARY###→編集の概要<br>###EDITOR###→Wikiに変更を加えた人（設定項目「<a href="#config___showuseras">showuseras</a>」に従います）<br>###PAGEURL###→編集したページにアクセスするURL（###PAGEURL### が無い場合は、テンプレートの末尾にURLが自動付加されます）';
$lang['guestIP'] = '変更者が非ログインユーザーだった際に、テンプレートの「###EDITOR###」部分を何で置き換えるか';
$lang['blacklist'] = 'ツイート対象外とするページ（バーティカルバー | で区切って下さい）<br>記入例：「playground:playground|start|wiki:syntax」';
$lang['debug'] = '編集者がマネージャーである場合、プラグイン実行時に、デバッグデータ（ボディ（JSON）とレスポンスヘッダー）を表示する（プラグインの動作に不具合が起こった際に、その原因を調査するのにご利用下さい）';
$lang['subjectOfTweet_edit']    = '編集';
$lang['subjectOfTweet_revert']    = '復元';
$lang['subjectOfTweet_create']    = '作成';
$lang['subjectOfTweet_delete']    = '削除';
$lang['subjectOfTweet_minor']    = '編集（小変更）';
$lang['guestIP_o_0']  ='IPアドレスも何も表示しない';
$lang['guestIP_o_alt']  ='代替テキストで置き換える（Hidingipプラグインが必要です。）';
$lang['guestIP_o_show']    ='IPアドレスを表示する';
