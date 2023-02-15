<?php
require_once('email_functions.php');
require_once("db_functions.php");
require_once('external/swiftmailer-5.4.8/lib/swift_required.php');

class ClientException extends Exception {
    public $public_error_code;

    public function __construct($public_error_code, $message, $code = 0, Throwable $previous = null) {
        $this->public_error_code = $public_error_code;
        parent::__construct($message, $code, $previous);
    }
}

class AuthorizationException extends Exception { }

function verify_signature() {
    $headers = apache_request_headers();
    if (!isset($headers["Authorization"])) {
        throw new AuthorizationException("Missing required Authorization header");
    }
    $auth_header = $headers["Authorization"];
    $auth_header_parts = explode(" ", $auth_header, 2);
    $client_name = $auth_header_parts[0];
    
    if (!isset(WEBHOOK_KEYS[$client_name])) {
        throw new AuthorizationException("Unauthorized");
    }

    $webhook_data = strtoupper($_SERVER["REQUEST_METHOD"]) . "\n" . $_SERVER["REQUEST_URI"] . "\n";
    $body_content = "";
    $body = fopen("php://input" , "r");
    while (!feof($body)) {
        $body_content .= fread($body, 4096);
    }
    fclose($body);
    $webhook_data .= base64_encode($body_content);

    foreach (WEBHOOK_KEYS[$client_name] as $secret) {
        $webhook_sig = hash_hmac("sha256", $webhook_data, $secret);
        if ($webhook_sig == $auth_header_parts[1]) {
            return;
        }
    }
    
    throw new AuthorizationException("Unauthorized");
}

function send_error($http_status, $code, $error, $instance=null) {
    http_response_code($http_status);
    header("Content-Type: application/json; charset=utf-8");
    $resp = array(
        "code" => $code,
        "error" => $error
    );
    if (!is_null($instance)) {
        $resp["instance"] = $instance;
    }
    echo json_encode($resp);
}

function get_required_param($param_name) {
    if (!isset($_GET[$param_name])) {
        throw new ClientException("ERR_MISSING_REQ_PARAM", "Missing required query parameter '$param_name'");
    }
    return $_GET[$param_name];
}

function get_badge_id_for_email($email) {
    $query = <<<EOD
SELECT
    P.badgeid
FROM
         Participants P
    JOIN CongoDump CD ON P.badgeid = CD.badgeid
WHERE
    CD.email = ?
EOD;
    $result = mysqli_query_with_prepare_and_error_handling($query, "s", array($email), false);
    if (!$result) {
        throw new Exception("Error querying database");
    }

    $rows = mysqli_num_rows($result);
    mysqli_data_seek($result, 0);
    $bidarray = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $bidarray[] = $row["badgeid"];
    }
    mysqli_free_result($result);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array(
        "badgeids" => $bidarray
    ));
}

function insert_participant_into_db($data) {
    $query = "SELECT 1 FROM Participants WHERE badgeid = ?";
    $result = mysqli_query_with_prepare_and_error_handling($query, "s", array($data->badgeid));
    $rows = mysqli_num_rows($result);
    if (is_null($rows)) {
        throw new Exception("Error querying for existing badgeid");
    }
    if ($rows > 0) {
        throw new ClientException("ERR_BADGEID_EXISTS", "badgeid '$data->badgeid' already exists");
    }

    $permrole_placeholders = join(",", array_fill(0, count($data->roles), "?"));
    $types = str_repeat("s", count($data->roles));
    $query = "SELECT COUNT(*) count FROM PermissionRoles WHERE permrolename IN ($permrole_placeholders)";
    $result = mysqli_query_with_prepare_and_error_handling($query, $types, $data->roles);
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        throw new Exception("Error querying for role names");
    }
    if ($row["count"] != count($data->roles)) {
        throw new ClientException("ERR_ROLE_NOT_EXIST", "One or more roles does not exist");
    }

    $query = <<<EOD
    INSERT INTO Participants (badgeid, pubsname)
        VALUES (?, ?);
    EOD;
    $rows = mysql_cmd_with_prepare($query, "ss", array($data->badgeid, $data->badgename));
    if (is_null($rows) || $rows !== 1) {
        throw new Exception("Failed to insert into Participants table. Insert update $rows rows.");
    }

    $query = <<<EOD
    INSERT INTO CongoDump
        (badgeid, email, regtype, firstname, lastname, badgename, phone, postaddress1, postaddress2, postcity, poststate, postzip, postcountry)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
    EOD;
    $ins_array = array($data->badgeid, $data->email, $data->regtype, $data->firstname, $data->lastname, $data->badgename, $data->phone, $data->postaddress1,
        $data->postaddress2, $data->postcity, $data->poststate, $data->postzip, $data->postcountry);
    $rows = mysql_cmd_with_prepare($query, "sssssssssssss", $ins_array);
    if (!$rows || $rows !== 1) {
        throw new Exception("Failed to insert into CongoDump table. Insert updated $rows rows.");
    }

    $types = str_repeat("s", count($data->roles) + 1);
    $ins_array = [$data->badgeid, ...$data->roles];
    $query = <<<EOD
    INSERT INTO UserHasPermissionRole
        (badgeid, permroleid)
        SELECT ?, permroleid FROM PermissionRoles WHERE permrolename IN ($permrole_placeholders);
    EOD;
    $rows = mysql_cmd_with_prepare($query, $types, $ins_array);
    var_dump($ins_array);
    if (is_null($rows) || $rows !== count($data->roles)) {
        throw new Exception("Failed to insert into CongoDump table. Insert updated $rows rows.");
    }
}

function insert_reset_item($badgeid, $email, $ipaddress, $selector, $token) {
    $expires = new DateTime('NOW');
    $expires->add(new DateInterval(NEW_ACCOUNT_LINK_TIMEOUT));
    $expiration = date_format($expires,'Y-m-d H:i:s');
    $token = hash('sha256', $token);

    $query = <<<EOD
    INSERT INTO ParticipantPasswordResetRequests
        (badgeidentered, email, ipaddress, expirationdatetime, selector, token)
        VALUES (?, ?, ?, ?, ?, ?);
    EOD;
    $insert_data = array($badgeid, $email, $ipaddress, $expiration, $selector, $token);
    $rows = mysql_cmd_with_prepare($query, "ssssss", $insert_data);
    if (is_null($rows) || $rows !== 1) {
        throw new Exception("Failed to insert into ParticipantPasswordResetRequests table. Insert updated $rows rows.");
    }
}

function send_welcome_email($firstname, $lastname, $badgename, $email, $url) {
    $conName = CON_NAME;

    //Define body
    $urlLink = sprintf('<a href="%s">%s</a>', $url, $url);
    if (!empty($badgename)) {
        $username = $badgename;
    } else {
        $comboname = "$firstname $lastname";
        if (!empty($comboname)) {
            $username = $comboname;
        } else {
            $username = "unknown";
        }
    }
    $link_lifetime = NEW_ACCOUNT_LINK_TIMEOUT_DISPLAY;
    $con_support_email = CON_SUPPORT_EMAIL;
    $emailBody = <<<EOD
    <html><body>
    <p>
        Hello $username,
    </p>
    <p>
        Thank you for volunteering to participate in the programming for $conName.
    </p>
    <p>
        We are using PlanZ to help us organize our programming. You will need to set up an account by following this link:
    </p>
    <p>
        $urlLink
    </p>
    <p>
        The link is good for $link_lifetime. If it has expired, please contact $con_support_email
    </p>
    <p>
        Thanks!
    </p></body></html>
EOD;

    $text_body = <<<EOD
    Hello $username,

    Thank you for volunteering to participate in the
    programming for $conName.

    We are using PlanZ to help us organize our
    programming. You will need to set up an account by
    following this link:

    $url

    The link is good for $link_lifetime. If it has
    expired, please contact $con_support_email

    Thanks!
EOD;

    send_email_with_plain_text($text_body, $emailBody, "Participating in $conName programming", [ $email => $username ]);
}

function add_participant($data, $ipaddress) {
    if (!$data->badgeid) {
        throw new ClientException("ERR_MALFORMED_BODY", "Missing required member 'badgeid' in body");
    }

    if (!$data->email) {
        throw new ClientException("ERR_MALFORMED_BODY", "Missing required member 'email' in body");
    }

    if (!$data->badgename && !$data->firstname && !$data->lastname) {
        throw new ClientException("ERR_MALFORMED_BODY", "Missing required member 'badgename', 'firstname' or 'lastname' in body");
    }

    if (!$data->roles) {
        throw new ClientException("ERR_MALFORMED_BODY", "Missing required member 'roles' in body");
    }

    foreach (
        array("badgeid", "email", "regtype", "firstname", "lastname", "badgename", "phone", "postaddress1", "postaddress2", "postcity", "poststate", "postzip", "postcountry")
        as $member
    ) {
        if ($data->{$member} != null && !is_string($data->{$member})) {
            throw new ClientException("ERR_MALFORMED_BODY", "Member '$member' must be a string");
        }
    }

    if (!is_array($data->roles) || array_sum(array_map('is_string', $data->roles)) != count($data->roles)) {
        throw new ClientException("ERR_MALFORMED_BODY", "Member 'roles' must be an array of strings");
    }

    insert_participant_into_db($data);

    $selector = bin2hex(random_bytes(8));
    $token = random_bytes(32);

    insert_reset_item($data->badgeid, $data->email, $ipaddress, $selector, $token);

    $url = sprintf('%sCreateAccount.php?%s', ROOT_URL, http_build_query([
        'selector' => $selector,
        'validator' => bin2hex($token)
    ]));
    send_welcome_email($data->firstname, $data->lastname, $data->badgename, $data->email, $url);
    
    header("Content-Type: application/json; charset=utf-8");
}

try {
    verify_signature();

    if (!prepare_db_and_more()) {
        render_server_error();
        return;
    };
    
    $action = get_required_param("action");
    switch ($action) {
        case "GetBadgeIdsForEmail":
            $email = get_required_param("email");
            get_badge_id_for_email($email);
            break;
        case "AddParticipant":
            $ipaddress = $_SERVER['REMOTE_ADDR'];
            if ($_SERVER["CONTENT_TYPE"] != "application/json") {
                throw new ClientException("ERR_MALFORMED_BODY", "Content-Type must be 'application/json'");
            }
            $json = file_get_contents('php://input');
            $data = json_decode($json);
            if (!$data) {
                throw new ClientException("ERR_MALFORMED_BODY", "Error parsing content as json");
            }
            add_participant($data, $ipaddress);
            break;
        default:
            throw new ClientException("ERR_UNKNOWN_ACTION", "Unknown value for action query parameter '$action'");
            break;
    }
} catch (ClientException $e) {
    send_error(400, $e->public_error_code, $e->getMessage());
} catch (AuthorizationException $e) {
    // Log auth errors in case we need to spot people brute forcing
    $instance = bin2hex(random_bytes(8));
    error_log("Client authorization error on webhook: [$instance]" . $e->getMessage());
    send_error(401, "ERR_AUTH", $e->getMessage(), $instance);
} catch (Exception $e) {
    $instance = bin2hex(random_bytes(8));
    error_log("Internal error in webhook: [$instance]" . $e->getMessage());
    send_error(500, "ERR_INTERNAL", "An internal error occurred", $instance);
}
?>