<?php
/**
 * @brief               OAuth 2 Server Token Gateway
 * @author              Joan Touzet
 * @copyright           (c) 2016 Joan Touzet
 * @license             GPL 2
 */

require_once str_replace( 'applications/oauth2server/interface/oauth/me.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

require_once str_replace( 'interface/oauth/me.php', 'sources/Server/Storage.php', str_replace( '\\', '/', __FILE__ ) );
require_once str_replace( 'interface/oauth/me.php', 'sources/Server/Server.php', str_replace( '\\', '/', __FILE__ ) );

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
    $profile = array (
        'id' => $member->member_id,
        'username' => $member->name,
        'displayName' => $member->name,
        'email' => $member->email,
        'profileUrl' => strval($member->url()),
        'avatar' => $member->get_photo(),
        'group' => $member->member_group_id,
        'group_others' => explode(',', $member->mgroup_others)
    );
    echo json_encode($profile);
} else {
    http_response_code(404);
    die;
}
