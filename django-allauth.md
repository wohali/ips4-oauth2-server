# Using the OAuth2 Server to integration IPS4 with Django Allauth

Django is a complete high-level web framework and when it's about signup and login, django-allauth is used. To integrate IPS4 with django-allauth is quite hard, but here's the explaination.

**NOTE**: "mysitename" and "MySiteName" need to be the same for every configuration steps listed below.

## Create a new custom provider in django-allauth (Step 1)

- Go to ```/allauth/socialaccount/providers``` and create a folder with your "mysitename".

## Configure your IPS4 Site (Step 2)

- Go to ACP > Community > OAuth Server > Applications > **Add Application**.
- Enter a unique name for the integration (e.g. My Django App) and the Callback URL of django-allauth which is **http://djangoapp.example.com/accounts/mysitename/login/callback/**.
- Check the boxes for **User profile** and **User email address**.
- Click **Save**.
- Select the user groups you wish to be able to authenticate against IPS4 for this integration. **Do not select Guest! :)**
- Click Save.
- Click on the Edit pencil again and take note of the **Client ID, Client Secret, and 3 integration URLs**.

## Code 4 files in django-allauth (Step 3)

**NOTE**: You're free to make some adjustments, then let us know here: https://github.com/wohali/ips4-oauth2-server/

- Create 4 files: ```provider.py```, ```test.py``` (optional), ```urls.py``` and ```views.py```.
- Fill the files with this sample code, change the part marked with **# Editing required** and replace "MySiteName" with your desired name in all files:

***provider.py***
```
from allauth.socialaccount import providers
from allauth.socialaccount.providers.base import ProviderAccount
from allauth.socialaccount.providers.oauth2.provider import OAuth2Provider


class MySiteNameAccount(ProviderAccount):
    def get_profile_url(self):
        return self.account.extra_data.get('profileUrl')

    def get_avatar_url(self):
        return self.account.extra_data.get('avatar')

    def to_str(self):
        dflt = super(MySiteNameAccount, self).to_str()
        return self.account.extra_data.get('username', dflt)


class MySiteNameProvider(OAuth2Provider):
    id = 'MySiteName'
    name = 'Site Name'
    account_class = MySiteNameAccount

    def extract_uid(self, data):
        return str(data['id'])

    def extract_common_fields(self, data):
        return dict(username=data.get('username'),
                    name=data.get('displayName'),
                    email=data.get('email'))

    def get_default_scope(self):
        scope = []
        return scope


providers.registry.register(MySiteNameProvider)
```
***test.py*** *(optional)*
It uses the example provided in the readme!
```
from allauth.socialaccount.tests import OAuth2TestsMixin
from allauth.tests import MockedResponse, TestCase

from .provider import MySiteNameProvider

class MySiteNameTests(OAuth2TestsMixin, TestCase):
    provider_id = MySiteNameProvider.id

    def get_mocked_response(self):
        return MockedResponse(200, """
{
  "id": 44,
  "username": "wohali",
  "displayName": "wohali",
  "email": "wohali@website.com",
  "profileUrl": "https:\/\/ipboard\/profile\/44-wohali\/",
  "avatar": "https:\/\/ipboard\/uploads\/profile\/photo-thumb-44.png"
}

""")
```
***urls.py***
```
from allauth.socialaccount.providers.oauth2.urls import default_urlpatterns

from .provider import MySiteNameProvider

urlpatterns = default_urlpatterns(MySiteNameProvider)
```
***views.py***
```
import requests

from allauth.socialaccount.providers.oauth2.views import (OAuth2Adapter,
                                                          OAuth2LoginView,
                                                          OAuth2CallbackView)

from .provider import MySiteNameProvider
from allauth.socialaccount import app_settings


class MySiteNameOAuth2Adapter(OAuth2Adapter):
    provider_id = MySiteNameProvider.id
    access_token_url = 'http://example.com/applications/oauth2server/interface/oauth/token.php' # Editing required"
    authorize_url = 'http://example.com/applications/oauth2server/interface/oauth/authorize.php' # Editing required"
    profile_url = 'http://example.com/applications/oauth2server/interface/oauth/me.php' # Editing required"

    # After successfully logging in, use access token to retrieve user info
    def complete_login(self, request, app, token, **kwargs):
        resp = requests.get(self.profile_url,
                            params={'access_token': token.token})
        extra_data = resp.json()
        if app_settings.QUERY_EMAIL and not extra_data.get('email'):
            extra_data['email'] = self.get_email(token)
        return self.get_provider().sociallogin_from_response(request,
                                                             extra_data)

oauth2_login = OAuth2LoginView.adapter_view(MySiteNameOAuth2Adapter)
oauth2_callback = OAuth2CallbackView.adapter_view(MySiteNameOAuth2Adapter)
```
- Go to your django project database and run these SQL codes, but first edit the values like ```DOMAIN```, ```NAME```, ```PROVIDER```, ```SECRET_KEY``` and ```CLIENT_ID```
```
UPDATE django_site SET domain = 'djangoapp.example.com', name = 'Site Name' WHERE id=1;
```
```
INSERT INTO socialaccount_socialapp (provider, name, secret, client_id, 'key')
       VALUES ("mysitename", "MySiteName", "---your Client Secret from IPS4---",
               "---your Client ID from IPS4---", "");
```
```
INSERT INTO socialaccount_socialapp_sites (socialapp_id, site_id)
       VALUES (1,1);
```
- (Optional) You can disable the email verification for all the accounts created with the OAuth2Adapter just typing this code in your ```settings.py``` of your app project.
```
SOCIALACCOUNT_PROVIDERS =  {'mysitename':
                                  {'VERIFIED_EMAIL': True }}
```

At the moment Django Allauth has no **easy** way to disable the signup form and the sign in form without editing the source code or creating new custom files.

1. For the sign up form, take a look at allauth doc here: http://django-allauth.readthedocs.io/en/latest/advanced.html#creating-and-populating-user-instances, in particular the function ```is_open_for_signup(self, request)```
2. For the sign in form, you must invent something

Here you can find the feature request for Allauth, just subscribe to get the last notifications: https://github.com/pennersr/django-allauth/issues/345

