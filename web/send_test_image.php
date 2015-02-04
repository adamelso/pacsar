#!/usr/bin/php
<?php
/**
 * User: Dean Vaughan
 * Date: 9/5/12
 * Time: 8:49 AM
 * DICOM send an image to store_server.php
 * This should not be called by a web browser
 */

chdir(dirname(__FILE__));
require_once('../class_dicom_php/class_dicom.php');

$d = new dicom_net;
$d->file = "../class_dicom_php/examples/dean.dcm";

print "Sending file...\n";

$out = $d->send_dcm('localhost', '1104', 'DEANO', 'TEST');

if ($out) {
  print "$out\n";
  exit;
}

print "Sent!\n";


?>