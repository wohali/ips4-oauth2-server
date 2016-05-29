<?php

namespace IPS\oauth2server;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

require_once str_replace( 'Server/Server.php', 'OAuth2/Autoloader.php', str_replace( '\\', '/', __FILE__ ) );
\OAuth2\Autoloader::register();

use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\RefreshToken;

class Server {

    /**
     * Creates an OAuth2 server with the given storage.
     *
     * @param Storage $storage
     * @return \OAuth2\Server
     */
    public static function createServer(Storage $storage) {

        // setup server
        $server = new \OAuth2\Server($storage, array('enforce_state' => false));

        // add the "client credentials" grant type (it is the simplest of the grant types)
        $server->addGrantType(new ClientCredentials($storage));

        // add the "authorization code" grant type (this is where the oauth magic happens)
        $server->addGrantType(new AuthorizationCode($storage));

        return $server;
    }

}
