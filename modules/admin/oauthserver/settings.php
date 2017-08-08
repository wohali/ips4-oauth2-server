<?php
/**
 * @package OAuth2 Server
 * @author <a href='https://atypical.net'>Joan Touzet</a>
 * @copyright (c) 2017 Joan Touzet
 */

namespace IPS\oauth2server\modules\admin\oauthserver;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return      void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
        parent::execute();
    }

    protected function manage()
    {
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('settings');
        $form = new \IPS\Helpers\Form;
        $form->addHeader( 'oauth2server_settings' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'oauth2server_wrap_global_template', \IPS\Settings::i()->oauth2server_wrap_global_template ) );
        if ( $values = $form->values() )
        {
            $form->saveAsSettings();
        }
        \IPS\Output::i()->output = $form;
    }
}
