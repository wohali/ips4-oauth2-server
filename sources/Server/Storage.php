<?php

namespace IPS\oauth2server;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

require_once str_replace( 'Server/Storage.php', 'OAuth2/Autoloader.php', str_replace( '\\', '/', __FILE__ ) );
\OAuth2\Autoloader::register();

use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\RefreshTokenInterface;


class Storage implements AccessTokenInterface, ClientCredentialsInterface, AuthorizationCodeInterface, RefreshTokenInterface {

    /**
     * IPB's database interface
     * @var interfaceDb
     */
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Look up the supplied oauth_token from storage.
     *
     * We need to retrieve access token data as we create and verify tokens.
     *
     * @param string $oauth_token OAuth token to be check with.
     * @return array
     * An associative array as below, and return NULL if the supplied oauth_token
     * is invalid:
     * - expires: Stored expiration in unix timestamp.
     * - client_id: (optional) Stored client identifier.
     * - user_id: Stored user identifier.
     * - scope: (optional) Stored scope values in space-separated string.
     * - id_token: (optional) Stored id_token (if "use_openid_connect" is true).
     */
    public function getAccessToken($oauth_token) {
        try {
            $token = $this->db->select( '*', 'oauth2server_access_tokens', array( 'access_token=?', $oauth_token ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return NULL;
        }
        if ($token) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
            $token['user_id'] = $token['member_id'];
        }
        return $token;
    }

    /**
     * Store the supplied access token values to storage.
     *
     * We need to store access token data as we create and verify tokens.
     *
     * @param string $access_token oauth_token to be stored.
     * @param string $client_id Client identifier to be stored.
     * @param int $user_id User identifier to be stored.
     * @param int $expires Expiration to be stored as a Unix timestamp.
     * @param string $scope (optional) Scopes to be stored in space-separated string.
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {

        $expires = date('Y-m-d H:i:s', $expires);
        $member_id = intval($user_id);
        $token = compact( 'access_token', 'client_id', 'member_id', 'expires', 'scope' );
        if ( $this->getAccessToken( $access_token ) ) {
            $this->db->update( 'oauth2server_access_tokens', $token, array( "access_token=?", $access_token ) );
        } else {
            $this->db->insert( 'oauth2server_access_tokens', $token );
        }
    }

    /**
     * Fetch authorization code data (probably the most common grant type).
     *
     * Retrieve the stored data for the given authorization code.
     *
     * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
     *
     * @param string $authorization_code Authorization code to be check with.
     * @return array An associative array as below, and NULL if the code is invalid
     * @code
     * return array(
     *     "client_id"    => CLIENT_ID,      // REQUIRED Stored client identifier
     *     "user_id"      => USER_ID,        // REQUIRED Stored user identifier
     *     "expires"      => EXPIRES,        // REQUIRED Stored expiration in unix timestamp
     *     "redirect_uri" => REDIRECT_URI,   // REQUIRED Stored redirect URI
     *     "scope"        => SCOPE,          // OPTIONAL Stored scope values in space-separated string
     * );
     * @endcode
     * @see http://tools.ietf.org/html/rfc6749#section-4.1
     */
    public function getAuthorizationCode($authorization_code) {
        try {
            $code = $this->db->select( '*', 'oauth2server_authorization_codes', array( 'authorization_code=?', $authorization_code ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return NULL;
        }
        if ($code) {
            // convert date string back to timestamp
            $code['expires'] = strtotime($code['expires']);
            $code['user_id'] = $code['member_id'];
        }
        return $code;
    }

    /**
     * Take the provided authorization code values and store them somewhere.
     *
     * This function should be the storage counterpart to getAuthCode().
     *
     * If storage fails for some reason, we're not currently checking for
     * any sort of success/failure, so you should bail out of the script
     * and provide a descriptive fail message.
     *
     * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
     *
     * @param string $authorization_code Authorization code to be stored.
     * @param string $client_id Client identifier to be stored.
     * @param int $user_id User identifier to be stored.
     * @param string $redirect_uri Redirect URI(s) to be stored in a space-separated string.
     * @param int $expires Expiration to be stored as a Unix timestamp.
     * @param string $scope
     * (optional) Scopes to be stored in space-separated string.
     */
    public function setAuthorizationCode($authorization_code, $client_id, $user_id, $redirect_uri, $expires, $scope = null) {
        if (func_num_args() > 6) {
            // we are calling with an id token
            call_user_func_array(array($this, 'setAuthorizationCodeWithIdToken'), func_get_args());
            return;
        }

        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        $member_id = intval($user_id);
        $code = compact('authorization_code', 'client_id', 'member_id', 'redirect_uri', 'expires', 'scope');

        // if it exists, update it.
        if ($this->getAuthorizationCode($authorization_code)) {
            $this->db->update('oauth2server_authorization_codes', $code, array( 'authorization_code=?', $authorization_code ) );
        } else {
            $this->db->insert('oauth2server_authorization_codes', $code);
        }
    }

    private function setAuthorizationCodeWithIdToken($authorization_code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null) {
        // convert expires to datestring
        $member_id = intval($user_id);
        $expires = date('Y-m-d H:i:s', $expires);
        $code = compact('authorization_code', 'client_id', 'member_id', 'redirect_uri', 'expires', 'scope', 'id_token');

        // if it exists, update it.
        if ($this->getAuthorizationCode($authorization_code)) {
            $this->db->update('oauth2server_authorization_codes', $code, array( 'authorization_code=?', $authorization_code ) );
        } else {
            $this->db->insert('oauth2server_authorization_codes', $code);
        }
    }

    /**
     * Once an Authorization Code is used, it must be expired
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.2
     *
     *    The client MUST NOT use the authorization code
     *    more than once.  If an authorization code is used more than
     *    once, the authorization server MUST deny the request and SHOULD
     *    revoke (when possible) all tokens previously issued based on
     *    that authorization code
     *
     */
    public function expireAuthorizationCode($code) {
        $this->db->delete( 'oauth2server_authorization_codes', array( 'authorization_code=?', $code ) );
    }

    /**
     * Make sure that the client credentials is valid.
     *
     * @param string $client_id Client identifier to be check with.
     * @param string $client_secret (optional) If a secret is required, check that they've given the right one.
     * @return boolean TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
     * @see http://tools.ietf.org/html/rfc6749#section-3.1
     */
    public function checkClientCredentials($client_id, $client_secret = null) {
        try {
            $client = $this->db->select( '*', 'oauth2server_clients', array( 'client_id=?', $client_id ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return FALSE;
        }

        // make this extensible
        return $client && $client['client_secret'] == $client_secret;
    }

    /**
     * Determine if the client is a "public" client, and therefore
     * does not require passing credentials for certain grant types
     *
     * @param $client_id
     * Client identifier to be check with.
     *
     * @return boolean TRUE if the client is public, and FALSE if it isn't.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-2.3
     * @see https://github.com/bshaffer/oauth2-server-php/issues/257
     */
    public function isPublicClient($client_id) {
        try {
            $client = $this->db->select( '*', 'oauth2server_clients', array( 'client_id=?', $client_id ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return FALSE;
        }

        return empty($client['client_secret']);
    }

    /**
     * Get client details corresponding client_id.
     *
     * OAuth says we should store request URIs for each registered client.
     * Implement this function to grab the stored URI for a given client id.
     *
     * @param string $client_id Client identifier to be check with.
     *
     * @return array Client details. The only mandatory key in the array is "redirect_uri".
     *     This function MUST return FALSE if the given client does not exist or is
     *      invalid. "redirect_uri" can be space-delimited to allow for multiple valid uris.
     * @code
     * return array(
     *     "redirect_uri" => REDIRECT_URI,      // REQUIRED redirect_uri registered for the client
     *     "client_id"    => CLIENT_ID,         // OPTIONAL the client id
     *     "grant_types"  => GRANT_TYPES,       // OPTIONAL an array of restricted grant types
     *     "user_id"      => USER_ID,           // OPTIONAL the user identifier associated with this client
     *     "scope"        => SCOPE,             // OPTIONAL the scopes allowed for this client
     * );
     * @endcode
     */
    public function getClientDetails($client_id) {
        try {
            $ret = $this->db->select( '*', 'oauth2server_clients', array( 'client_id=?', $client_id ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return FALSE;
        }
        return $ret;
    }

    /**
     * Get the scope associated with this client
     *
     * @param string $client_id client ID
     * @return string the space-delineated scope list for the specified client_id
     */
    public function getClientScope($client_id) {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }
        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }
        return null;
    }

    /**
     * Check restricted grant types of corresponding client identifier.
     *
     * If you want to restrict clients to certain grant types, override this
     * function.
     *
     * @param string $client_id Client identifier to be check with.
     * @param string $grant_type Grant type to be check with
     *
     * @return boolean TRUE if the grant type is supported by this client identifier, and
     *                 FALSE if it isn't.
     */
    public function checkRestrictedGrantType($client_id, $grant_type) {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array) $grant_types);
        }
        // if grant_types are not defined, then none are restricted
        return true;
    }

    /**
     * Grant refresh access tokens.
     *
     * Retrieve the stored data for the given refresh token.
     *
     * Required for OAuth2::GRANT_TYPE_REFRESH_TOKEN.
     *
     * Returns an associative array as below, and NULL if the refresh_token is
     * invalid:
     * - refresh_token: Refresh token identifier.
     * - client_id: Client identifier.
     * - user_id: User identifier.
     * - expires: Expiration unix timestamp, or 0 if the token doesn't expire.
     * - scope: (optional) Scope values in space-separated string.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-6
     * @param string $refresh_token Refresh token to be check with.
     * @return array
     */
    public function getRefreshToken($refresh_token) {
        try {
            $token = $this->db->select( '*', 'oauth2server_refresh_tokens', array( 'refresh_token=?', $refresh_token ) )->first();
        }
        catch ( \UnderflowException $e ) {
            return NULL;
        }
        if ($token) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
            $token['user_id'] = $token['member_id'];
        }
        return $token;
    }

    /**
     * Take the provided refresh token values and store them somewhere.
     *
     * This function should be the storage counterpart to getRefreshToken().
     *
     * If storage fails for some reason, we're not currently checking for
     * any sort of success/failure, so you should bail out of the script
     * and provide a descriptive fail message.
     *
     * Required for OAuth2::GRANT_TYPE_REFRESH_TOKEN.
     *
     * @param string $refresh_token Refresh token to be stored.
     * @param string $client_id Client identifier to be stored.
     * @param string $user_id User identifier to be stored.
     * @param string $expires Expiration timestamp to be stored. 0 if the token doesn't expire.
     * @param string $scope (optional) Scopes to be stored in space-separated string.
     */
    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {

        $expires = date( 'Y-m-d H:i:s', $expires );
        $member_id = intval( $user_id );
        $token = compact( 'refresh_token', 'client_id', 'member_id', 'expires', 'scope' );
        $this->db->insert( 'oauth2server_refresh_tokens', $token );
    }

    /**
     * Expire a used refresh token.
     *
     * This is not explicitly required in the spec, but is almost implied.
     * After granting a new refresh token, the old one is no longer useful and
     * so should be forcibly expired in the data store so it can't be used again.
     *
     * If storage fails for some reason, we're not currently checking for
     * any sort of success/failure, so you should bail out of the script
     * and provide a descriptive fail message.
     *
     * @param string $refresh_token Refresh token to be expired.
     */
    public function unsetRefreshToken($refresh_token) {
        $this->db->delete( 'oauth2server_refresh_tokens', array( 'refresh_token=?', $refresh_token ) );
    }

    /**
     * Checks whether a member already confirmed OAuth access for a given application.
     *
     * @param string $client_id Client ID of your OAuth2 application
     * @param string $user_id Member ID from IPB
     * @param string $scope Scopes, separed by space (still unused)
     * @return boolean
     */
    public function hasAuthorization($client_id, $user_id, $scope) {
        $member_id = intval($user_id);
        $ret = $this->db->select( '*', 'oauth2server_members', array( 'client_id=? AND member_id=?', $client_id, $member_id ) );
        return $ret->count() == 1;
    }

    /**
     * Sets Authorization for a given member and application.
     *
     * @param string $client_id Client ID of your OAuth2 application
     * @param string $user_id Member ID from IPB
     * @param string $scope Scopes, separed by space (still unused)
     */
    public function setAuthorization($client_id, $user_id, $scope) {
        $created_at = date( 'Y-m-d H:i:s' );
        $member_id = intval( $user_id );
        $row = compact( 'client_id', 'member_id', 'created_at', 'scope' );
        $this->db->insert( 'oauth2server_members', $row );
    }
}
