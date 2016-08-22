<?php
/**
 * @package     OAuth2 Server
 * @author      Joan Touzet
 * @copyright   (c) 2016 Joan Touzet
 */

namespace IPS\oauth2server;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Client Node
 */
class _Client extends \IPS\Node\Model implements \IPS\Node\Permissions
{
    protected static $multitons;
    public static $databaseColumnId = 'node_id';
    protected static $databaseIdFields = array('client_id');
    public static $databaseTable = 'oauth2server_clients';
    public static $databasePrefix = '';
    public static $databaseColumnOrder = NULL;
    public static $databaseColumnParent = NULL;
    public static $nodeTitle = 'module__oauth2server_client';
    public static $subnodeClass = NULL;
    public static $nodeSortable = FALSE;

    /**
     * @brief   [Node] ACP Restrictions
     * @code
        array(
            'app'       => 'core',              // The application key which holds the restrictrions
            'module'    => 'foo',               // The module key which holds the restrictions
            'map'       => array(               // [Optional] The key for each restriction - can alternatively use "prefix"
                'add'           => 'foo_add',
                'edit'          => 'foo_edit',
                'permissions'   => 'foo_perms',
                'delete'        => 'foo_delete'
            ),
            'all'       => 'foo_manage',        // [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
            'prefix'    => 'foo_',              // [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
     * @encode
     */
    protected static $restrictions = array(
        'app'       => 'oauth2server',
        'module'    => 'oauthserver',
        'all'       => 'clients_manage'
    );

    /**
     * @brief   [Node] App for permission index
     */
    public static $permApp = 'oauth2server';
    
    /**
     * @brief   [Node] Type for permission index
     */
    public static $permType = 'oauth2server';
    
    /**
     * @brief   The map of permission columns
     */
    public static $permissionMap = array(
        'access'          => 'view'
    );  

    /**
     * @brief   [Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
     */
    public static $titleLangPrefix = 'oauth2server_client_';
    
    /**
     * @brief   [Node] Prefix string that is automatically prepended to permission matrix language strings
     */
    public static $permissionLangPrefix = 'perm_oauth2server_';

    /**
     * [Node] Get whether or not this node is enabled
     *
     * @note    Return value NULL indicates the node cannot be enabled/disabled
     * @return  bool|null
     */
    protected function get__enabled()
    {
        return $this->open;
    }
    
    /**
     * Get client options
     */
    protected function get__options()
    {
        $options = NULL;
       
        /* Decode options */
        if( $this->options )
        {
            $options = json_decode( $this->options );
        }

        return $options;
    }

    /**
     * [Node] Get Node Title
     *
     * @return  string|null
     */
    protected function get__title()
    {
        return $this->client_name;
    }
     
    /**
     * [Node] Get Node Description
     *
     * @return  string|null
     */
    protected function get__description()
    {
        return $this->redirect_uri;
    }

    /**
     * Load and check permissions
     *
     * @param   mixed   $id     ID
     * @param   string  $perm   Permission Key
     * @return  static
     * @throws  \OutOfRangeException
     */
    public static function _loadAndCheckPerms( $id, $perm='view' )
    {
        $node = parent::loadAndCheckPerms( $id, $perm );
        
        if ( !$node->open and !\IPS\Member::loggedIn()->isAdmin() )
        {
            throw new \OutOfRangeException;
        }
        
        return $node;
    }

    /**
     * [ActiveRecord] Delete Record
     *
     * @return  void
     */
    public function delete()
    {
        \IPS\Db::i()->delete('oauth2server_access_tokens', array( 'client_id=?', $this->client_id ) );
        \IPS\Db::i()->delete('oauth2server_authorization_codes', array( 'client_id=?', $this->client_id ) );
        \IPS\Db::i()->delete('oauth2server_members', array( 'client_id=?', $this->client_id ) );
        \IPS\Db::i()->delete('oauth2server_refresh_tokens', array( 'client_id=?', $this->client_id ) );

        return parent::delete();
    }

    /**
     * [Node] Add/Edit Form
     *
     * @param   \IPS\Helpers\Form   $form   The form
     * @return  void
     */
    public function form( &$form )
    {
        // must pass as reference :(
        $strong = TRUE;

        $form->add( new \IPS\Helpers\Form\Text( 'client_name', $this->client_id ? $this->client_name : '', TRUE, array( 'maxLength' => 255 ) ) );
        $form->add( new \IPS\Helpers\Form\Text( 'redirect_uri', $this->client_id ? $this->redirect_uri : '', TRUE, array( 'maxLength' => 2000 ) ) );

        $form->add( new \IPS\Helpers\Form\CheckboxSet( 'scope', $this->client_id ? explode( ' ', $this->scope) : array( 'user.profile' ), TRUE, array(
            'options' => array( 'user.profile' => 'scope_user.profile', 'user.email' => 'scope_user.email', 'user.groups' => 'scope_user.groups', 'user.reputation' => 'scope_user.reputation' ),
        ) ) );

        $form->hiddenValues['client_id'] = $this->_id ? $this->client_id : mb_substr(md5(openssl_random_pseudo_bytes(20, $strong)), 0, 20);
        $form->hiddenValues['client_secret'] = $this->_id ? $this->client_secret : mb_substr(md5(openssl_random_pseudo_bytes(40, $strong)), 0, 40);
        $form->hiddenValues['member_id'] = \IPS\Member::loggedIn()->member_id;
        // $form->hiddenValues['scope'] = 'user.email user.profile';
        $form->hiddenValues['grant_types'] = 'authorization_code implicit';

        if ( $this->_id ) {
            $form->addHtml( "<p><b>Set these parameters in your application's OAuth configuration:</b></p><dl>" );
            $form->addSeparator();
            $form->addHtml( "<dt><b>" . \IPS\Member::loggedIn()->language()->addToStack( 'client_id' ) . "</b></dt>" );
            $form->addHtml( "<dd><p><tt>" . $form->hiddenValues['client_id'] . "</tt></p></dd>" );
            $form->addSeparator();
            $form->addHtml( "<dt><b>" . \IPS\Member::loggedIn()->language()->addToStack( 'client_secret' ) . "</b></dt>" );
            $form->addHtml( "<dd><p><tt>" . $form->hiddenValues['client_secret'] . "</tt></p></dd>" );
            $form->addSeparator();
            $form->addHtml( "<dt><b>" . \IPS\Member::loggedIn()->language()->addToStack( 'authorization_url' ) . "</b></dt>" );
            $form->addHtml( "<dd><p><tt>" . \IPS\Settings::i()->base_url . "applications/oauth2server/interface/authorize.php</tt></p></dd>" );
            $form->addSeparator();
            $form->addHtml( "<dt><b>" . \IPS\Member::loggedIn()->language()->addToStack( 'access_token_url' ) . "</b></dt>" );
            $form->addHtml( "<dd><p><tt>" . \IPS\Settings::i()->base_url . "applications/oauth2server/interface/token.php</tt></p></dd>" );
            $form->addSeparator();
            $form->addHtml( "<dt><b>" . \IPS\Member::loggedIn()->language()->addToStack( 'profile_url' ) . "</b></dt>" );
            $form->addHtml( "<dd><p><tt>" . \IPS\Settings::i()->base_url . "applications/oauth2server/interface/me.php</tt></p></dd>");
            $form->addSeparator();
            $form->addHtml("</dl>");
        }
    }

    public function saveForm( $values )
    {
        $values[ 'scope' ] = implode ( ' ', $values[ 'scope' ] );
        parent::saveForm( $values );
    }

}
