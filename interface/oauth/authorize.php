<?php
/**
 * @brief               OAuth 2 Server Authorization Gateway
 * @author              Joan Touzet
 * @copyright           (c) 2016-2017 Joan Touzet
 * @license             GPL 2
 */


require_once str_replace( 'applications/oauth2server/interface/oauth/authorize.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

require_once str_replace( 'interface/oauth/authorize.php', 'sources/Server/Storage.php', str_replace( '\\', '/', __FILE__ ) );
require_once str_replace( 'interface/oauth/authorize.php', 'sources/Server/Server.php', str_replace( '\\', '/', __FILE__ ) );

// require login
$member_id = \IPS\Member::loggedIn()->member_id;
if ( ! $member_id ) {
    // ref parameter is base64 encoding of destination URL
    // Since 4.2.0, we have to "Inception" this because login-based redirect can only target an internal URL
    $real_ref_url = \IPS\Settings::i()->base_url . "applications/oauth2server/interface/oauth/authorize.php?" . http_build_query($_GET, null, ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
    $real_ref = base64_encode( $real_ref_url );
    $ref_url = \IPS\Http\Url::internal( 'app=oauth2server&module=redirect&controller=redirect&ref=' . $real_ref, 'front');
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
    // check scope for containment
    if ( \IPS\Request::i()->scope ) { 
        $scope_req = explode( ' ', \IPS\Request::i()->scope );
        $scope_cli = explode( ' ', $client['scope'] );
        if ( count( array_intersect( $scope_req, $scope_cli ) ) != count( $scope_req ) ) {
            // scope requested not authorized by IPS ACP-entered client definition
            \IPS\Output::i()->sendOutput( 'ERROR: Requested scope "' . $scope_req . '" not allowed by ACP definition.', 403, 'text/html' );
        }
        $scope = $scope_req;
    } else {
        $scope = explode( ' ', $client['scope'] );
    }

    // TODO: Surface scope in template output
    $form = \IPS\Theme::i()->getTemplate( 'server', 'oauth2server', 'front' )->authorize( $client, $scope );
    if ( \IPS\Settings::i()->oauth2server_wrap_global_template ) {
        $title = \IPS\Member::loggedIn()->language()->addToStack('authorize_title');
        \IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $title, $form, true, \IPS\ROOT_PATH ) , 200, 'text/html', \IPS\Output::i()->httpHeaders );
    } else {
        $header = \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->logo();
        \IPS\Output::i()->sendOutput( $header . $form, 200, 'text/html', \IPS\Output::i()->httpHeaders );
    }
}

// print the authorization code if the user has authorized your client
$is_authorized = ( $_POST['authorized'] === "Yes" );
if ( $is_authorized ) {
    \IPS\Session::i()->csrfCheck();
    $storage->setAuthorization( \IPS\Request::i()->client_id, $member_id, \IPS\Request::i()->scope );
}
$server->handleAuthorizeRequest( $request, $response, $is_authorized, $member_id );
$response->send();


