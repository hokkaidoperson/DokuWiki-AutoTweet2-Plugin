<?php
/**
 * AutoTweet 2 plugin
 * Post the information of changes to Twitter
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     HokkaidoPerson <dosankomali@yahoo.co.jp>
 */

if(!defined('DOKU_INC')) die();


class action_plugin_autotweet2 extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'tweet', array());
    }


    //Reference: https://syncer.jp/Web/API/Twitter/REST_API/

    public function tweet(Doku_Event $event, $param) {

        // Load configurations and set up
        $api_key = $this->getConf('apiKey');
        $api_secret = $this->getConf('apiSecret');
        $access_token = $this->getConf('accessToken');
        $access_token_secret = $this->getConf('accessTokenSecret');
        $request_url = 'https://api.twitter.com/1.1/statuses/update.json' ;
        $request_method = 'POST' ;

        // If keys and secrets are empty, the function will not run
        if ($api_key == '' or $api_secret == '' or $access_token == '' or $access_token_secret == '') return;

        // Check the blacklist
        $blacklist = '|' . $this->getConf('blacklist') . '|';
        $savingid = $event->data['id'];
        if (strpos($blacklist, '|' . $savingid . '|') !== FALSE) return;


        // Check the type of editing and the conf "subjectOfTweet"
        $subject = $this->getConf('subjectOfTweet');

        switch ($event->data['changeType']) {
        case DOKU_CHANGE_TYPE_EDIT      : if (strpos($subject, 'edit') === FALSE) return; else $edittype = $this->getLang('edit');
            break;
        case DOKU_CHANGE_TYPE_REVERT    : if (strpos($subject, 'revert') === FALSE) return; else $edittype = $this->getLang('revert');
            break;
        case DOKU_CHANGE_TYPE_CREATE    : if (strpos($subject, 'create') === FALSE) return; else $edittype = $this->getLang('create');
            break;
        case DOKU_CHANGE_TYPE_DELETE    : if (strpos($subject, 'delete') === FALSE) return; else $edittype = $this->getLang('delete');
            break;
        case DOKU_CHANGE_TYPE_MINOR_EDIT: if (strpos($subject, 'minor') === FALSE) return; else $edittype = $this->getLang('minor');
            break;
        default:
            return;
        }

        // Construct the message
        $message = $this->getConf('template');
        $message = str_replace('###WIKITITLE###', $conf['title'], $message);
        $message = str_replace('###PAGETITLE###', tpl_pagetitle($savingid, 1), $message);
        $message = str_replace('###TYPE###', $edittype, $message);
        $message = str_replace('###SUMMARY###', $event->data['summary'], $message);

        $pageurl = wl($savingid, '', TRUE);

        if (strpos($message, '###PAGEURL###') === FALSE) $message .= ' ' . $pageurl; else $message = str_replace('###PAGEURL###', $pageurl, $message);


        // Copied and adoped from the reference above
        //
        // パラメータA (リクエストのオプション)
        $params_a = array(
            'status' => $message ,
        ) ;

        // キーを作成する (URLエンコードする)
        $signature_key = rawurlencode( $api_secret ) . '&' . rawurlencode( $access_token_secret ) ;

        // パラメータB (署名の材料用)
        $params_b = array(
            'oauth_token' => $access_token ,
            'oauth_consumer_key' => $api_key ,
            'oauth_signature_method' => 'HMAC-SHA1' ,
            'oauth_timestamp' => time() ,
            'oauth_nonce' => microtime() ,
            'oauth_version' => '1.0' ,
        ) ;

        // パラメータAとパラメータBを合成してパラメータCを作る
        $params_c = array_merge( $params_a , $params_b ) ;

        // 連想配列をアルファベット順に並び替える
        ksort( $params_c ) ;

        // パラメータの連想配列を[キー=値&キー=値...]の文字列に変換する
        $request_params = http_build_query( $params_c , '' , '&' ) ;

        // 一部の文字列をフォロー
        $request_params = str_replace( array( '+' , '%7E' ) , array( '%20' , '~' ) , $request_params ) ;

        // 変換した文字列をURLエンコードする
        $request_params = rawurlencode( $request_params ) ;

        // リクエストメソッドをURLエンコードする
        // ここでは、URL末尾の[?]以下は付けないこと
        $encoded_request_method = rawurlencode( $request_method ) ;
         
        // リクエストURLをURLエンコードする
        $encoded_request_url = rawurlencode( $request_url ) ;
         
        // リクエストメソッド、リクエストURL、パラメータを[&]で繋ぐ
        $signature_data = $encoded_request_method . '&' . $encoded_request_url . '&' . $request_params ;

        // キー[$signature_key]とデータ[$signature_data]を利用して、HMAC-SHA1方式のハッシュ値に変換する
        $hash = hash_hmac( 'sha1' , $signature_data , $signature_key , TRUE ) ;

        // base64エンコードして、署名[$signature]が完成する
        $signature = base64_encode( $hash ) ;

        // パラメータの連想配列、[$params]に、作成した署名を加える
        $params_c['oauth_signature'] = $signature ;

        // パラメータの連想配列を[キー=値,キー=値,...]の文字列に変換する
        $header_params = http_build_query( $params_c , '' , ',' ) ;

        // リクエスト用のコンテキスト
        $context = array(
            'http' => array(
                'method' => $request_method , // リクエストメソッド
                'header' => array(			  // ヘッダー
                    'Authorization: OAuth ' . $header_params ,
                ) ,
            ) ,
        ) ;

        // オプションがある場合、コンテキストにPOSTフィールドを作成する
        if ( $params_a ) {
            $context['http']['content'] = http_build_query( $params_a ) ;
        }

        // cURLを使ってリクエスト
        $this->curl = curl_init() ;
        curl_setopt( $this->curl, CURLOPT_URL , $request_url ) ;	// リクエストURL
        curl_setopt( $this->curl, CURLOPT_HEADER, true ) ;	// ヘッダーを取得
        curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST, $context['http']['method'] ) ;	// メソッド
        curl_setopt( $this->curl, CURLOPT_SSL_VERIFYPEER, false ) ;	// 証明書の検証を行わない
        curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true ) ;	// curl_execの結果を文字列で返す
        curl_setopt( $this->curl, CURLOPT_HTTPHEADER, $context['http']['header'] ) ;	// ヘッダー
        if( isset( $context['http']['content'] ) && !empty( $context['http']['content'] ) ) {
            curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $context['http']['content'] ) ;	// リクエストボディ
        }
        curl_setopt( $this->curl, CURLOPT_TIMEOUT, 5 ) ;	// タイムアウトの秒数
        $res1 = curl_exec( $this->curl ) ;
        $res2 = curl_getinfo( $this->curl ) ;
        curl_close( $this->curl ) ;

    }


}