<?php

namespace Drupal\smssystem\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of SMS Message Template entities.
 *
 * @ingroup smssystem
 */
class SmsMessageTemplateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['template_name'] = $this->t('Template Name');
    $header['template_text'] = $this->t('Template Message');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\smssystem\Entity\SmsMessageTemplate $entity */
    $row['template_name'] = $entity->get('template_name')->value;
    // Display SMS Message template body text in raw format.
    $row['template_text'] = [
      'data' => [
        '#markup' => '<pre>' . $entity->get('template_text')->value . '</pre>',
      ],
    ];
    return $row + parent::buildRow($entity);
  }

}
