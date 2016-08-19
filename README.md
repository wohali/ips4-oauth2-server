# OAuth2 Server for IPS4

This adds an OAuth2 server to your [IPS Community Suite 4](http://invisionpower.com/). This allows external applications to authenticate and authorize against your IPS4 membership. 

Administrators looking to integrate external applications with their IPS4 site via OAuth2 will need to register those applications with the IPS4 Admin Control Panel (ACP). 

## Installation
1. Download the latest version of OAuth2 Server for IPS4 from the [Invision Power Marketplace](https://invisionpower.com/files/).
2. Unzip the archive and locate the ``oauth2server.tar`` file.
3. Navigate to your site's Application list (ACP > System > Site Features > Applications).
4. Click the *Install* button and upload the ``oauth2server.tar`` file.

## Application configuration
1. Visit the settings panel under ACP > Community > OAuth2 Server > Applications.
2. Click the *Add Application* button.
3. Enter your application's name and its Redirect URI. The Redirect URI is typically found in your application's OAuth settings or documentation. Be sure to match whether it is SSL-enabled or not (http:// vs. https://).
4. Click *Save*.
5. Select the user groups you wish to be able to authenticate and authorize 
6. At the list of Applications, click on the Edit pencil.
7. Take note of the application's Client ID, Client Secret, and Authorization, Access Token, and Profile URLs. Your application will need all of these values to complete its OAuth configuration. Be sure to match whether your IPS4 installation is SSL-enabled or not (http:// vs. https://).

## Tested applications

The following applications have been tested against the OAuth Server for IPS4 integration:

* Rocket.Chat (May 2016)


# Technical Details

OAuth2 integration is a 4 step process.

## 1. Client application redirects user to request IPS4 access

### Request
```
GET http(s)://ipboard/applications/oauth2server/interface/oauth/authorize.php?...
```

### Parameters

Name | Type | Description
-----|------|--------------
`client_id`|`string` | **Required**. The client ID generated when creating the application in AdminCP.
`redirect_uri`|`string` | **Required**. The URL in your app where users will be sent after authorization.
`state`|`string` | **Required**. An unguessable random string. It is used to protect against cross-site request forgery attacks.
`response_type`|`string`| **Required**. Value is typically `code`.
`scope`|`string`| Optional. Value is a space (`%2C`) delimited list of requested scopes. Currently supported scopes: `user.profile`, `user.email`, `user.groups`.

### Response

If the user is not logged into IPS4, they will be presented with a login screen. If this is the first time that the user has accessed the OAuth integration from this application, they will be presented with a simple form to authorize the integration (with Yes/No buttons).

## 2. IPS4 redirects back to the application site

Assuming the user picks Yes in the authorization form (or has previously done so), IPS4 will redirect to the Application's Redirect URI, with a temporary code in the `code` parameter and a copy of the `state` parameter previously provided. The application should check the `state` parameter to match; if it does not, the request may have been created by a malicious third party and the process should be aborted.

## 3. Application requests an access token

### Request
```
POST http(s)://ipboard/applications/oauth2server/interface/oauth/token.php
```

### Parameters

Name | Type | Description
-----|------|---------------
`grant_type`|`string`| **Required**. Typical value is `authorization_code`.
`client_id`|`string` | **Required**. The client ID generated when creating the application in the IPS4 AdminCP.
`client_secret`|`string` | **Required**. The client secret generated when creating the application in the IPS4 AdminCP.
`redirect_uri`|`string` | The URL in your app where users will be sent after authorization.
`code`|`string` | **Required**. The code you received as a response in Step 2, above.

### Response
```
{
  "access_token": "abcdefabcdefabcdefabcdefabcdefabcdefabc",
  "expires_in": 3600,
  "token_type": "Bearer",
  "scope": "user.profile user.email",
  "refresh_token": "123456123456123456123456123456123456123"
}
```

## 4. Application uses the access token to retrieve user profile data

### Request
```
GET http(s)://ipboard/applications/oauth2server/interface/oauth/me.php?...
```

### Parameters
Name | Type | Description
-----|------|---------------
`access_token`|`string`| **Required**. The access token provided in Step 3 above.

Note that you can also pass the token in the HTTP Authorization header, e.g.:

```
Authorization: Bearer <access_token>
```

For example, this can be done in curl as follows:

```
curl -H "Authorization: Bearer <access_token>" https://ipboard/applications/oauth2server/interface/oauth/me.php
```

### Response
```
{
  "id": 44,
  "username": "wohali",
  "displayName": "wohali",
  "email": "wohali@website.com",
  "profileUrl": "https:\/\/ipboard\/profile\/44-wohali\/",
  "avatar": "https:\/\/ipboard\/uploads\/profile\/photo-thumb-44.png"
}
```

## Credits
* [OAuth2 Server for IP.Board 3](https://github.com/freezy/ipb-oauth2-server) by [freezy](https://github.com/freezy).
* [OAuth 2.0 Server for PHP](http://bshaffer.github.io/oauth2-server-php-docs/), an excellent OAuth2 library in PHP

## License

GPLv2. See [LICENSE](LICENSE).
