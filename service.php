<?php
namespace Stanford\EmailRelay;
/** @var \Stanford\EmailRelay\EmailRelay $module */

echo $module->emLog("Incoming Request", $_REQUEST);

$result = $module->sendEmail();

header("Content-type: application/json");
echo json_encode($result);