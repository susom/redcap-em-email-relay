<?php
namespace Stanford\EmailRelay;
/** @var \Stanford\EmailRelay\EmailRelay $module */

// Do not log $_REQUEST: it holds the recipients, subject, body and the email token.
$module->emDebug("Incoming relay request" . (isset($_GET['pid']) ? " for project " . intval($_GET['pid']) : " (system)"));

$result = $module->sendEmail();

header("Content-type: application/json");
echo json_encode($result);