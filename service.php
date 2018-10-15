<?php
namespace Stanford\EmailRelay;
/** @var \Stanford\EmailRelay\EmailRelay $module */

echo $module->emLog($_REQUEST, "Incoming Request");
$result = $module->sendEmail();
header("Content-type: application/json");
echo json_encode($result);