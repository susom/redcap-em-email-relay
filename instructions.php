<?php
namespace Stanford\EmailRelay;
/** @var \Stanford\EmailRelay\EmailRelay $module */


require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>

<h3>Email Relay API Instructions</h3>
    <p>
    This module allows you to create a project-specific API url that can be used for relaying email messages from an
        outside system (such as a GCP/Amazon project.  It does not use the normal REDCap API tokens but a project
        specific email token that was generated when the module was activated on this project.
    </p>
    <p>
        Each email sent is logged to the REDCap project logs (and optionally to the specified record)
    </p>
<br>

<h4>Endpoint</h4>
<p>
    You must send a 'POST' request to the following url to initiate an email:
</p>
<pre>
<?php echo $module->getProjectUrl($project_id) ?>&NOAUTH
</pre>
<br>
<h4>Example</h4>
<p>
The following parameters are valid in the body of the POST
</p>
<pre>
    email_token: <?php echo $module->email_token; ?> (this token is unique to this project)
    to:          A comma-separated list of valid email addresses (no names)
    from_name:   Jane Doe
    from_email:  Jane@doe.com
    cc:          (optional) comma-separated list of valid emails
    bcc:         (optional) comma-separated list of valid emails
    subject:     A Subject
    body:        A Message Body (<?php echo htmlentities("<b>html</b>") ?> is okay!)
    record_id:   (optional) a record_id in the project - email will be logged to this record
</pre>
<br>


<h4>IP Filters</h4>
<?php
    $ip_filters = $module->getProjectSetting('ip');
    if (empty($ip_filters)) {
        echo "<div class='alert alert-danger'>No IP Filters are defined.  This is strongly suggested for improved security.</div>";
    } else {
        echo "<pre>" . implode("\n", $ip_filters) . "</pre>";
    }
?>

<br>
<p>
    At this point attachments are not supported.
</p>