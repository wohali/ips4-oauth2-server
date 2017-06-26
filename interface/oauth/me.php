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
    $profile = array( );
    if ( strlen( $token['scope'] == 0 ) ) {
        // Compatibility for tokens created prior to v1.2.0 of this plugin
        // Use the default scope from the client.
        $client = $storage->getClientDetails( $token['client_id'] );
        $scope = explode( ' ', $client['scope'] );
    } else {
        $scope = explode( ' ', $token['scope'] );
    }

    if ( in_array( 'user.profile', $scope ) ) {
        $profile = array_merge ( $profile, array (
            'id' => $member->member_id,
            'username' => $member->name,
            'displayName' => $member->name,
            'profileUrl' => strval($member->url()),
            'avatar' => $member->get_photo()
        ) );
    }
    if ( in_array( 'user.email', $scope ) ) {
        $profile = array_merge ( $profile, array (
            'email' => $member->email
        ) );
    }
    if ( in_array( 'user.groups', $scope ) ) {
        $profile = array_merge ( $profile, array (
            'group' => $member->member_group_id,
            'group_others' => explode(',', $member->mgroup_others)
        ) );
    }
    if ( in_array( 'user.reputation', $scope ) ) {
        $profile = array_merge ( $profile, array (
            'reputation' => $member->pp_reputation_points
        ) );
    }

    echo json_encode($profile);
} else {
    http_response_code(404);
    die;
}
