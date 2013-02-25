<?php

require_once 'config.inc.php'; // for $APIKEY and $APIENDPOINT
require_once 'etherpad-lite-client.php';

$userId = $_SERVER['HTTP_REMOTE_USER'];
$displayName = @$_SERVER['HTTP_DISPLAYNAME'];
if (!$displayName) $displayName = @$_SERVER['HTTP_CN'];

if (!$userId) {
  exit("unknown REMOTE_USER, SP shib badly configured");
}
//echo "userId=$userId displayname=$displayName\n<p>";

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

$ether = new EtherpadLiteClient($APIKEY, $APIENDPOINT);

try {
  $etherAuthorID = $ether->createAuthorIfNotExistsFor($userId, $displayName)->authorID;
} catch (Exception $e) { myerror($e, "createAuthorIfNotExistsFor Failed"); }

// Get the Params from the URL
$action = @$_GET['action'];
$currentPadId = @$_GET["name"];
if ($action) {
  handleAction($ether, $etherAuthorID, $action);
} else if ($currentPadId) {
  list($etherGroupID, $_padname) = explode("$",$currentPadId);
  createSessionID($ether, $etherGroupID, $etherAuthorID);
}

$pads = getPads($ether, $etherAuthorID);

html_start();
if (@$BANDEAU) echo $BANDEAU;

echo "<div class='main'>";

echo "<div class='actions'>";
if ($currentPadId) {
  if (!@$pads[$currentPadId]) {
    // this must be a just created pad that is not modified so not listed yet
    // fake one
    $pads[$currentPadId] = onePad_raw($etherAuthorID, $currentPadId, true);
    //unset($pads[$currentPadId]["actions"]);
  }
  currentPadUi($pads[$currentPadId]);
}
if ($pads) changePadForm($pads, $currentPadId);
newPadForm();
echo "</div>"; // actions

if ($currentPadId) {
  iframe($currentPadId);
}
echo "</div>"; // main
echo "</body>";

function newPadForm() {
  echo "<form action='/' style='display:inline;'>";
  //echo "Nom du « pad »";
  echo "<input type='text' name='name'>";
  echo "<input type='submit' value='Créer un pad'>";
  echo "<input type='hidden' name='action' value='newPad'>";
  echo "</form>";
}

function changePadForm($pads, $currentPadId) {
  echo "Pads";
  echo "<form id='wantedPad' style='display: inline'>";
  echo "<select onchange='setFormAction(true)'>";
  foreach ($pads as $_id => $pad) {
    if ($pad['is_creator']) echo padSelectEntry($pad, $currentPadId) . "<br>\n";
  }
  foreach ($pads as $_id => $pad) {
    if (!$pad['is_creator']) echo padSelectEntry($pad, $currentPadId) . "<br>\n";
  }
  echo "</select>";
  if (!$currentPadId) echo "<input type='submit' value='Choisir'></input>";
  echo "</form>";

  echo "<script>";
  echo "function setFormAction(then_submit) { \n";
  echo "  var form = $('#wantedPad');";
  echo "  form.attr('action', $('#wantedPad select').val()); \n";
  echo "  if (then_submit) form.submit()";
  echo "} setFormAction(false);";
  echo "</script>";
}

function iframe($name) {
  echo "<iframe class='p_proxy' src='/p/$name'></iframe>";
  echo "<script> $(function () { var iframe = $('iframe.p_proxy'); var resize = function () { iframe.height($(window).height() - iframe.position().top) }; resize(); $(window).resize(resize); }); </script>";
}
function currentPadUi($pad) {
  echo "<div class='currentPad'>";
  echo "<ul>";
  foreach ($pad['actions'] as $action) {   
    $actionToText = array('makePublic' => "Rendre public",
			 'makePrivate' => "Rendre privé",
			 'deletePad' => "Supprimer");
    $text = $actionToText[$action];
    $id = $pad['id'];
    echo "<li><a href=/?name=$id&action=$action>$text</a></li>";
  }
  echo "</ul>";
  echo "</div>";
}

function padSelectEntry($pad, $currentPadId) {
  $selected = $currentPadId && $currentPadId === $pad['id'] ? 'selected' : '';
  return "<option $selected value='" . $pad['href'] . "'>" . $pad['nice_name'] . ($pad['is_creator'] ? '' : " (contributeur)") .  "</option>";
}

function getPads($ether, $etherAuthorID) {
  try {
    $padList = $ether->listPadsOfAuthor($etherAuthorID)->padIDs;
  } catch (Exception $e) {
    echo "\n\nlistPads Failed: ". $e->getMessage();
  }
  $pads = array();
  foreach ($padList as $_key => $padId) {
    $pads[$padId] = onePad($ether, $etherAuthorID, $padId);
  }
  return $pads;
}

function onePad($ether, $etherAuthorID, $padId) {
  $isPrivate = $ether->getPublicStatus($padId)->publicStatus === false;
  return onePad_raw($etherAuthorID, $padId, $isPrivate);
}

function onePad_raw($etherAuthorID, $padId, $isPrivate) {
  list($nice_name, $is_creator) = nice_padname($padId, $etherAuthorID);
  $p_prefix = '/ip';
  $actions = $is_creator ? array("deletePad", ($isPrivate ? 'makePublic' : 'makePrivate')) : array();

  return array('id' => $padId,
	       'nice_name' => $nice_name,
	       'is_creator' => $is_creator,
	       'href' => "$p_prefix/$padId",
	       'actions' => $actions);
}

function nice_padname($padId, $etherAuthorID) {
  list($_group, $padname) = explode("$",$padId);
  $s = removePrefixOrNULL($padname, "$etherAuthorID.");
  $is_creator = $s !== null;
  if ($s === null) {
    $s = preg_replace('/^a\.[^.]+\./', '', $padname);
  }
  $s = preg_replace('/_/', ' ', $s);
  return array($s, $is_creator);
}

function handleAction($ether, $etherAuthorID, $action) {
  $name = @$_GET["name"];

  if ($action == "newPad") // If the request is to create a new pad
  {

    if (!$name) $name = genRandomString();
    $name = preg_replace('/[\$\/]/', '_', $name);
    $name = "$etherAuthorID.$name";

    $contents = @$_GET["contents"];   
    $etherGroupID = createEtherGroup($ether, 'everybody');
    try {
      $padID = $ether->createGroupPad($etherGroupID,$name,$contents)->padID;
      $newlocation = "/ip/$padID"; // redirect to the new padID location
    } catch (Exception $e) {
      myerror($e, "createGroupPad Failed");
    }
  }
  else if ($action == "deletePad") // If teh request is to delete an existing pad
  {
    try {
      $ether->deletePad($name);
      $newlocation = '/';
    } catch (Exception $e) {
      myerror($e, "deletePad Failed");
    }
  }
  else if ($action == "makePublic" || $action == "makePrivate") // If teh request is to delete an existing pad
  {
    try {
      $ether->setPublicStatus($name, ($action == "makePublic" ? "true" : "false"));
      $newlocation = "/ip/$name";
    } catch (Exception $e) {
      myerror($e, "Changing PublicStatus Failed");
    }
  } else {
    exit("invalid action $action");
  }
  header("Location: $newlocation");
  exit;
}

function html_start() {
  echo <<<EOF
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<style type="text/css">
.actions{background-color:#fff;padding:14px 10px 10px 10px;}
.currentPad ul { margin: 0; padding: 0; font-size:81.3%}
.currentPad li { float: right; list-style: none; padding-left: 1em; }
a, a:visited { color: #000080; }

form { padding: 0 3em 0 0.5em; }
body { margin: 0; } 
.main { margin-left: 0; }
iframe.p_proxy { width: 100%; border: none; }
</style>
</head>
<body>
EOF;
}

function myerror($exception, $msg) {
  exit("$msg with message: ``" . $exception->getMessage() . "''");
}

function createEtherGroup($ether, $name) {
    try {
      return $ether->createGroup()->groupID;
    } catch (Exception $e) { 
      myerror($e, "createGroupIfNotExistsFor Failed");
    }
    return;
}

function createSessionID($ether, $etherGroupID, $etherAuthorID) {
  $validUntil = mktime(0, 0, 0, date("m"), date("d")+1, date("y")); // One day in the future
  $etherSessionID = $ether->createSession($etherGroupID, $etherAuthorID, $validUntil)->sessionID;
  setcookie("sessionID",$etherSessionID, 0, "/"); // Set a cookie 
  // echo "New Session ID is $etherSessionID->sessionID\n\n";
}

// A funtion to generate a random name if something doesn't already exist
function genRandomString() {
  $length = 10;
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $string = '';
  for ($p = 0; $p < $length; $p++) {
    $string .= $characters[mt_rand(0, strlen($characters))];
  }
  return $string;
}
function startsWith($hay, $needle) {
  return substr($hay, 0, strlen($needle)) === $needle;
}
function removePrefix($s, $prefix) {
    return startsWith($s, $prefix) ? substr($s, strlen($prefix)) : $s;
}
function removePrefixOrNULL($s, $prefix) {
    return startsWith($s, $prefix) ? substr($s, strlen($prefix)) : NULL;
}

?>
