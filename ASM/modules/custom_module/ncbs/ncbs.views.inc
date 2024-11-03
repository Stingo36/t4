<?php 

/**
 * Implements hook_views_data().
 */
function ncbs_views_data() {
    $data = [];

    // Check if the current user has the 'admin' or 'administrator' role
    $current_user = \Drupal::currentUser();
    if ($current_user->hasRole('admin') || $current_user->hasRole('administrator')) {
        // Add the field only if the user has the required role
        $data['views']['all_applications_comments'] = [
            'title' => \Drupal::translation()->translate('All Application Comments'),
            'help' => \Drupal::translation()->translate('All Application Comments'),
            'field' => [
                'id' => 'all_application_comments',
            ],
        ];
    }

    $data['views']['ncbs_custom_field'] = [
        'title' => t('Add Comments'),
        'help' => t('This is a custom field added by the NCBS module.'),
        'field' => [
          'id' => 'ncbs_custom_field',
        ],
      ];

    $data['views']['send_email'] = [
        'title' => t('Send Email'),
        'help' => t('This is a custom field added by the NCBS module.'),
        'field' => [
          'id' => 'send_email',
        ],
      ];
    $data['views']['send_referee_email'] = [
        'title' => t('Send Referee Email'),
        'help' => t('This is a custom field added by the NCBS module.'),
        'field' => [
          'id' => 'send_referee_email',
        ],
      ];

    return $data;
}
