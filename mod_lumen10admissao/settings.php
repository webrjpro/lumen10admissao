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
defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_lumen10admissao/integrity', get_string('integrityheading', 'mod_lumen10admissao'),
        html_writer::link(new moodle_url('/mod/lumen10admissao/certificado.php'), get_string('integritylink', 'mod_lumen10admissao'))));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/enabled', get_string('globalenabled', 'mod_lumen10admissao'),
        get_string('globalenabled_desc', 'mod_lumen10admissao'), 1));
    $settings->add(new admin_setting_configtextarea('mod_lumen10admissao/privacynotice', get_string('privacynotice', 'mod_lumen10admissao'),
        get_string('privacynotice_desc', 'mod_lumen10admissao'),
        get_string('privacynotice_default', 'mod_lumen10admissao')));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/retentiondays', get_string('retentiondays', 'mod_lumen10admissao'),
        get_string('retentiondays_desc', 'mod_lumen10admissao'), 0, PARAM_INT));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/uploadbudgetmb', get_string('uploadbudget', 'mod_lumen10admissao'),
        get_string('uploadbudget_desc', 'mod_lumen10admissao'), 10240, PARAM_INT));
    $settings->add(new admin_setting_heading('mod_lumen10admissao/declarationheading', get_string('declarationheading', 'mod_lumen10admissao'),
        get_string('declarationheading_desc', 'mod_lumen10admissao')));
    $settings->add(new admin_setting_configtextarea('mod_lumen10admissao/welcometext', get_string('welcometext', 'mod_lumen10admissao'),
        get_string('welcometext_desc', 'mod_lumen10admissao'),
        get_string('welcometext_default', 'mod_lumen10admissao')));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/declarationenabled', get_string('declarationenabled', 'mod_lumen10admissao'), '', 1));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationinstitution', get_string('institutionname', 'mod_lumen10admissao'),
        get_string('institutionname_desc', 'mod_lumen10admissao'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtextarea('mod_lumen10admissao/declarationtext', get_string('declarationtext', 'mod_lumen10admissao'),
        get_string('declarationtext_desc', 'mod_lumen10admissao'),
        get_string('declarationdefault', 'mod_lumen10admissao')));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationsubtitle', get_string('declarationsubtitle', 'mod_lumen10admissao'), '', get_string('declarationsubtitle_default', 'mod_lumen10admissao'), PARAM_TEXT));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationmodality', get_string('declarationmodality', 'mod_lumen10admissao'), '', get_string('declarationmodality_default', 'mod_lumen10admissao'), PARAM_TEXT));
    $settings->add(new admin_setting_configselect('mod_lumen10admissao/declarationtimezone', get_string('declarationtimezone', 'mod_lumen10admissao'),
        get_string('declarationtimezone_desc', 'mod_lumen10admissao'), 'America/Sao_Paulo', \core_date::get_list_of_timezones()));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/declarationdecoration', get_string('declarationdecoration', 'mod_lumen10admissao'),
        get_string('declarationdecoration_desc', 'mod_lumen10admissao'), 1));
    $images = ['accepted_types' => ['.png', '.jpg', '.jpeg'], 'maxbytes' => 2 * 1024 * 1024, 'maxfiles' => 1];
    $settings->add(new admin_setting_configstoredfile('mod_lumen10admissao/declarationlogo', get_string('declarationlogo', 'mod_lumen10admissao'),
        get_string('declarationlogo_desc', 'mod_lumen10admissao'), 'decllogo', 0, $images));
    $settings->add(new admin_setting_configstoredfile('mod_lumen10admissao/declarationwatermark', get_string('declarationwatermark', 'mod_lumen10admissao'),
        get_string('declarationwatermark_desc', 'mod_lumen10admissao'), 'declwatermark', 0, $images));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationwatermarktext', get_string('declarationwatermarktext', 'mod_lumen10admissao'),
        get_string('declarationwatermarktext_desc', 'mod_lumen10admissao'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/declarationqr', get_string('declarationqr', 'mod_lumen10admissao'), '', 1));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/declarationvisual', get_string('declarationvisual', 'mod_lumen10admissao'), '', 1));
    $settings->add(new admin_setting_configstoredfile('mod_lumen10admissao/declarationsignature', get_string('declarationsignature', 'mod_lumen10admissao'),
        get_string('declarationsignature_desc', 'mod_lumen10admissao'), 'declsignature', 0, $images));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationsignatory', get_string('declarationsignatory', 'mod_lumen10admissao'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('mod_lumen10admissao/declarationsignatoryrole', get_string('declarationsignatoryrole', 'mod_lumen10admissao'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configcheckbox('mod_lumen10admissao/declarationdigital', get_string('declarationdigital', 'mod_lumen10admissao'),
        get_string('declarationdigital_desc', 'mod_lumen10admissao'), 0));
    $settings->add(new admin_setting_configstoredfile('mod_lumen10admissao/declarationcertificate', get_string('declarationcertificate', 'mod_lumen10admissao'),
        get_string('declarationcertificate_desc', 'mod_lumen10admissao'),
        'declp12', 0, ['accepted_types' => ['.p12', '.pfx'], 'maxbytes' => 1024 * 1024, 'maxfiles' => 1]));
    $settings->add(new \mod_lumen10admissao\admin_secret('mod_lumen10admissao/declarationcertpassword', get_string('declarationcertpassword', 'mod_lumen10admissao'),
        get_string('declarationcertpassword_desc', 'mod_lumen10admissao'), ''));
}
