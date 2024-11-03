#LDAP / Active Directory Integration

##Module overview
Drupal LDAP / Active Directory Integration module allows your users to log in to your Drupal site using their LDAP / AD credentials. In addition to LDAP, this module also allows you Windows auto login using NTLM/Kerberos Authentication.
##Resources
For more information review the following resources:

* [Project page](https://www.drupal.org/project/ldap_auth)
* [Module Installation Steps](https://www.drupal.org/docs/contributed-modules/ldap-integration/how-to-install-ldap-active-directory-integration-module)
* [Module Configuration Step](https://www.drupal.org/docs/contributed-modules/ldap-integration/configure-ldap-login-with-drupal)
* [Feature Handbook](https://www.drupal.org/docs/contributed-modules/ldap-integration)
* [Guide to setup NTLM/kerberos single-sign-on](https://plugins.miniorange.com/guide-to-setup-kerberos-single-sign-sso)


## FEATURES & RESOURCES

### LDAP Authentication

| Feature                                                                                              | Description                                                  |
|------------------------------------------------------------------------------------------------------|--------------------------------------------------------------|
| Allow Login with LDAP Server Credentials                                                             | Enable users to log in using their credentials from LDAP.      |
| [User Authentication Restrictions](https://plugins.miniorange.com/drupal-ldap/drupal-login-settings) | Choose authentication options: only Drupal, only LDAP, or Both.|
| LDAP Group-Specific Login                                                                            | Allow only users from specific LDAP groups to log in.          |


### LDAP Mapping Features

| Feature                                                                                                                   | Description                                                                            |
|---------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------|
| [Attribute Mapping](https://www.drupal.org/docs/contributed-modules/ldap-integration/ldap-attribute-mapping) | Map the user LDAP attributes values to the Drupal user fields.                         |
| [Role Mapping](https://www.drupal.org/docs/contributed-modules/ldap-integration/ldap-user-role-mapping)                   | Map the user’s Drupal Role based on the LDAP OU and groups.                            |
| [Group Mapping](https://www.drupal.org/docs/contributed-modules/ldap-integration/ldap-groups-to-drupal-groups-mapping)    | Map the user to Drupal Groups based on their groups in LDAP.                           |
| Profile Mapping                                                                                                           | Map the user’s profile created by the Drupal Profile module with the LDAP information. |

### User Sync / Provisioning Features

| Feature                                                                                                                                                                                                                                                  | Description                                                                                                         |
|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| [Import LDAP Users](https://www.drupal.org/docs/contributed-modules/ldap-integration/import-users-from-ldap) | Import all users from your LDAP / AD Server with a single click (Manually and Scheduler Based). [Youtube Video <img src="https://img.icons8.com/fluent/48/000000/youtube-play.png" width="20">](https://www.youtube.com/watch?v=T7yDZsY-HrM)                    |
| [LDAP Directory and Password Sync Provisioning](https://www.drupal.org/docs/contributed-modules/ldap-integration/ldap-sync-and-provisioning)                                                                                                             | Sync the user information from the LDAP Server to the Drupal website i.e., Create, Delete, Update user information. |
| User attribute mapping during User sync                                                                                                                                                                                                                  | Map the LDAP user's attribute to the Drupal users.                                                                  |
| Role-Based Provisioning                                                                                                                                                                                                                                  | Sync the user’s Drupal role based on the LDAP groups and vice versa.                                                |
| Group-Based Provisioning                                                                                                                                                                                                                                 | Sync the user’s Drupal group based on the LDAP groups and the OU.                                                   |


### NTLM/Kerberos Authentication

| Feature                                         | Description                                                       |
|--------------------------------------------------|-------------------------------------------------------------------|
| Windows SSO Login                                | Automatically logs in to your Drupal site using the currently logged-in Windows user. |






This project has been sponsored by:
* miniOrange Inc\
  miniOrange is a Single Sign-on (SSO) and Identity & Access Management (IAM) provider.
  miniOrange has catered to the security and IAM requirements of government organizations, educational institutions, and NGOs through robust and resilient solutions.

  For more information visit www.miniorange.com, mail us at [drupalsupport@xecurify.com](mailto:drupalsupport@xecurify.com) or call [+1 978 658 9387](tel:+19786589387).
