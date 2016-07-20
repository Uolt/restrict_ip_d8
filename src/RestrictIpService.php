<?php

namespace Drupal\restrict_ip;


class RestrictIpService implements RestrictIpServiceInterface {
  /**
   * {@inheritdoc}
   */
  public function whitelistedRegions() {
    return array('sidebar_first');
  }

  /**
   * {@inheritdoc}
   */
  public function whitelistedJsKeys() {
    return array('misc/jquery.once.js');
  }

  /**
   * {@inheritdoc}
   */
  public function accessDeniedPageAlter(&$page) {
    $page['additional_information'] = array(
      '#markup' => t('Additional information to be shown on the Restrict IP Access Denied page'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );
  }
}