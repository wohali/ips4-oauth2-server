<?php
/**
 * @brief               OAuth 2 Server Authorization Gateway
 * @author              Joan Touzet
 * @copyright           (c) 2016 Joan Touzet
 * @license             GPL 2
 */

require_once str_replace( 'applications/oauth2server/interface/authorize.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\Front::i();

require_once str_replace( 'interface/authorize.php', 'sources/Server/Storage.php', str_replace( '\\', '/', __FILE__ ) );
require_once str_replace( 'interface/authorize.php', 'sources/Server/Server.php', str_replace( '\\', '/', __FILE__ ) );

// require login
$member_id = \IPS\Member::loggedIn()->member_id;
if ( ! $member_id ) {
    // ref parameter is base64 encoding of destination URL
    $ref_url = \IPS\Settings::i()->base_url . "applications/oauth2server/interface/authorize.php?" . http_build_query($_GET);
    $ref = base64_encode( $ref_url );
    \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=login&ref=' . $ref, 'front', 'login' ) );
}

// setup storage and new server
$storage = new \IPS\oauth2server\Storage( \IPS\Db::i() );
$server = \IPS\oauth2server\Server::createServer($storage);

// setup OAuth request and response
$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// check application access permissions
$client = $storage->getClientDetails($_GET['client_id']);
if ( ! $client ) {
    $response->setError(400, 'invalid_client', 'The client id supplied is invalid');
    $response->send();
    die;
}
try {
    $node = \IPS\oauth2server\Client::loadAndCheckPerms( $client['node_id'], 'access'  );
}
catch ( \OutOfRangeException $e ) {
    // no perms! return a fake error code to abort login process
    $response->setError(403, 'unauthorized_user');
    $response->send();
    die;
}

// validate the authorize request
if ( !$server->validateAuthorizeRequest( $request, $response ) ) {
    $response->send();
    die;
}

// check for saved authorizations
if ($storage->hasAuthorization( \IPS\Request::i()->client_id, $member_id, '' ) ) {
    $server->handleAuthorizeRequest( $request, $response, true, $member_id);
    $response->send();
    die;
}

// display an authorization form
if ( empty($_POST) ) {
    $client = $storage->getClientDetails( \IPS\Request::i()->client_id );
    $header = \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->logo();
    $form = \IPS\Theme::i()->getTemplate( 'server', 'oauth2server', 'front' )->authorize( $client );
    \IPS\Output::i()->sendOutput( $header . $form, 200, 'text/html', \IPS\Output::i()->httpHeaders );
}

// print the authorization code if the user has authorized your client
$is_authorized = ( $_POST['authorized'] === "Yes" );
if ( $is_authorized ) {
    \IPS\Session::i()->csrfCheck();
    $storage->setAuthorization( \IPS\Request::i()->client_id, $member_id, '');
}
$server->handleAuthorizeRequest( $request, $response, $is_authorized, $member_id );
$response->send();


