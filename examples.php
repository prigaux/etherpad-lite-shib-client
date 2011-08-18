<pre>
<?php
error_reporting(-1);
// Include the Class
include 'etherpad-lite-client.php';

// Create an instance
$instance = new EtherpadLiteClient('EtherpadFTW','http://beta.etherpad.org/api'); // Example URL:  http://your.hostname.tld:8080/api

// All API calls return a JSON value, you should print_r the JSON to see more info.

echo "<h1>Pads</h1>";

/* Example: Create Author */ 
try {
  $author = $instance->createAuthor('John McLear'); // This really needs explaining..
  $authorID = $author->authorID;
echo "The AuthorID is now $authorID\n\n";
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\ncreateAuthor Failed with message ". $e->getMessage();
}

/* Example: get Mapped Author */
// Bug this is not written yet

/* Example: Create a new Pad */
try {
  $instance->createPad('testPad','Hello world');
} catch (Exception $e) {
  echo "\n\ncreatePad Failed with message ". $e->getMessage();
}

/* Example: Set Text into a Pad */
try {
  $instance->setText('testPad','Hello world');
} catch (Exception $e) {
  echo "\n\nsetText Failed with message ". $e->getMessage();
}


/* Example: Delete Pad */
try {
  $instance->deletePad('testPad');
} catch (Exception $e) {
  // the pad doesn't exist?
  echo "\n\ndeletePad Failed with message ". $e->getMessage();
}

/* Example: Get Ready Only ID of a pad */
try {
  $readOnlyID = $instance->getReadOnlyID('testPad');
  echo "The read only ID of this pad is: $readOnlyID->readOnlyID\n\n";
} catch (Exception $e) {
  echo "\n\ngetReadOnlyID Failed with message ". $e->getMessage();
}


/* Example: Get Public Status of a pad and include some logic -- This only works for group pads */
try {
  $getpublicStatus = $instance->getPublicStatus('testPad');
  if ($getpublicStatus->publicStatus === false){echo "This Pad is not public\n\n";}else{echo "This Pad is public\n\n";}
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\ngetPublicStatus Failed with message ". $e->getMessage();
}

/* Example: Set Public Status of a pad -- This only works for group pads */
try {
  $instance->setPublicStatus('testPad',true); // true or false
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\nsetPublicStatus Failed with message ". $e->getMessage();
}


/* Example: Set Password on a pad -- This only works for group pads */
try {
  $instance->setPassword('testPad','aPassword');
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\nsetPassword Failed with message ". $e->getMessage();
}

/* Example: Get true/false if the pad is password protected and include some logic -- This only works for group pads*/
try {
  $isPasswordProtected = $instance->isPasswordProtected('testPad');
  if ($isPasswordProtected->isPasswordprotected === false){echo "Pad is not password protected\n\n";}else{echo "Pad is password protected\n\n";}
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\nisPasswordProtected Failed with message ". $e->getMessage();
}

/* Example: Get revisions Count of a pad */
try {
  $revisionCount = $instance->getRevisionsCount('testPad');
  $revisionCount = $revisionCount->revisions;
  echo "Pad has $revisionCount revisions\n\n";
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\ngetRevisionsCount Failed with message ". $e->getMessage();
}

/* Example: Get Pad Contents and echo to screen */
try {
  $padContents = $instance->getText('testPad');
  echo "Pad text is: <br/><ul>$padContents->text\n\n</ul>";
  echo "End of Pad Text\n\n<hr>";
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\nisgetText Failed with message ". $e->getMessage();
}

echo "<h1>Groups</h1>";

/* Example: Create Group */
try {
  $createGroup = $instance->createGroup();
  $groupID = $createGroup->groupID;
  echo "New GroupID is $groupID\n\n";
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\ncreateGroup Failed with message ". $e->getMessage();
}

/* Example: Create Group Pad */
try {
  $newPad = $instance->createGroupPad($groupID,'testpad','Example text body'); 
  $padID = $newPad->padID;
  echo "Created new pad with padID: $padID\n\n";
} catch (Exception $e) {
  // the pad already exists or something else went wrong
  echo "\n\ncreateGroupPad Failed with message ". $e->getMessage();
}

/* Example: List Pads from a group */
try {
  $padList = $instance->listPads($groupID); 
  echo "Available pads for this group:\n";
  var_dump($padList->padIDs);
  echo "\n";
} catch (Exception $e) {
  echo "\n\nlistPads Failed: ". $e->getMessage();
}

/* Example: Create Mapped Group -- This maps a humanly readable name to a groupID */
// $mapGroup = $instance->getMappedGroup($groupID);
// BUG This bit is confusing as hell and the PHP function doesn't exist in the class - Waitnig on original author to write it

/* Example: Delete a Group */
try {
  $instance->deleteGroup($groupID);
} catch (Exception $e) {
  echo "\n\ndeleteGroupFailed: ". $e->getMessage();
}


echo "<hr>";
echo "<h1>Sessions</h1>";

/* Example: Create Session */
$validUntil = mktime(0, 0, 0, date("m"), date("d")+1, date("y")); // One day in the future
$sessionID = $instance->createSession($groupID, $authorID, $validUntil);
echo "New Session ID is $sessionID->sessionID\n\n";

/* Example: Get Session info */
echo "Session info:\n";
$sessionID = $sessionID->sessionID;
$sessioninfo = $instance->getSessionInfo($sessionID);
var_dump($sessioninfo);
echo "\n";

/* Example: List Sessions os Author */
echo "Sessions the Author $authorID is part of:\n";
$authorSessions = $instance->listSessionsOfAuthor($authorID);
var_dump($authorSessions);
echo "\n";

/* Example: List Sessions of Group */
$groupSessions = $instance->listSessionsOfGroup($groupID);
var_dump($groupSessions);

/* Example: Delete Session */
$instance->deleteSession($sessionID);

?>
