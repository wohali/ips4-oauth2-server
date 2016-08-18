<?php
/**
 * @brief               OAuth 2 Server Token Gateway
 * @author              Joan Touzet
 * @copyright           (c) 2016 Joan Touzet
 * @license             GPL 2
 */

require_once str_replace( 'applications/oauth2server/interface/oauth/token.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

require_once str_replace( 'interface/oauth/token.php', 'sources/Server/Storage.php', str_replace( '\\', '/', __FILE__ ) );
require_once str_replace( 'interface/oauth/token.php', 'sources/Server/Server.php', str_replace( '\\', '/', __FILE__ ) );

// setup storage and new server
$storage = new \IPS\oauth2server\Storage( \IPS\Db::i() );
$server = \IPS\oauth2server\Server::createServer($storage);

$request = OAuth2\Request::createFromGlobals();

// handle a request for an OAuth2.0 Access Token and send the response to the client
$response = $server->handleTokenRequest($request)->send();
