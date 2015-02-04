<?php
/**
 * User: Dean Vaughan
 * Date: 9/5/12
 * Time: 12:36 PM
 */

// Lets assume that images received from the TEST ae title need the patient's state of residence appended to the history.
// The good folks at TEST have placed the patient's state into tag 0038,0500 for us already

if($img['history']) {
  $img['history'] .= ' ';
}
$img['history'] .= "Patient's State: " . $d->get_tag('0038', '0500');

