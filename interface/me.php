<?php
/**
 * @brief               OAuth 2 Server Token Gateway
 * @author              Joan Touzet
 * @copyright           (c) 2016 Joan Touzet
 * @license             GPL 2
 */

require_once str_replace( 'applications/oauth2server/interface/me.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\Front::i();

require_once str_replace( 'interface/me.php', 'sources/Server/Storage.php', str_replace( '\\', '/', __FILE__ ) );
require_once str_replace( 'interface/me.php', 'sources/Server/Server.php', str_replace( '\\', '/', __FILE__ ) );

// setup storage and new server
$storage = new \IPS\oauth2server\Storage( \IPS\Db::i() );
$server = \IPS\oauth2server\Server::createServer($storage);

$request = OAuth2\Request::createFromGlobals();

// validate token
if (!$server->verifyResourceRequest($request)) {
    $server->getResponse()->send();
    die;
}

$token = $server->getAccessTokenData($request);
header('Content-Type: application/json');

// create profile object
if ( $member = \IPS\Member::load( $token['member_id'] ) ) {
    $profile = [];
    $profile['id'] = $member->member_id;
    $profile['username'] = $member->name;
    $profile['displayName'] = $member->name;
    if( in_array('email', $scope, true) )
        $profile['email'] = $member->email;
    $profile['profileUrl'] = strval($member->url());
    $profile['avatar'] = $member->get_photo();

    echo json_encode($profile);
} else {
    http_response_code(404);
    die;
}
