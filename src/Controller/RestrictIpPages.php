<?php

namespace Drupal\restrict_ip\Controller;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\restrict_ip\RestrictIpServiceInterface;

/**
 * Report controller of User Expire module.
 */
class RestrictIpPages extends ControllerBase {

  /**
   * The restrict ip settings.
   *
   * @var \Drupal\restrict_ip\Controller\RestrictIpPages
   */
  protected $settings;

  /**
   * The restrict ip settings.
   *
   * @var \Drupal\restrict_ip\RestrictIpService
   */
  protected $restrictIpService;

  /**
   * Constructs a \Drupal\user_expire\Controller object.
   *
   * @param \Drupal\restrict_ip\RestrictIpServiceInterface $restrict_ip_service
   */
  public function __construct(RestrictIpServiceInterface $restrict_ip_service) {
    $this->settings = $this->config('restrict_ip.settings');
    $this->restrictIpService = $restrict_ip_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('restrict_ip.service')
    );
  }

  /**
   * Callback path for restrict_ip/access_denied
   *
   * Redirects user to the front page if they have been
   * whitelisted. Otherwise shows an access denied error.
   *
   * {@inheritdoc}
   */
  public function accessDenied() {
    if (!ip_restricted()) {
      $this->redirect('<front>');
    }

    $page['access_denied'] = array(
      '#markup' => $this->t('This site cannot be accessed from your IP address.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );

    $contact_mail = trim($this->settings->get('mail_address'));
    if (strlen($contact_mail)) {
      $contact_mail = str_replace('@', '[at]', $contact_mail);
      $page['contact_us'] = array(
        '#markup' => $this->t('If you feel this is in error, please contact an administrator at !email.', array('!email' => '<span id="restrict_ip_contact_mail">' . $contact_mail . '</span>')),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
    }

    if ($this->settings->get('allow_role_bypass')) {
      if (\Drupal::currentUser()->isAuthenticated()) {
        $page['logout_link'] = array(
          '#markup' => l($this->t('Logout'), 'user/logout'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        );
      }
      elseif ($this->settings->get('login_link_denied_page')) {
        $page['login_link'] = array (
          '#markup' => l($this->t('Sign in'), 'user/login'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        );
      }
    }

    $page['#attached']['js'][] = array(
      'type' => 'file',
      'data' => drupal_get_path('module', 'restrict_ip') . '/js/restrict_ip.js',
    );

    $this->restrictIpService->accessDeniedPageAlter($page);

    return $page;
  }
}
