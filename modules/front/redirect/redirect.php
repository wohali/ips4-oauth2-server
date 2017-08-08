<?php

namespace IPS\oauth2server\modules\front\redirect;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
  header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
  exit;
}

/**
 * redirect
 */
class _redirect extends \IPS\Dispatcher\Controller
{
  /**
   * Execute
   *
   * @return	void
   */
  public function execute()
  {
    parent::execute();
  }

  /**
   * Redirect a user to the specified URL.
   *
   * @param string $ref The URL to which the member will be redirected, base64 encoded.
   * @return	void
   */
  protected function manage()
  {
    $ref = \IPS\Request::i()->ref;
    /* Did we just log in? */
    if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->_fromLogin ) ) {
      \IPS\Output::i()->redirect( base64_decode($ref) );
    } else {
      \IPS\Output::i()->redirect( \IPS\Http\Url::internal('') );
    }
  }
}
