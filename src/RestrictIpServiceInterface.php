<?php

namespace Drupal\restrict_ip;

/**
 * Interface RestrictIpServiceInterface.
 * Hooks provided by the Restrict IP module.
 */
interface RestrictIpServiceInterface {
  /**
   * Add regions to be whitelisted even when the user has been denied access
   *
   * @return
   *   An array of keys representing regions to be allowed even when
   *   the user is denied access by IP. These keys can be found by
   *   in the .info file for the theme, as region[KEY] = Region Name
   *   where KEY is the key to be returned in the return array
   *   of this function.
   */
  public function whitelistedRegions();

  /**
   * Add js keys to be whitelisted even when the user has been denied access
   *
   * @return
   *   An array of keys representing javascripts to be allowed even when
   *   the user is denied access by IP. These keys can be found by
   *   as the keys in hook_js_alter().
   */
  public function whitelistedJsKeys();

  /**
   * Alter the Restrict IP Access Denied page.
   *
   * @param $page
   *   The render array for the access deneid page passed by reference.
   */
  public function accessDeniedPageAlter(&$page);
}