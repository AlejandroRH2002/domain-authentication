<?php
namespace local_domainauthentication\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once(__DIR__ . '/../local/domain_validator.php');

use local_domainauthentication\local\domain_validator;

/**
 * Safe user-creation form owned by the plugin.
 */
class external_user_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'userdetails', get_string('userdetails', 'local_domainauthentication'));
        $mform->addElement('text', 'username', get_string('username'));
        $mform->setType('username', PARAM_USERNAME);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string('firstname'));
        $mform->setType('firstname', PARAM_NOTAGS);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname'));
        $mform->setType('lastname', PARAM_NOTAGS);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');

        $mform->addElement('header', 'externalrequirements',
            get_string('externalrequirements', 'local_domainauthentication'));
        $mform->addElement('textarea', 'domainauthentication_justification',
            get_string('justification', 'local_domainauthentication'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('domainauthentication_justification', PARAM_TEXT);
        $mform->addElement('date_selector', 'domainauthentication_expirationdate',
            get_string('expirationdate', 'local_domainauthentication'), ['optional' => false]);
        $mform->addHelpButton('domainauthentication_justification', 'justification',
            'local_domainauthentication');
        $mform->addHelpButton('domainauthentication_expirationdate', 'expirationdate',
            'local_domainauthentication');

        $this->add_action_buttons(true, get_string('createuser'));
    }

    public function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);
        $errors += domain_validator::validate((array)$data);

        $username = trim((string)($data['username'] ?? ''));
        $username = \core_text::strtolower($username);
        if ($username !== '' && $DB->record_exists('user', [
            'username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id,
        ])) {
            $errors['username'] = get_string('usernameexists');
        }

        $passworderror = '';
        if (!empty($data['password']) &&
                !check_password_policy($data['password'], $passworderror, (object)$data)) {
            $errors['password'] = $passworderror;
        }

        return $errors;
    }
}
