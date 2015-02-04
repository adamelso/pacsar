#!/usr/bin/php
<?php
/**
 * User: Dean Vaughan
 * Date: 9/4/12
 * Time: 10:34 AM
 * Processes files received by store_server.php
 * This should not be called by a web browser
 */

chdir(dirname(__FILE__));

require_once('../class_dicom_php/class_dicom.php');

// This function will log a message to a file
function logger($message) {
  $now_time = date("Ymd G:i:s");

  $message = "[IMPORT] $now_time - $message";

  $fh = fopen("./store_server.log", 'a') or die("can't open file");
  fwrite($fh, "$message\n");
  fclose($fh);

  print "$message\n";

}

// store_server.php will pass these values to us.
$dir = (isset($argv[1]) ? $argv[1] : ''); // Directory our DICOM file is
$file = (isset($argv[2]) ? $argv[2] : ''); // Filename of the DICOM file
$sent_to_ae = (isset($argv[3]) ? $argv[3] : ''); // AE Title the image was sent to
$sent_from_ae = ((isset($argv[4]) ? $argv[4] : '')); // AE Title the image was sent from

// Lets make sure we were called correctly.
if (!$file || !$dir || !$sent_to_ae || !$sent_from_ae) {
  logger("Missing args: " . print_r($argv, true));
  exit;
}

// Lets get the dicom_tag class ready to go so we can look at the data in the image
$d = new dicom_tag;

// Lets make sure the DICOM file exists before proceeding
$d->file = "$dir/$file"; //
if (!file_exists($d->file)) {
  logger($d->file . ": does not exist");
  exit;
}

// Load the tags from the images
$d->load_tags();

// We're going to parse out some information from the DICOM file and store it in an array for easy finding
$img = array();

// The name is stored in the images as last^first. Lets seperate it out.
$img['name'] = $d->get_tag('0010', '0010');
list($img['lastname'], $img['firstname']) = explode('^', $img['name']);

// Patient ID
$img['id'] = $d->get_tag('0010', '0020');

// Lets make the date DB friendly and get the year, month, day for use in the file name
$img['appt_date'] = $d->get_tag('0008', '0020');
$img['appt_date'] = date('Y-m-d', strtotime($img['appt_date']));
list($img['year'], $img['month'], $img['day']) = explode('-', $img['appt_date']);

// patient's birth date
$img['dob'] = $d->get_tag('0010', '0030');

// study uid should uniquely identify the study
$img['study_uid'] = $d->get_tag('0020', '000d');

// Hopefully a descriptive study description
$img['study_desc'] = $d->get_tag('0008', '1030');

// This should also uniquely identify the study
$img['accession'] = $d->get_tag('0008', '0050');

// Patient history
$img['history'] = $d->get_tag('0010', '21B0');

// The name of the facility taking the images
$img['institution'] = $d->get_tag('0008', '0080');

// These define the order the images should be displayed
$img['series_number'] = $d->get_tag('0020', '0011');
$img['instance_number'] = $d->get_tag('0020', '0013');

// This is unique to this image
$img['sop_instance'] = $d->get_tag('0008', '0018');

// How is the pixel data of the image encoded?
$img['transfer_syntax'] = $d->get_tag('0002', '0010');

// depending on the modality, this should be the specific body part in the image (hand, leg, arm, ect)
$img['body_part_examined'] = $d->get_tag('0018', '0015');

// The date/time the image was created. This is spread over two tags. Also, lets make it SQL friendly
$img['image_date'] = $d->get_tag('0008', '0023');
$img['image_time'] = $d->get_tag('0008', '0033');
$img['image_date'] = date('Y-m-d G:i:s', strtotime($img['image_date'] . ' ' . $img['image_time']));

// The modality of the image
$img['modality'] = $d->get_tag('0008', '0060');

// Log that we received the image
logger("Received " . $img['name'] . " from $sent_to_ae -> $sent_from_ae");

// The DICOM standard is an odd because it's not very standardized. Different PACS systems and modalities implement
// it in different ways. Take patient history for example, there is a specific tag defined to hold patient history, but
// not every modality will put it in that tag. Some will put it in patient comments, some in visit comments, in fact,
// I've seen patient history in four different tags.
// This leads to a problem for us, we want to make sure we get all of the information we need, but we don't want to mess
// up our nice and tight import.php file with some huge if/else statement (or whatever) that tries to sort this mess out.
// To make everyone happy we're going to implement a quick and dirty module system so we can write bits of code
// specific to certain AE titles.

// We'll loop through the module directory for files that match $sent_from_ae if we find any, run the code they contain.
if ($handle = opendir('./modules')) {
  while (false !== ($module = readdir($handle))) {
    if(strstr($module, '.php')) {
      include("./modules/$module");
      logger("Ran module $module");
    }
  }
  closedir($handle);
}

// For debug, lets log the entire $img array so we can see what's going on
logger(print_r($img, true));

// Lets create a directory to keep our images in.
// We're going to organize the images by the AE title they came from and the date they were taken.
$store_dir = "./received_images/$sent_from_ae/" . $img['year'] . "/" . $img['month'] . "/" . $img['day'] . "/" .
  $img['study_uid'];

if (!file_exists($store_dir)) {
  mkdir($store_dir, 0777, true);
  logger("Created $store_dir");
}

// storescp is nice enough to give the file the same name as the sop instance, so lets just use that as our file name.
// Note that we're appending .dcm to it just to make it obvious its a DICOM file.
// As an added benefit, the sop instance should be unique in a study, so if we have an image with the same sop instance,
// we can assume the new image is a duplicate.

if (file_exists("$store_dir/$file.dcm")) {
  logger("$store_dir/$file.dcm already exists, new file is probably a duplicate, ignoring.");
  unlink($d->file);
  exit;
}

// Move our received image into the storage directory.
rename($d->file, "$store_dir/$file.dcm");
chmod("$store_dir/$file.dcm", 0666);
logger("Moved image to $store_dir/$file.dcm");

// Now that we've got the image headers parsed and the image moved to where we want to keep it, lets put the information
// we collected into a database

// Connect to the DB. If you changed the DB name, username, or password, you'll need to make it match here.
$db = mysql_connect("localhost", "pacs", "pacspassword") or die("Could not connect to DB.");
if(!$db) {
  die("no db");
}
if(!mysql_select_db("pacs", $db)) {
  die("No database selected.");
}

// Here's one that's forgotten in many, many (many) commercial PACS systems; the information you get from an image
// was entered by a person and needs to be validated and sanitized before being put into a database. A smart person
// can and will turn the patient's name or history into an SQL injection attack.
// mysql_real_escape_string() is the minimum you should do to protect yourself.
foreach($img as $key => $value) {
  $img["$key"] = mysql_real_escape_string($value);
}

// We have the database for our images split into two tables, studies and images. The studies table will contain
// information that pertains to the study has a whole, while images will contain information about specific images.
// Each study can contain multiple images.

// Lets make sure this image isn't part of an existing study.
// We're going to assume that over our entire database, which could contain millions of studies, received from 100s
// of modalities, that the study uid, accession, patient id, sent_from_ae, and last name, all taken together equals
// an unique study.
// If you were to follow the DICOM standard the study uid or accession should denote a unique study, but when
// you're receiving images from many sources its very possible these can duplicate.
$study_seq = 0; // This will store the existing study if found
$sql = "SELECT seq FROM studies WHERE study_uid = '" . $img['study_uid'] . "' AND accession = '" . $img['accession'] .
       "' AND id = '" . $img['id'] . "' AND lastname = '" . $img['lastname'] . "' AND sent_from_ae = '$sent_from_ae' LIMIT 1" ;
$result = mysql_query($sql) or die(mysql_error());
$row = mysql_fetch_array($result, MYSQL_ASSOC);
if($row['seq']) {
  $study_seq = $row['seq']; // Found and existing study
}
else {
  // We did not find an existing study, we need to create a DB entry for it.

  // Lets create an SQL INSERT
  $sql = "INSERT INTO studies(" .
    "`firstname`, " .
    "`lastname`, " .
    "`id`, " .
    "`appt_date`, " .
    "`dob`, " .
    "`study_uid`, " .
    "`study_desc`, " .
    "`accession`, " .
    "`history`, " .
    "`institution`, " .
    "`sent_from_ae`, " .
    "`sent_to_ae` " .
    ") VALUES (" .
    "\"$img[firstname]\", " .
    "\"$img[lastname]\", " .
    "\"$img[id]\", " .
    "\"$img[appt_date]\", " .
    "\"$img[dob]\", " .
    "\"$img[study_uid]\", " .
    "\"$img[study_desc]\", " .
    "\"$img[accession]\", " .
    "\"$img[history]\", " .
    "\"$img[institution]\", " .
    "\"$sent_from_ae\", " .
    "\"$sent_to_ae\" " .
    ")";
  mysql_query($sql) or die(mysql_error());

  // Now that we've got the info in the DB, we need to find out what seq it was assigned.
  $sql = "SELECT seq FROM studies WHERE study_uid = '" . $img['study_uid'] . "' AND accession = '" . $img['accession'] .
    "' AND id = '" . $img['id'] . "' AND lastname = '" . $img['lastname'] . "' ORDER by seq DESC LIMIT 1" ;
  logger($sql);
  $result = mysql_query($sql) or die(mysql_error());
  $row = mysql_fetch_array($result, MYSQL_ASSOC);
  if($row['seq']) {
    $study_seq = $row['seq'];
  }
}

// The images know what study they belong to by the study_seq, if it doesn't exist at this point something went wrong.
if(!$study_seq) {
  logger("There is no study_seq to assign to the image.");
  exit;
}

// Now lets create a DB entry for the image
$sql = "INSERT INTO images(" .
  "`study_seq`, " .
  "`series_number`, " .
  "`instance_number`, " .
  "`sop_instance`, " .
  "`transfer_syntax`, " .
  "`body_part_examined`, " .
  "`image_date`, " .
  "`modality` " .
  ") VALUES (" .
  "\"$study_seq\", " .
  "\"$img[series_number]\", " .
  "\"$img[instance_number]\", " .
  "\"$img[sop_instance]\", " .
  "\"$img[transfer_syntax]\", " .
  "\"$img[body_part_examined]\", " .
  "\"$img[image_date]\", " .
  "\"$img[modality]\" " .

  ")";
mysql_query($sql) or die(mysql_error());

// As a finale, lets create a JPEG thumbnail of the image we received. This will be used in the next tutorial.
// On slow machines this can take a few seconds, so log the start time and the end time just so we know.
logger("Creating thumbnail");
$d = new dicom_convert;
$d->file = "$store_dir/$file.dcm";
$tn_file = $d->dcm_to_tn();

// At this point the thumbnail is in the same directory as the DICOM image, lets move it to its own directory.
if(!file_exists("$store_dir/tn")) {
  mkdir("$store_dir/tn");
}
rename($tn_file, "$store_dir/tn/" . basename($tn_file));

logger("Thumbnail created");

logger("Successfully imported $file.")

?>
