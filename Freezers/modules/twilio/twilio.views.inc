<?php

/**
 * @file
 * Views integration.
 */

/**
 * Implements hook_views_data().
 */
function twilio_views_data() {

  $data['views']['table']['group'] = t('Twilio');
  $data['views']['table']['join'] = [
    'users' => [
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];
  $data['views']['twilio_user_number'] = [
    'title' => t('Phone number'),
    'help' => t('The phone number associated with the user account.'),
    'field' => [
      'id' => 'twilio_user_number',
    ],
  ];

  $data['views']['twilio_user_country'] = [
    'title' => t('Country code'),
    'help' => t('The country code associated with the user account.'),
    'field' => [
      'id' => 'twilio_user_country',
    ],
  ];

  return $data;
}
