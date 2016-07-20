<?php

namespace Drupal\restrict_ip;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a RestrictIpSubscriber.
 *
 * This function determines whether or not the user should be
 * whitelisted, and if they should, it sets a flag indicating so
 */
class RestrictIpSubscriber implements EventSubscriberInterface {
  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a Foo object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * // only if KernelEvents::REQUEST !!!
   * @see Symfony\Component\HttpKernel\KernelEvents for details
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function RestrictIpLoad(GetResponseEvent $event) {
    global $conf;
    $user = \Drupal::currentUser();
    $config = \Drupal::configFactory()->getEditable('ip_restrict.settings');

    // Allow Drush requests regardless of IP.
    if (PHP_SAPI !== 'cli') {
      // Get the value saved ot the system, and turn it into an array of IP addresses.
      $ip_addresses = restrict_ip_sanitize_ip_list($config->get('address_list'));
      // Add any whitelisted IPs from the settings.php file to the whitelisted array
      if (isset($conf['restrict_ip_whitelist'])) {
        $ip_addresses = array_merge($ip_addresses, restrict_ip_sanitize_ip_list(implode(PHP_EOL, $conf['restrict_ip_whitelist'])));
      }

      // We only need to check IP addresses if at least one IP has been set to be whitelisted.
      if (count($ip_addresses)) {


//        $user_ip = \Drupal::request()->getClientIp();
        $user_ip = $this->request->getClientIp();


        $access_denied = TRUE;
        foreach ($ip_addresses as $ip_address) {
          $ip_address = trim($ip_address);
          if (strlen($ip_address)) {
            // Check if the given IP address matches the current user
            if ($ip_address == $user_ip) {
              // The given IP is allowed - so we don't deny access (aka we allow it)
              $access_denied = FALSE;
              // No need to continue as user is allowed
              break;
            }

            $pieces = explode('-', $ip_address);
            // We only need to continue checking this IP address
            // if it is a range of addresses
            if (count($pieces) == 2) {
              $start_ip = $pieces[0];
              $end_ip = $pieces[1];
              $start_pieces = explode('.', $start_ip);
              // If there are not 4 sections to the IP then its an invalid
              // IPv4 address, and we don't need to continue checking
              if (count($start_pieces) === 4) {
                $user_pieces = explode('.', $user_ip);
                $continue = TRUE;
                // We compare the first three chunks of the first IP address
                // With the first three chunks of the user's IP address
                // If they are not the same, then the IP address is not within
                // the range of IPs
                for ($i = 0; $i < 3; $i++) {
                  if ((int) $user_pieces[$i] !== (int) $start_pieces[$i]) {
                    // One of the chunks has failed, so we can stop
                    // checking this range
                    $continue = FALSE;
                    break;
                  }
                }
                // The first three chunks have past testing, so now we check the
                // range given to see if the final chunk is in this range
                if ($continue) {
                  // First we get the start of the range
                  $start_final_chunk = (int) array_pop($start_pieces);
                  $end_pieces = explode('.', $end_ip);
                  // Then we get the end of the range. This will work
                  // whether the user has entered XXX.XXX.XXX.XXX - XXX.XXX.XXX.XXX
                  // or XXX.XXX.XXX.XXX-XXX
                  $end_final_chunk = (int) array_pop($end_pieces);
                  // Now we get the user's final chunk
                  $user_final_chunk = (int) array_pop($user_pieces);
                  // And finally we check to see if the user's chunk lies in that range
                  if ($user_final_chunk >= $start_final_chunk && $user_final_chunk <= $end_final_chunk) {
                    // The user's IP lies in the range, so we don't deny access (ie - we grant it)
                    $access_denied = FALSE;
                    // No need to cintinue checking addresses as the user has been granted
                    break;
                  }
                }
              }
            }
          }
        }

        // The user has been denied access, so we need to set this value as so.
        if ($access_denied) {
          ip_restricted(TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('RestrictIpLoad', 20);
    return $events;
  }
}