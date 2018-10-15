<?php
namespace Stanford\EmailRelay;
/** @var \Stanford\EmailRelay\EmailRelay $module */


require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>

<h3>Email Relay API Instructions</h3>
    <p>
    This module allows you to create either system level or project-specific API urls that can be used for relaying email messages from an
        outside system (such as a GCP/Amazon project).  It does not use the normal REDCap API tokens. 
    </p>
    <p>
    If emails are related to a specific project, enable this external module on that project.
    </p>
    <p>
    If emails are not related to a project, then using the system level configuration to generate a URL/Token.
    </p>
    <p>
        Each email sent is logged to REDCap logs (and optionally to the specified record/project)
    </p>
<br>

<h4>Endpoint</h4>
<p>
    You must send a 'POST' request to the following url to initiate an email:
</p>
<pre>
<?php echo isset($project_id) ? $module->getProjectUrl($project_id) : $module->getSystemUrl() ?>&NOAUTH
</pre>
<br>
<h4>Example</h4>
<p>
The following parameters are valid in the body of the POST
</p>
<pre>
    email_token: <?php
    if(isset($project_id)){
        echo "<b>". $module->email_token . "</b>  (this token is unique to this project)";
    }else{
        echo "[XXXXXXXXXX]";
    }

    ?>
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



<?php
    if(isset($project_id)){
        echo "<h4>IP Filters</h4>";
        $ip_filters = $module->getProjectSetting('ip');
        if (empty($ip_filters)) {
            echo "<div class='alert alert-danger'>No IP Filters are defined.  This is strongly suggested for improved security.</div>";
        } else {
            echo "<pre>" . implode("\n", $ip_filters) . "</pre>";
        }
    }
?>

<br>
<p>
    At this point attachments are not supported.
</p>