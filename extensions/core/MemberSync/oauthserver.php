<?php
/**
 * @brief		Member Sync
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	OAuth2 Server
 * @since		28 May 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\oauth2server\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync
 */
class _oauthserver
{
	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
        // take the easy way out - delete both sets of tokens and force a re-auth
        \IPS\Db::i()->delete('oauth2server_access_tokens', array( 'member_id=?', $member->member_id ) );
        \IPS\Db::i()->delete('oauth2server_refresh_tokens', array( 'member_id=?', $member->member_id ) );
        \IPS\Db::i()->delete('oauth2server_members', array( 'member_id=?', $member->member_id ) );
        \IPS\Db::i()->delete('oauth2server_access_tokens', array( 'member_id=?', $member2->member_id ) );
        \IPS\Db::i()->delete('oauth2server_refresh_tokens', array( 'member_id=?', $member2->member_id ) );
        \IPS\Db::i()->delete('oauth2server_members', array( 'member_id=?', $member2->member_id ) );
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	$member	\IPS\Member	The member
	 * @return	void
	 */
	public function onDelete( $member )
	{
        \IPS\Db::i()->delete('oauth2server_access_tokens', array( 'member_id=?', $member->member_id ) );
        \IPS\Db::i()->delete('oauth2server_refresh_tokens', array( 'member_id=?', $member->member_id ) );
        \IPS\Db::i()->delete('oauth2server_members', array( 'member_id=?', $member->member_id ) );
	}
}
