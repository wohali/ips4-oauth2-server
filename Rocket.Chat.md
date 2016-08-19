# Using the OAuth2 Server to integration IPS4 with Rocket.Chat

It's simple to configure Rocket.Chat to use your IPS OAuth2 server, creating a single-sign-on solution for your Rocket.Chat instance. Here's how.

## Configure Rocket.Chat (Pass 1)

1. Install and setup Rocket.Chat. A walkthrough is beyond the scope of this guide.
2. Create an initial administrator user and password, then login as that administrator.
3. Administration > OAuth > Add Custom OAuth button (at the very bottom). Enter a unique identifier for this integration (ex: ips4)
4. Find the **Custom OAuth: <IPS4>** section and expand it. Make a note of the Callback URL, e.g. `https://your.rocketchat.com/_oauth/ips4`

## Configure your IPS4 Site

1. ACP > Community > OAuth Server > Applications > **Add Application**.
2. Enter a unique name for the integration (e.g. Rocket.Chat) and the Callback URL you obtained from Rocket.Chat above.
3. Check the boxes for **User profile** and **User email address**.
4. Click **Save**.
5. Select the user groups you wish to be able to authenticate against IPS4 for this integration. **Do not select Guest! :)**
6. Click Save.
7. Click on the Edit pencil again and take note of the **Client ID, Client Secret, and 3 integration URLs**.

## Configure Rocket.Chat (Pass 2)
1. Change the following settings:
  *  **Enabled**: True
  *   **URL**: Top-level URL for your IPS4 site, such as `https://your.ips4.com/`. If your site is not installed at the root of the webserver, include any subdirectory here, such as `https://mysite.com/ips4`.
  *   **Token Path**: `applications/oauth2server/interface/oauth/token.php`
  *   **Identity Path**: `applications/oauth2server/interface/oauth/me.php`
  *   **Authorization Path**: `applications/oauth2server/interface/oauth/authorize.php`
  *   **Token sent via**: Payload or Header both work, it doesn't matter.
  *   **ID**: your Client ID from IPS4 here
  *   **Secret**: your Client Secret from IPS4 here
  *   **Scope**: `user.email user.profile`
  *   **Login Style**: I recommend Popup
  *   **Button** settings as you wish.
2. You may have to restart the Rocket.Chat server at this point. I had to, but I cannot guarantee that is is mandatory.
3. Logout as Administrator and use the new button to log in as an IPS4 user. If this works _your installation is working, congratulations!_
4. Log back in as the Administrator user and give admin access to your IPS4 user (`#general` chat > people icon on the right > click on user > MAKE ADMIN).
5. (Optional) Disable username/password login for Rocket.Chat: Administration > Accounts > Show Form-based Login set to **False. _WARNING_: Do not do this until you have made at least one IPS4 user an administrator or you will lose admin access to your Rocket.Chat server! :(**

