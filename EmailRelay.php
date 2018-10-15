<?php
namespace Stanford\EmailRelay;

require_once "emLoggerTrait.php";

use ExternalModules\ExternalModules;
use REDCap;
use Message;

/**
 * Class EmailRelay
 *
 * @package Stanford\EmailRelay
 */
class EmailRelay extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;

    const TOKEN_KEY = "email_token";
    public $email_token;
    private $url;

    public function __construct()
    {
        parent::__construct();

        // If in project context, load the object
        global $project_id;
        if ($project_id) {
            $this->emDebug("Loading " . self::TOKEN_KEY . " for project $project_id");
            $this->email_token = $this->getProjectSetting(self::TOKEN_KEY, $project_id);
        }

        $this->emDebug($this->PREFIX . " constructed $project_id");
    }

    public function redcap_module_project_enable($version, $project_id) {
        if (empty($this->email_token)) {
            // Generate a random token for this project
            $this->email_token = generateRandomHash(10);
            $this->setProjectSetting(self::TOKEN_KEY, $this->email_token, $project_id);
        }
        // self::log($this->PREFIX . " " . $version . " was enabled for project $project_id");
    }

    public function redcap_module_project_disable($version, $project_id) {
        // self::log($this->PREFIX . " " . $version . " was disabled for project $project_id");
    }


    public function getProjectUrl($project_id) {
        $url = $this->getUrl("service",true, true);
        $url .= "&pid=" . $project_id. "&NOAUTH";
        return $url;
    }

    public function getSystenmUrl($project_id) {
        $url = $this->getUrl("service",true, true);
        $url .= "&NOAUTH";
        return $url;
    }

    /**
     * Takes a string of emails and returns a validated string of emails
     * @param $list of emails
     * @return array with true|false and result
     */
    public function parseEmailList($list) {
        // Handle comma-separated lists
        $emails = array_filter(array_map('trim', explode(",",$list)));
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return array( false, "Invalid Email: $email");
            }
        }
        return array( true, implode(",", $emails) );
    }

    public function sendEmail() {
        global $project_id;

        if(isset($_GET["pid"])){       
            return $this->sendProjectEmail();
        }

        return $this->sendSystemEmail();
    }

    public function sendSystemEmail() {
        // Verify Email Token
        $email_token = empty($_POST['email_token']) ? null : $_POST['email_token'];

        $external_tokens = $this->getSubSettings("external_tokens");
        $this->emDebug("External Tokens: ", $external_tokens);
        $token_match = false;
        foreach($external_tokens["email_token"] as $token_i => $token){
            if($email_token == $token){
                $token_match = $token_i;
                break;
            }
        }

        if($token_match === false) {
            return array(
                "error"=>"Invalid Email Token : $email_token";
            );
        }

        // Verify IP Filter
        $ip_filter = array_filter(array($external_tokens["ip"][$token_match]));

        $this->emDebug("ip_filter:",$ip_filter);

        if (!empty($ip_filter)) {
            $isValid = false;
            foreach ($ip_filter as $filter) {
                if (self::ipCIDRCheck($filter)) {
                    $isValid = true;
                    break;
                }
            }
            if (!$isValid) return (array("error"=> "Invalid source IP!"));
        }

        $result = $this->prepareEmail();

        return array("result" => $result);
    }

    public function sendProjectEmail() {
        global $project_id;

        // Verify Email Token
        $email_token = empty($_POST['email_token']) ? null : $_POST['email_token'];

        $this->emDebug("t". $email_token, "o". $this->email_token);
        if(empty($email_token) || $email_token != $this->email_token) {
            return array(
                "error"=>"Invalid Email Token $email_token | " . $this->email_token
            );
        }

        // Verify IP Filter
        $ip_filter = array_filter($this->getProjectSetting('ip', $project_id));

        $this->emDebug("ip_filter:",$ip_filter);

        if (!empty($ip_filter)) {
            $isValid = false;
            foreach ($ip_filter as $filter) {
                if (self::ipCIDRCheck($filter)) {
                    $isValid = true;
                    break;
                }
            }
            if (!$isValid) return (array("error"=> "Invalid source IP!"));
        }

        $result = $this->prepareEmail();

        return array("result" => $result);
    }

    public function prepareEmail(){
        global $project_id;
        $to         = empty($_POST['to']) ? null : $_POST['to'];
        $from_name  = empty($_POST['from_name']) ? null : $_POST['from_name'];
        $from_email = empty($_POST['from_email']) ? null : $_POST['from_email'];
        $cc         = empty($_POST['cc']) ? null : $_POST['cc'];
        $bcc        = empty($_POST['bcc']) ? null : $_POST['bcc'];
        $subject    = empty($_POST['subject']) ? null : $_POST['subject'];
        $body       = empty($_POST['body']) ? null : $_POST['body'];
        $record_id  = empty($_POST['record_id']) ? null : $_POST['record_id'];
        $sql        = empty($project_id) ? null : "Email Token : " . $_POST['email_token'];

        $msg = new Message();

        // Parse To:
        list($success, $to) = $this->parseEmailList($to);
        if (!$success) return array( "error" => $to);
        if (empty($to)) return array("error" => "To address is required");
        $msg->setTo($to);

        // Parse CC:
        list($success, $cc) = $this->parseEmailList($cc);
        if (!$success) return array( "error" => $cc);
        $msg->setCc($cc);

        // Parse BCC:
        list($success, $bcc) = $this->parseEmailList($bcc);
        if (!$success) return array( "error" => $bcc);
        $msg->setBcc($bcc);

        // From Email:
        list($success, $from_email) = $this->parseEmailList($from_email);
        if (!$success) return array( "error" => $from_email);
        if (empty($from_email)) return array("error" => "from_email address is required");
        $msg->setFrom($from_email);

        // From Name:
        if (!empty($from_name)) $msg->setFromName($from_name);

        if (empty($subject)) return array("error" => "subject is required");
        $msg->setSubject($subject);

        $msg->setBody($body);

        // Attachments
        // TODO: Dev if necessary...

        $result = $msg->send();

        if ($result) {
            REDCap::logEvent("Email Relay API Delivery", "To: $to\nFrom: $from_email\nSubject: $subject",$sql,$record_id,null);
        }
        return $result;
    }

    // Checks if the IP is valid given an IP or CIDR range
    // e.g. 192.168.123.1 = 192.168.123.1/30
    public static function ipCIDRCheck ($CIDR) {
        $ip = trim($_SERVER['REMOTE_ADDR']);

        // Convert IPV6 localhost into IPV4
        if ($ip == "::1") $ip = "127.0.0.1";

        if(strpos($CIDR, "/") === false) $CIDR .= "/32";
        list ($net, $mask) = explode("/", $CIDR);
        $ip_net  = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long($ip);
        $ip_ip_net = $ip_ip & $ip_mask;
        return ($ip_ip_net == $ip_net);
    }
}