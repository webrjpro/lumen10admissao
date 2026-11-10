<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_lumen10admissao
 * @copyright 2026 Carlos Antonio de Oliveira Piquet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lumen10admissao;
defined('MOODLE_INTERNAL') || die();

/** Durable outbox. Tokens are generated at delivery and only their hashes are stored here. */
final class mail {
    public static function queue(int $requestid, string $kind): int {
        global $DB;
        if (!in_array($kind, ['confirm', 'access', 'correction', 'rejected', 'approved', 'enrolled'])) {
            throw new \coding_exception('Unknown admission notification');
        }
        $queued = $DB->get_record('lumen10admissao_mail', ['requestid' => $requestid, 'kind' => $kind, 'timesent' => 0]);
        if ($queued) { return (int)$queued->id; }
        return (int)$DB->insert_record('lumen10admissao_mail', (object)[
            'requestid' => $requestid, 'kind' => $kind, 'timecreated' => time(), 'nextattempt' => time(),
        ]);
    }

    public static function send(int $id): bool {
        global $DB, $CFG;
        $lock = \core\lock\lock_config::get_lock_factory(workflow::COMPONENT)->get_lock('mail-' . $id, 0);
        if (!$lock) { return false; }
        $userlock = null;
        $requestlock = null;
        $registration = null;
        $temporary = null;
        $attachment = '';
        try {
            $job = $DB->get_record('lumen10admissao_mail', ['id' => $id], '*', MUST_EXIST);
            if ($job->timesent) { return true; }
            if ($job->nextattempt > time() || $job->attempts >= 10) { return false; }
            $requestlock = \core\lock\lock_config::get_lock_factory(workflow::COMPONENT)->get_lock(hash('sha256', 'request:' . $job->requestid), 10);
            if (!$requestlock) { return false; }
            $job = $DB->get_record('lumen10admissao_mail', ['id' => $id]);
            if (!$job) { return true; }
            $request = $DB->get_record(workflow::TABLE, ['id' => $job->requestid]);
            $valid = ['confirm' => ['awaitingconfirmation'], 'access' => ['pending', 'correction'],
                'correction' => ['correction'], 'rejected' => ['rejected'],
                'approved' => ['approved'], 'enrolled' => ['approved']];
            if (!$request || !$request->email || !in_array($request->status, $valid[$job->kind])) {
                $job->timesent = time(); $job->errorcode = 'obsolete';
                $DB->update_record('lumen10admissao_mail', $job); return true;
            }
            $job->attempts++;
            $job->nextattempt = time() + min(DAYSECS, 60 * (2 ** min(10, $job->attempts)));
            $DB->update_record('lumen10admissao_mail', $job);
            if (!empty($CFG->noemailever)) {
                $job->errorcode = 'email_disabled'; $DB->update_record('lumen10admissao_mail', $job); return false;
            }
            try {
                [$activity, $cm, $reception, $target] = workflow::activity((int)$request->admissaoid);
                if ($reception->category != $request->categoryid || $reception->id != $request->receptioncourseid ||
                        workflow::course((int)$request->courseid)->category != $request->categoryid) {
                    throw new \moodle_exception('error_category', workflow::COMPONENT);
                }
                $recipient = (object)['id' => -99, 'email' => $request->email, 'firstname' => $request->firstname,
                    'lastname' => $request->lastname, 'maildisplay' => 0, 'mailformat' => 0, 'mailstop' => 0,
                    'deleted' => 0, 'suspended' => 0, 'auth' => 'manual', 'mnethostid' => $CFG->mnet_localhost_id,
                    'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => $request->socialname,
                    'lang' => current_language()];
                $course = workflow::course((int)$request->courseid);
                $subject = get_string('mail_subject', workflow::COMPONENT, $request->protocol);
                $body = get_string('mail_intro', workflow::COMPONENT, (object)[
                    'name' => $request->firstname, 'course' => workflow::requested_name($request), 'protocol' => $request->protocol,
                ]);
                if (in_array($job->kind, ['confirm', 'access', 'correction'])) {
                    $purpose = $job->kind === 'confirm' ? 'confirm' : 'access';
                    $token = bin2hex(random_bytes(32));
                    $request->{$purpose . 'hash'} = hash('sha256', $token);
                    $request->{$purpose . 'expires'} = time() + ($purpose === 'confirm' ? DAYSECS : 7 * DAYSECS);
                    $DB->update_record(workflow::TABLE, $request);
                    $url = new \moodle_url('/mod/lumen10admissao/confirmar.php', ['token' => $token, 'purpose' => $purpose]);
                    if ($purpose === 'confirm') {
                        $body .= get_string('mail_confirm_body', workflow::COMPONENT);
                    } else {
                        if ($job->kind === 'correction') { $body .= get_string('mail_correction_body', workflow::COMPONENT, $request->reason); }
                        $body .= get_string('mail_access_body', workflow::COMPONENT);
                    }
                    $body .= $url->out(false) . get_string('mail_ignore', workflow::COMPONENT);
                } else if ($job->kind === 'rejected') {
                    $body .= get_string('mail_rejected_body', workflow::COMPONENT, $request->reason);
                } else {
                    $user = $DB->get_record('user', ['id' => $request->userid, 'deleted' => 0, 'suspended' => 0], '*', MUST_EXIST);
                    // Mailbox mismatch or institutional account changes are never overwritten by admissions.
                    if (\core_text::strtolower($user->email) !== $request->email) { throw new \moodle_exception('error_account', workflow::COMPONENT); }
                    $recipient = $user;
                    $userlock = \core\lock\lock_config::get_lock_factory(workflow::COMPONENT)->get_lock('credentials-' . $user->id, 10);
                    if (!$userlock) { throw new \moodle_exception('error_public', workflow::COMPONENT); }
                    $registration = $DB->get_record('lumen10admissao_reg', ['userid' => $user->id], '*', MUST_EXIST);
                    if ($job->kind === 'approved') {
                        $welcome = (string)workflow::config('welcometext', get_string('welcometext_default', 'mod_lumen10admissao'));
                        $body .= strtr($welcome, declaration::substitutions($request, $registration->number, $request->timecompleted ?: time())) . "\n\n";
                        $file = declaration::issue_locked($request);
                        if ($file) {
                            $attachment = make_request_directory() . '/declaracao-matricula.pdf';
                            $file->copy_content_to($attachment);
                            $body .= get_string('mail_attachment', workflow::COMPONENT);
                        }
                    } else { $body .= get_string('mail_enrolled_body', workflow::COMPONENT); }
                    $body .= get_string('mail_registration_body', workflow::COMPONENT, (object)['registration' => $registration->number, 'platform' => $CFG->wwwroot]);
                    $body .= get_string('mail_username_body', workflow::COMPONENT, $user->username);
                    if ($job->kind === 'approved' && $request->newaccount && !$registration->credentialsent &&
                            get_user_preferences('auth_forcepasswordchange', 0, $user->id)) {
                        // Only a new admissions account receives a temporary password. No cleartext is persisted.
                        $temporary = self::temporary_password($user);
                        if (!update_internal_user_password($user, $temporary)) { throw new \moodle_exception('error_account', workflow::COMPONENT); }
                        $body .= get_string('mail_password_body', workflow::COMPONENT, $temporary);
                    } else {
                        $body .= get_string('mail_existingpassword_body', workflow::COMPONENT) .
                            (new \moodle_url('/login/forgot_password.php'))->out(false) . "\n";
                    }
                    $body .= get_string('mail_reception_body', workflow::COMPONENT) . workflow::reception_url($request)->out(false) . "\n";
                    $body .= get_string('mail_receptioncourse_body', workflow::COMPONENT) . (new \moodle_url('/course/view.php', ['id' => $request->receptioncourseid]))->out(false) . "\n";
                    if ($request->courseid == $request->receptioncourseid) {
                        $body .= get_string('mail_category_body', workflow::COMPONENT);
                    } else if (in_array($request->enrolstatus, ['existing', 'enrolled'])) {
                        $body .= get_string('mail_destination_body', workflow::COMPONENT) . (new \moodle_url('/course/view.php', ['id' => $request->courseid]))->out(false) . "\n";
                    } else { $body .= get_string('mail_teachingpending_body', workflow::COMPONENT); }
                }
                $ok = email_to_user($recipient, \core_user::get_support_user(), $subject, $body, '', $attachment, $attachment ? 'declaracao-matricula.pdf' : '');
                $job->errorcode = $ok ? null : 'mail_delivery_failed';
                if ($ok) {
                    $job->timesent = time();
                    if ($temporary !== null) { $DB->set_field('lumen10admissao_reg', 'credentialsent', time(), ['id' => $registration->id]); }
                }
                $DB->update_record('lumen10admissao_mail', $job);
                return $ok;
            } catch (\Throwable $error) {
                $job->errorcode = 'notification_failed';
                $DB->update_record('lumen10admissao_mail', $job);
                // Never log recipient data, SMTP credentials or bearer tokens in a retry error.
                return false;
            }
        } finally {
            if ($attachment && is_file($attachment)) { unlink($attachment); }
            if ($userlock) { $userlock->release(); } if ($requestlock) { $requestlock->release(); } $lock->release();
        }
    }

    public static function temporary_password(\stdClass $user): string {
        // Cryptographic randomness; never derive credentials from names or admission dates.
        $groups = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%&*+-=?'];
        $chars = [];
        foreach ($groups as $group) { $chars[] = $group[random_int(0, strlen($group) - 1)]; }
        $alphabet = implode('', $groups);
        while (count($chars) < 20) { $chars[] = $alphabet[random_int(0, strlen($alphabet) - 1)]; }
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i); [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        $password = implode('', $chars);
        $error = null;
        if (!check_password_policy($password, $error, $user)) { throw new \moodle_exception('error_credentials', workflow::COMPONENT); }
        return $password;
    }

    public static function dispatch_request(int $id): bool {
        global $DB;
        $ok = true;
        foreach ($DB->get_records('lumen10admissao_mail', ['requestid' => $id, 'timesent' => 0], 'id') as $job) {
            $ok = self::send((int)$job->id) && $ok;
        }
        return $ok;
    }

    public static function resend(int $id): void {
        global $DB;
        $request = workflow::request($id);
        workflow::assert_scope($request, 'approve');
        $last = $DB->get_record_sql('SELECT MAX(timecreated) AS recent FROM {lumen10admissao_mail} WHERE requestid=:id', ['id' => $id]);
        if ($last && $last->recent > time() - 60) { throw new \moodle_exception('error_state', workflow::COMPONENT); }
        $kind = ['awaitingconfirmation' => 'confirm', 'pending' => 'access', 'correction' => 'correction',
            'rejected' => 'rejected', 'approved' => 'approved'][$request->status] ?? null;
        if (!$kind) { throw new \moodle_exception('error_state', workflow::COMPONENT); }
        workflow::locked(['request:' . $id], function() use ($id, $kind, $DB, $request) {
            foreach ($DB->get_records('lumen10admissao_mail', ['requestid' => $id, 'kind' => $kind, 'timesent' => 0]) as $job) {
                $job->attempts = 0; $job->nextattempt = time(); $DB->update_record('lumen10admissao_mail', $job);
            }
            self::queue($id, $kind); workflow::audit($request, 'notification_requested');
        });
    }
}
