Extension for TYPO3 CMS Supporting OAuth2 Login
============

What does this extension do?
============

At the moment, this extension allows TYPO3 to integrate with Azure AD using the third-party package "Azure Active Directory Provider for OAuth 2.0 Client" - https://github.com/TheNetworg/oauth2-azure. This third-party package is already specified as a dependency in `composer.json`.

How does this extension work?
============
* This extension initiates the authorization request to the OAuth2 provider.
* Users enter their credentials on the OAuth2 Provider's login page and provide necessary consent.
* The OAuth2 Provider verifies the credentials and, upon success, returns to the TYPO3 website.
* This extension retrieves the user's basic information, such as first name, surname, and email, from the OAuth2 provider.
* If the user does not exist in the TYPO3 system, the extension creates a new user and enables their login session.
* If the user already exists, it enables their login session.
* Users are then redirected to a preset page.

Installation
============

The installation process is straightforward and involves the following steps:

1. Run `composer req wiminno/oauth2-login`
2. Configure the following values in `Settings > Extension Configuration > oauth2_login > Azure`:
   * Azure Client Id - Obtain from your Azure Application.
   * Azure Client Secret - Obtain from your Azure Application.
   * Azure Tenant Id - Obtain from your Azure Application (For a multi-tenant application you may leave it as empty).
   * Callback/Redirect URL for Azure - Recommended to set as `https://{your-host}/oauth2/callback`. Ensure the same value is set within your Azure Application.
3. Configure the following values in `Settings > Extension Configuration > oauth2_login > User`:
   * Frontend Users Storage Folder - Set the PID value of a storage folder for the Frontend User.
   * Group(s) to be assigned for Frontend Users (comma separated) - Frontend Group UIDs to be assigned for the Frontend User.
   * Redirect URL after successful login - Full URL of the TYPO3 page where the redirect will occur after successful login.
4. Define the slug of the authorization URL and Callback/Redirect URL in `config.yaml` of your site config. Do not change the map value.
   <pre>
   routeEnhancers:
      PageTypeSuffix:
          type: PageType
          default: '/'
          index: ''
          map:
            oauth2/authorize: 11557601
            oauth2/callback: 11557602</pre>
6. In the login template, include the authorization URL as a link, for example:<br />
   `<a href="/oauth2/authorize" class="button button-default button-primary">Login with Azure</a>`
