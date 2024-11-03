CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------

 This is a service which is used for sending SMS messages by specific templates.

REQUIREMENTS
-------------------

 * date_popup
 * token
 * views
 * views_data_export

INSTALLATION
------------

 * Install as usual, see
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8 for further
   information.
 * It has an internal drupal service for this purpose. Here are examples:

```php
$sms_service = \Drupal::service('smssystem.send_sms');

// Send a simple sms to a recipient with a message.
$sms_service->sendSms('+37369123456', 'Hello! This is a test SMS message!');

// Send a sms to a recipient with a given template.
// Available templates are here: /admin/config/system/smssystem/templates/list.
$sms_service->sendSmsByTemplate('order_completed', '+37369123456');

// Send an SMS to the queue. The queue name "sms_send_processing".
// Available queues list: /admin/config/system/smssystem/sms-queue-list.
$sms_service->sendSms('+37369123456', 'Hello!', TRUE);
$sms_service->sendSmsByTemplate('order_place', '+37369123456', TRUE);
```

CONFIGURATION
-------------

* Admin main config page: `/admin/config/system/smssystem`
* Admin API config page: `/admin/config/system/smssystem/api`
* SMS Message templates page: `/admin/config/system/smssystem/templates/list`
* Reporting page: `/admin/config/system/smssystem/reporting`
* SMS Queue list page: `/admin/config/system/smssystem/sms-queue-list`

TROUBLESHOOTING
---------------

 * For troubleshooting, visit this page `/admin/reports/dblog`.


MAINTAINERS
-----------

Current maintainers:

* Vesterli Andrei (andreivesterli)
