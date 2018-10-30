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

        if (!isset($_SERVER['REMOTE_USER'])) {
            switch ($this->getConf('guestIP')) {
            case 'show': $message = str_replace('###EDITOR###', $_SERVER['REMOTE_ADDR'], $message);
                break;
            case 'alt' :
                if(!plugin_isdisabled('hidingip')) {
                    $hidingip = plugin_load('helper', 'hidingip');
                    $message = str_replace('###EDITOR###', $hidingip->altText(), $message);
                    break;
                }
                // Else, fall through
            default    : $message = str_replace('###EDITOR###', '', $message);
            }
        } else $message = str_replace('###EDITOR###', userlink(null, true), $message);

        $pageurl = wl($savingid, '', TRUE);

        if (strpos($message, '###PAGEURL###') === FALSE) $message .= ' ' . $pageurl; else $message = str_replace('###PAGEURL###', $pageurl, $message);

        // Insert a space after "@" not to violent the Twitter rule that prohibits "automated @ tweets"
        $message = str_replace('@', '@ ', $message);  // one-byte @
        $message = str_replace('＠', '＠ ', $message);  // two-byte ＠

        // Copied and adoped from the reference above
        //
        // Parameter A (the option of the request)
        $params_a = array(
            'status' => $message ,
        ) ;

        // Make a key (URL encode)
        $signature_key = rawurlencode( $api_secret ) . '&' . rawurlencode( $access_token_secret ) ;

        // Parameter B (ingredients of the signature)
        $params_b = array(
            'oauth_token' => $access_token ,
            'oauth_consumer_key' => $api_key ,
            'oauth_signature_method' => 'HMAC-SHA1' ,
            'oauth_timestamp' => time() ,
            'oauth_nonce' => microtime() ,
            'oauth_version' => '1.0' ,
        ) ;

        // Make parameter C by merging A and B
        $params_c = array_merge( $params_a , $params_b ) ;

        // Sort the associative array in alphabetical order
        ksort( $params_c ) ;

        // Convert the associative array of parameters into the string [key=value&key=value...]
        $request_params = http_build_query( $params_c , '' , '&' ) ;

        // Follow some characters
        $request_params = str_replace( array( '+' , '%7E' ) , array( '%20' , '~' ) , $request_params ) ;

        // URL-encode the converted string
        $request_params = rawurlencode( $request_params ) ;

        // URL-encode the request method
        // In this time, it sholdn't include [?] in the last of the URL and after
        $encoded_request_method = rawurlencode( $request_method ) ;
         
        // URL-encode the request URL
        $encoded_request_url = rawurlencode( $request_url ) ;
         
        // Merge the request method, the request URL, and the parameters by [&]
        $signature_data = $encoded_request_method . '&' . $encoded_request_url . '&' . $request_params ;

        // Using the key [$signature_key] and the data [$signature_data], make a HMAC-SHA1 type hash value
        $hash = hash_hmac( 'sha1' , $signature_data , $signature_key , TRUE ) ;

        // Base64-encode, and the [$signature] is ready
        $signature = base64_encode( $hash ) ;

        // Add the signature to the associative array of the data [$params]
        $params_c['oauth_signature'] = $signature ;

        // Convert the associative array of the parameter into the string [key=value&key=value...]
        $header_params = http_build_query( $params_c , '' , ',' ) ;

        // Context for the request
        $context = array(
            'http' => array(
                'method' => $request_method , // Request method
                'header' => array(			  // Header
                    'Authorization: OAuth ' . $header_params ,
                ) ,
            ) ,
        ) ;

        // If there is an option, make a POST field into the context
        if ( $params_a ) {
            $context['http']['content'] = http_build_query( $params_a ) ;
        }

        // Request by using cURL
        $this->curl = curl_init() ;
        curl_setopt( $this->curl, CURLOPT_URL , $request_url ) ;	// Request URL
        curl_setopt( $this->curl, CURLOPT_HEADER, true ) ;	// Get the header
        curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST, $context['http']['method'] ) ;	// Method
        curl_setopt( $this->curl, CURLOPT_SSL_VERIFYPEER, false ) ;	// Don't verify the certificate
        curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true ) ;	// Return the result of the curl_exec with a string
        curl_setopt( $this->curl, CURLOPT_HTTPHEADER, $context['http']['header'] ) ;	// Header
        if( isset( $context['http']['content'] ) && !empty( $context['http']['content'] ) ) {
            curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $context['http']['content'] ) ;	// Request body
        }
        curl_setopt( $this->curl, CURLOPT_TIMEOUT, 5 ) ;	// Timeout seconds
        $res1 = curl_exec( $this->curl ) ;
        $res2 = curl_getinfo( $this->curl ) ;
        curl_close( $this->curl ) ;

        $json = substr( $res1, $res2['header_size'] ) ;	// Got data (such as JSON)
        $header = substr( $res1, 0, $res2['header_size'] ) ;	// Response header

        if ($this->getConf('debug') and auth_ismanager()) {
            msg('[Debug] Body(JSON): ' . $json);
            msg('[Debug] Response header: ' . $header);
        }

    }


}