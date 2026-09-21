<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/external_user_form.php');
require_once(__DIR__ . '/classes/local/external_account_repository.php');
require_once($CFG->dirroot . '/user/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/domainauthentication:createuser', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/domainauthentication/index.php'));
$PAGE->set_title(get_string('createexternaluser', 'local_domainauthentication'));
$PAGE->set_heading(get_string('createexternaluser', 'local_domainauthentication'));

$form = new local_domainauthentication\form\external_user_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/user.php'));
}

if ($data = $form->get_data()) {
    global $DB, $CFG;

    $user = (object)$data;
    $user->username = \core_text::strtolower(trim($user->username));
    $user->email = trim($user->email);
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();
    $user->password = hash_internal_user_password($user->password);

    $justification = $data->domainauthentication_justification ?? '';
    $expirationdate = $data->domainauthentication_expirationdate ?? 0;
    unset($user->domainauthentication_justification, $user->domainauthentication_expirationdate);

    $transaction = $DB->start_delegated_transaction();
    try {
        $userid = user_create_user($user, false, false);
        $domain = local_domainauthentication\local\domain_validator::get_domain($user->email);
        if ($domain !== null && !local_domainauthentication\local\domain_validator::is_institutional_domain($domain)) {
            local_domainauthentication\local\external_account_repository::save(
                $userid, $justification, $expirationdate);
        }
        $transaction->allow_commit();
    } catch (\Throwable $exception) {
        $transaction->rollback($exception);
        throw new moodle_exception('errorcreatinguser', 'local_domainauthentication');
    }

    redirect(new moodle_url('/user/profile.php', ['id' => $userid]),
        get_string('usersaved', 'local_domainauthentication'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// The form is the only creation path exposed by this page; all data is still
// validated again on the server by moodleform::get_data().
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createexternaluser', 'local_domainauthentication'));
echo $OUTPUT->notification(get_string('externalflownotice', 'local_domainauthentication'), 'warning');
$form->display();
echo $OUTPUT->footer();
