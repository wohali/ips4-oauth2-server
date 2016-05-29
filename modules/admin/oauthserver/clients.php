<?php
/**
 * @package     OAuth2 Server
 * @author      Joan Touzet
 * @copyright   (c) 2016 Joan Touzet
 */

namespace IPS\oauth2server\modules\admin\oauthserver;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Clients
 */
class _clients extends \IPS\Node\Controller
{
    /**
     * Node Class
     */
    protected $nodeClass = 'IPS\oauth2server\Client';

    /**
     * Execute
     *
     * @return  void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'clients_manage' );
        parent::execute();
    }

    /**
     * Modify root buttons
     */
    public function _getRootButtons()
    {
        $buttons = parent::_getRootButtons();
        
        /* Change form title */
        $buttons['add']['title'] = 'add_client';
        return $buttons;
    }
}
