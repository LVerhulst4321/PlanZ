<?php
// Copyright (c) 2026 PlanZ contributors. All rights reserved. See copyright document for more details.
//
// Lets a user who signed in through OIDC and owns several members choose which member to act as:
// straight after sign-in, and later through "Switch Participant". The choices are the members
// stored in the session at sign-in from verified claims; the form only sends back a member ID.
global $headerErrorMessage, $title, $linki;
$title = 'Select Participant';
require_once('CommonCode.php');
require_once('ParticipantHeader.php');
require_once('ParticipantFooter.php');
require_once('oidc_functions.php');
require_once('./api/admin/module_model.php');

$members = $_SESSION['oidc_members'] ?? [];
if (!is_oidc_login_enabled() || !is_array($members) || count($members) === 0) {
    header('Location: ./login.php');
    exit();
}

$config = oidc_config();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!oidc_check_csrf($_POST['csrf'] ?? null)) {
        $error = 'Your session has expired. Please choose again.';
    } else {
        $member = oidc_find_session_member((int)($_POST['mid'] ?? 0));
        if ($member === null) {
            $error = 'Please choose one of the participants listed.';
        } else {
            try {
                oidc_activate_member($member, $config);
                $target = $_SESSION['oidc_target'] ?? null;
                unset($_SESSION['oidc_target']);
                header('Location: ' . oidc_after_signin_location($target));
                exit();
            } catch (OidcException $e) {
                error_log($e->getMessage());
                $error = $e->userMessage;
            }
        }
    }
}

$activeMid = $_SESSION['oidc_active_mid'] ?? null;
$preselect = $activeMid;
if ($preselect === null) {
    foreach ($members as $member) {
        if ($member['is_self']) {
            $preselect = $member['mid'];
        }
    }
}
if ($preselect === null) {
    $preselect = $members[0]['mid'];
}
$switching = isset($_SESSION['badgeid']);

participant_header($title, !$switching, 'Normal', true);
$h = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<div class="container mt-3">
    <div class="card">
        <div class="card-header">
            <h2>Which participant are you using PlanZ as?</h2>
        </div>
        <div class="card-body">
<?php if ($error !== '') { ?>
            <div class="alert alert-danger"><?php echo $h($error); ?></div>
<?php } ?>
            <p>Your <?php echo $h($config['provider_name']); ?> account has more than one membership for
                <?php echo $h(CON_NAME); ?>. Choose the person whose programme details you want to see or
                change. You can switch later with <strong>Switch Participant</strong>.</p>
            <form method="post" action="SelectParticipant.php">
                <input type="hidden" name="csrf" value="<?php echo $h(oidc_csrf_token()); ?>" />
                <fieldset class="mb-3">
                    <legend class="sr-only">Participant</legend>
<?php foreach ($members as $member) {
        $id = 'mid-' . (int)$member['mid']; ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="mid" id="<?php echo $id; ?>"
                            value="<?php echo (int)$member['mid']; ?>"<?php echo $member['mid'] === $preselect ? ' checked' : ''; ?> />
                        <label class="form-check-label" for="<?php echo $id; ?>">
                            <?php echo $h(oidc_member_label($member)); ?>
<?php if ($member['is_self']) { ?>
                            <span class="badge badge-secondary ml-1">You</span>
<?php } ?>
<?php if ($member['mid'] === $activeMid) { ?>
                            <span class="badge badge-info ml-1">Current</span>
<?php } ?>
                        </label>
                    </div>
<?php } ?>
                </fieldset>
                <button type="submit" class="btn btn-primary">Continue</button>
<?php if ($switching) { ?>
                <a class="btn btn-secondary ml-2" href="<?php echo $h(oidc_landing_page()); ?>">Cancel</a>
<?php } ?>
            </form>
        </div>
    </div>
</div>
<?php
participant_footer();
