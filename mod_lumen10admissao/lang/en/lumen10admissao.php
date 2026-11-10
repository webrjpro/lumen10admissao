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
 * Language strings.
 *
 * @package mod_lumen10admissao
 * @copyright 2026 Carlos Antonio de Oliveira Piquet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['accountactive'] = 'Active';
$string['accountclosed'] = 'Closed';
$string['accountstatus'] = 'Account status';
$string['accountsuspended'] = 'Suspended';
$string['accountunconfirmed'] = 'Unconfirmed';
$string['action'] = 'Action';
$string['actions'] = 'Actions';
$string['active'] = 'Active';
$string['allstatuses'] = 'All statuses';
$string['alsoenrol'] = 'Also enrol in the teaching course';
$string['applicantsystem'] = 'Applicant / system';
$string['application'] = 'Application';
$string['applicationintro'] = 'Complete the information below. After approval and confirmation, you will receive reception access to complete your record and provide remaining documents.';
$string['approveconfirmation'] = 'Approve and request enrolment confirmation';
$string['attempts'] = 'Attempts';
$string['audit_approve'] = 'Approved: confirmation requested';
$string['audit_confirmed'] = 'Applicant confirmed: admission completed';
$string['audit_correction'] = 'Correction requested';
$string['audit_correction_accessed'] = 'Applicant accessed correction request';
$string['audit_declaration_issued'] = 'Enrolment declaration issued';
$string['audit_declaration_viewed'] = 'Declaration viewed';
$string['audit_document_viewed'] = 'Document viewed';
$string['audit_enrol'] = 'Course enrolment released';
$string['audit_notification_requested'] = 'Notification resend requested';
$string['audit_reject'] = 'Application not approved';
$string['audit_resubmitted'] = 'Correction resubmitted for review';
$string['audit_submitted'] = 'Application received for review';
$string['audit_withdrawn'] = 'Application withdrawn';
$string['author'] = 'Author';
$string['autoenrol'] = 'Suggest course enrolment when approving';
$string['awaitingdelivery'] = 'Awaiting delivery';
$string['awaitingnotice'] = 'The application is approved and only awaits applicant confirmation. No second review is required.';
$string['backapplications'] = 'Back to applications';
$string['birth'] = 'Birth';
$string['birthmarriage'] = 'Birth or marriage certificate';
$string['category'] = 'Category';
$string['categorydestination'] = 'Apply to this category — access to reception';
$string['cert_birth'] = 'Birth certificate';
$string['cert_marriage'] = 'Marriage certificate';
$string['certificateheading'] = 'Technical integrity certificate';
$string['certificateinvalid'] = 'Unable to confirm package integrity. Check the certificate, file versions and Sodium extension.';
$string['certificatelimitations'] = 'The signature identifies the delivered bytes. This certificate is not an INPI registration, an ICP-Brasil signature or a restriction on server administrators.';
$string['certificatepagetitle'] = 'Integrity and licence — Lumen10 Admissions';
$string['certificatevalid'] = 'Valid signature. Files match the certified package.';
$string['certtype'] = 'Certificate type';
$string['checked'] = 'Last checked';
$string['checkedfiles'] = 'Files checked';
$string['choosecourse'] = 'Select a destination';
$string['closedate'] = 'Applications close';
$string['closedocument'] = 'Close document';
$string['codelicence'] = 'Code licence';
$string['completednotice'] = 'Admission completed. Welcome, access instructions and the enrolment declaration (when enabled) are sent by email. No further review is required.';
$string['completerecord'] = 'Complete your record and submit reception documents';
$string['component'] = 'Component';
$string['configure'] = 'Configure applications';
$string['confirm'] = 'Confirm';
$string['confirmaction'] = 'Confirm action';
$string['confirmapproved'] = 'Confirm approved admission';
$string['confirmdelete'] = 'Yes, delete application';
$string['confirmedemail'] = 'Confirmed email';
$string['confirmintro'] = 'Reception approved your application. Confirm below to complete enrolment and receive access instructions by email. You do not need to complete the form again.';
$string['consent'] = 'I have read the notice and request a review of my application';
$string['contact'] = 'Contact and submission';
$string['continue'] = 'Continue';
$string['copied'] = 'Link copied';
$string['copyfallback'] = 'Select and copy the link';
$string['copylink'] = 'Copy application link';
$string['copyrightnotice'] = 'Copyright © 2026 Carlos Antonio de Oliveira Piquet. This component is licensed under GPL v3 or later. Commercial services and branding are subject to separate agreements.';
$string['correctapplication'] = 'Respond to correction request';
$string['correctionintro'] = 'Reception requested a correction. Continue below to review the request and correct your information or documents.';
$string['courseenrol'] = 'Course enrolment';
$string['courseunavailable'] = 'Course unavailable';
$string['cpf'] = 'Brazilian CPF';
$string['currentstatus'] = 'Current status: {$a}';
$string['datause'] = 'Use of your information';
$string['date'] = 'Date';
$string['declarationcertificate'] = 'Institutional A1 certificate (.p12/.pfx)';
$string['declarationcertificate_desc'] = 'Private file, unavailable through file URLs. Up to 1 MB. Signature trust and certificate chain depend on the supplied certificate.';
$string['declarationcertpassword'] = 'A1 certificate password';
$string['declarationcertpassword_desc'] = 'Stored using Moodle encryption; also preserve the encryption key in private backups. The stored value is not displayed.';
$string['declarationcode'] = 'Declaration code';
$string['declarationdecoration'] = 'Show green Lumen10 template decorations';
$string['declarationdecoration_desc'] = 'Green corners and dotted decorations. Disable for a neutral institutional document.';
$string['declarationdefault'] = 'This is to certify that {aluno}, CPF {cpf}, registration {matricula}, is enrolled {vinculo}, in {modalidade} mode, at {instituicao}.

This declaration reflects enrolment at the time of issue. Check the QR code for current status.';
$string['declarationdigital'] = 'Digitally sign with an institutional certificate';
$string['declarationdigital_desc'] = 'Requires a valid A1 PKCS#12 certificate and its password. May be combined with a signature image. No demonstration certificate is used.';
$string['declarationenabled'] = 'Issue and send an enrolment declaration';
$string['declarationheading'] = 'Welcome and enrolment declaration';
$string['declarationheading_desc'] = 'The declaration is issued and attached to the access email after the applicant confirms approval. Changes apply to new issues; existing PDFs remain unchanged.';
$string['declarationhint'] = 'Configure text, logo, watermark, QR code and signatures in Site administration → Plugins → Activity modules → Lumen10 Admissions.';
$string['declarationlogo'] = 'Header logo';
$string['declarationlogo_desc'] = 'PNG/JPG up to 2 MB. Leave empty to use the main Moodle logo when available.';
$string['declarationmodality'] = 'Declaration study mode';
$string['declarationmodality_default'] = 'Distance learning';
$string['declarationnotfound'] = 'Declaration not found or unavailable for verification.';
$string['declarationqr'] = 'Show verification QR code';
$string['declarationrelationship'] = 'in course {$a}';
$string['declarationsignatory'] = 'Signatory name';
$string['declarationsignatoryrole'] = 'Signatory position';
$string['declarationsignature'] = 'Signature image';
$string['declarationsignature_desc'] = 'Visual PNG/JPG signature up to 2 MB. This is not a certificate-based digital signature.';
$string['declarationsubtitle'] = 'Declaration subtitle';
$string['declarationsubtitle_default'] = 'Electronically issued document';
$string['declarationtext'] = 'Declaration text';
$string['declarationtext_desc'] = 'Plain text, up to 8,000 characters. Placeholders: {aluno}, {nomesocial}, {cpf}, {matricula}, {turma}, {curso}, {secretaria}, {instituicao}, {data}, {protocolo}, {modalidade}, {vinculo}.';
$string['declarationtimezone'] = 'Declaration time zone';
$string['declarationtimezone_desc'] = 'Used for the issue date; does not change the time zone of other Moodle components.';
$string['declarationtitle'] = 'Enrolment declaration';
$string['declarationview'] = 'View enrolment declaration';
$string['declarationvisual'] = 'Show visual signature field';
$string['declarationwatermark'] = 'Watermark image';
$string['declarationwatermark_desc'] = 'PNG/JPG up to 2 MB. Applied transparently to keep the text readable.';
$string['declarationwatermarktext'] = 'Watermark fallback text';
$string['declarationwatermarktext_desc'] = 'Used when no image is configured. Leave empty to omit a text watermark.';
$string['declarationwelcome'] = 'Declaration and welcome';
$string['delete'] = 'Delete';
$string['deleteapplication'] = 'Delete application';
$string['deletednotice'] = 'Application deleted. The student\'s account and other records were preserved.';
$string['deletequestion'] = 'Are you sure you want to delete the application for {$a->name} ({$a->protocol})?';
$string['deletewarning'] = 'This application\'s data, attachments and declaration will be removed. The student\'s account, reserved registration number, course enrolments and existing student record will be kept.';
$string['delivery'] = 'Delivery';
$string['destination'] = 'Application destination';
$string['disablednotice'] = 'Applications closed. Enable them in settings when ready to share the link.';
$string['documenthint'] = 'PDF: up to 10 MB. JPG, PNG or WebP: up to 5 MB. Include both sides of your identity document in one file.';
$string['documentsheading'] = 'Application documents';
$string['email'] = 'Email';
$string['emailconfirm'] = 'Confirm your email';
$string['emptyapplications'] = 'No applications found in this reception course.';
$string['enablednotice'] = 'Applications enabled. The link respects activity visibility and the configured application period.';
$string['enrol_enrolled'] = 'Enrolled through admissions';
$string['enrol_existing'] = 'Already actively enrolled';
$string['enrol_notrequested'] = 'Enrolment not yet requested';
$string['enrolactive'] = 'active enrolment';
$string['enrolclosed'] = 'Enrolment closed / no active enrolment';
$string['enrolhint'] = 'When unchecked, only reception access is released first. Staff can authorise teaching course enrolment later. The optional Lumen10 Student Record integration is used only when installed.';
$string['enrolinactive'] = 'suspended or outside the enrolment period';
$string['enrolmentstatus'] = 'Enrolment status';
$string['enrolnone'] = 'not enrolled';
$string['enrolremoved'] = 'Enrolment removed or missing';
$string['enrolsuspended'] = 'Suspended or outside the enrolment period';
$string['error_account'] = 'The matching account is suspended, unconfirmed or privileged. Admissions cannot change this account.';
$string['error_authorizer'] = 'The institution must check the staff authorisation before completing this admission. No account was released.';
$string['error_category'] = 'The destination course and its reception course must be in the same category. If either course was moved, review the configuration before continuing.';
$string['error_closed'] = 'Applications closed on {$a} (institutional time). Contact reception for information about the next application period.';
$string['error_conflict'] = 'The CPF/email identity conflicts or matches multiple accounts. Verify identity and request a correction. No account was changed.';
$string['error_consent'] = 'You must accept the notice before submitting.';
$string['error_course'] = 'This course is unavailable for applications.';
$string['error_cpf'] = 'Enter a valid Brazilian CPF.';
$string['error_credentials'] = 'The randomly generated temporary password does not meet this Moodle site\'s password policy. Review the institutional password policy before completing admission.';
$string['error_dates'] = 'The closing date must be later than the opening date.';
$string['error_declarationconfig'] = 'Review the enrolment declaration configuration: images, text, certificate and password must be valid. No digital signature was applied without a valid certificate.';
$string['error_disabled'] = 'Applications are currently unavailable. Please contact the institution.';
$string['error_documents'] = 'Upload your identity document and birth or marriage certificate to complete the application.';
$string['error_emailmatch'] = 'The email addresses must match.';
$string['error_enrol'] = 'An enrolment in this course is suspended, expired or starts in the future. Resolve it using enrolment management; admissions will not reactivate it.';
$string['error_filesize'] = 'The limit is 10 MB for PDFs and 5 MB for images.';
$string['error_filetype'] = 'The file must be a valid PDF, JPG, PNG or WebP with a matching extension and content.';
$string['error_filiation'] = 'Enter at least one parent or guardian name.';
$string['error_invalid'] = 'Check the information provided and try again.';
$string['error_manual'] = 'Configure an active manual enrolment method and a valid student role for this course.';
$string['error_namelength'] = 'The first name and remaining names must each be at most 100 characters long.';
$string['error_notopen'] = 'Applications open on {$a} (institutional time).';
$string['error_paused'] = 'The institution has paused new applications. Please contact reception for further information.';
$string['error_public'] = 'Unable to complete the request now. Check the information or try again later.';
$string['error_registration'] = 'Unable to reserve a unique registration number. Try again; no duplicate number was created.';
$string['error_state'] = 'The application status has changed. Refresh the page before continuing.';
$string['error_storage'] = 'Admission document storage has reached the available limit. Please contact reception.';
$string['error_token'] = 'This link has expired or has already been used. Request a new tracking link.';
$string['error_visualchoice'] = 'Indicate whether you have a visual impairment.';
$string['error_visualreport'] = 'Upload the visual impairment report for authorised staff to review. Accepted formats: PDF up to 10 MB or JPG, PNG and WebP up to 5 MB.';
$string['eventapplication'] = 'Lumen10 admission application action';
$string['existingaccountnotice'] = 'Existing account: {$a->name} ({$a->username}). Course status: {$a->status}. The existing password will be kept.';
$string['fatherlabel'] = 'Father/guardian';
$string['fathername'] = 'Father or guardian name';
$string['filter'] = 'Filter';
$string['filterstatus'] = 'Filter by status';
$string['fullname'] = 'Full name';
$string['globalenabled'] = 'Allow public applications';
$string['globalenabled_desc'] = 'Each activity retains its own course, category and dates. Disabling this setting pauses all new applications.';
$string['hashmatch'] = 'Integrity confirmed: this exactly matches the issued PDF.';
$string['hashmismatch'] = 'This file differs from the issued PDF. Check its origin and signature before accepting it.';
$string['hashunavailable'] = 'Unable to check the file in this browser. Use a current browser over HTTPS.';
$string['history'] = 'History';
$string['honeypot'] = 'Leave this field empty';
$string['identitydocs'] = 'Identity documents';
$string['identitydocument'] = 'Identity document (RG)';
$string['inactiveunconfirmed'] = 'Inactive — unconfirmed account';
$string['institutionname'] = 'Institution name on declarations';
$string['institutionname_desc'] = 'Leave empty to use the Moodle site full name.';
$string['integrityheading'] = 'Authorship, integrity and licence';
$string['integritylink'] = 'Verify the technical certificate and licence';
$string['issued'] = 'Issued';
$string['issuer'] = 'Issuing institution';
$string['lumen10_admissao:approve'] = 'Decide applications and create student accounts';
$string['lumen10_admissao:configure'] = 'Open applications and configure reception';
$string['lumen10_admissao:review'] = 'Review applications and admission data';
$string['lumen10admissao:addinstance'] = 'Add an admissions activity';
$string['lumen10admissao:approve'] = 'Approve applications and create accounts in this category';
$string['lumen10admissao:delete'] = 'Delete applications and attachments in this reception course after confirmation';
$string['lumen10admissao:review'] = 'Review applications in this reception course';
$string['lumen10admissao:view'] = 'View own admission applications';
$string['mail_access'] = 'Tracking';
$string['mail_approved'] = 'Welcome, access and declaration';
$string['mail_confirm'] = 'Approval: confirm enrolment';
$string['mail_correction'] = 'Correction requested';
$string['mail_enrolled'] = 'Course access released';
$string['mail_rejected'] = 'Application not approved';
$string['manualdelete'] = 'Application manually deleted';
$string['marriage'] = 'Marriage';
$string['medicalreport'] = 'Visual impairment report';
$string['modulename'] = 'Lumen10 Admissions';
$string['modulename_help'] = 'Receive applications in a reception course. Each activity has its own public form. Staff approval sends a one-use confirmation link; confirmation creates or reuses a Moodle account and enrols the applicant in reception. Enrolment in teaching courses remains a separate institutional decision. An optional destination course must belong to the same category.';
$string['modulenameplural'] = 'Lumen10 Admissions';
$string['motherlabel'] = 'Mother/guardian';
$string['mothername'] = 'Mother or guardian name';
$string['no'] = 'No';
$string['noownapplications'] = 'You have no applications in this activity.';
$string['notapplicable'] = 'Not applicable';
$string['notification'] = 'Notification';
$string['notifications'] = 'Notifications';
$string['openapplications'] = 'Open public applications';
$string['opendate'] = 'Applications open';
$string['owner'] = 'Identified owner';
$string['parenthint'] = 'Enter at least one parent or guardian name. If unknown, enter “Not provided”.';
$string['pdfcourse'] = 'Course:';
$string['pdfcpf'] = 'CPF:';
$string['pdfcurrentstatus'] = 'Current Moodle status';
$string['pdfenrolmentdate'] = 'Enrolment date:';
$string['pdffooterpage'] = 'Page {$a->page} / {$a->total}';
$string['pdffooterverification'] = 'Verification: {$a}';
$string['pdfheading'] = 'ENROLMENT DECLARATION';
$string['pdfissueddate'] = 'Issue date: {$a}';
$string['pdfmodality'] = 'Study mode:';
$string['pdfregistration'] = 'Registration:';
$string['pdfregular'] = 'Enrolled';
$string['pdfsignatory'] = 'Academic reception representative';
$string['pdfsignaturepurpose'] = 'Enrolment declaration issued by the institution';
$string['pdfsizelimit'] = 'Select a PDF up to 20 MB.';
$string['pdfstatus'] = 'Status:';
$string['pdfstudent'] = 'Student:';
$string['pdfvalidation'] = 'Enrolment verification';
$string['pluginadministration'] = 'Manage applications and admissions';
$string['pluginname'] = 'Lumen10 Admissions';
$string['preservefiles'] = 'Upload only the documents you want to replace. Existing documents are kept when no new file is uploaded.';
$string['privacy:metadata:actorid'] = 'Account responsible for the action.';
$string['privacy:metadata:declaration'] = 'Issued enrolment declaration, verification code, displayed identity, issue date, PDF hash and signature type.';
$string['privacy:metadata:documents'] = 'Identity documents, birth or marriage certificates and, when visual impairment is declared, a private report reviewed by authorised staff.';
$string['privacy:metadata:email'] = 'Email address used for confirmation, tracking and access notifications.';
$string['privacy:metadata:externalmail'] = 'Contact details and messages delivered to the email server configured in Moodle.';
$string['privacy:metadata:field:accessexpires'] = 'Tracking link expiry time.';
$string['privacy:metadata:field:accesshash'] = 'Hash of the one-use tracking token.';
$string['privacy:metadata:field:action'] = 'Identifier of the performed action.';
$string['privacy:metadata:field:activekey'] = 'Pseudonymous key used to prevent duplicate active applications.';
$string['privacy:metadata:field:actorid'] = 'Account responsible for the action, or zero for a public/system action.';
$string['privacy:metadata:field:admissaoid'] = 'Activity that received the application.';
$string['privacy:metadata:field:attempts'] = 'Number of delivery attempts.';
$string['privacy:metadata:field:categoryid'] = 'Application category.';
$string['privacy:metadata:field:classname'] = 'Category displayed in the document.';
$string['privacy:metadata:field:code'] = 'Random public declaration verification code.';
$string['privacy:metadata:field:confirmexpires'] = 'Confirmation link expiry time.';
$string['privacy:metadata:field:confirmhash'] = 'Hash of the one-use confirmation token.';
$string['privacy:metadata:field:consenttime'] = 'Time the applicant accepted the data use notice.';
$string['privacy:metadata:field:courseid'] = 'Requested destination course.';
$string['privacy:metadata:field:cpf'] = 'Brazilian CPF identity number provided by the applicant.';
$string['privacy:metadata:field:credentialsent'] = 'Time the temporary password was first delivered.';
$string['privacy:metadata:field:email'] = 'Application contact email address.';
$string['privacy:metadata:field:enrolrequested'] = 'Whether teaching course enrolment was requested.';
$string['privacy:metadata:field:enrolstatus'] = 'Authorised enrolment status.';
$string['privacy:metadata:field:errorcode'] = 'Generic delivery error code without credentials.';
$string['privacy:metadata:field:fathername'] = 'Father or guardian name.';
$string['privacy:metadata:field:firstname'] = 'Applicant first name.';
$string['privacy:metadata:field:hits'] = 'Attempt count in the rate limiting window.';
$string['privacy:metadata:field:institution'] = 'Issuing institution displayed in the document.';
$string['privacy:metadata:field:kind'] = 'Notification type.';
$string['privacy:metadata:field:lastname'] = 'Applicant remaining names.';
$string['privacy:metadata:field:mothername'] = 'Mother or guardian name.';
$string['privacy:metadata:field:newaccount'] = 'Whether admissions created the account.';
$string['privacy:metadata:field:nextattempt'] = 'Scheduled next delivery attempt.';
$string['privacy:metadata:field:note'] = 'Audit note.';
$string['privacy:metadata:field:number'] = 'Unique internal number; remains reserved without a personal link after anonymisation.';
$string['privacy:metadata:field:phone'] = 'Legacy contact telephone number, when present.';
$string['privacy:metadata:field:protocol'] = 'Unique application reference.';
$string['privacy:metadata:field:ratekey'] = 'Pseudonymous rate limiting key.';
$string['privacy:metadata:field:reason'] = 'Reason recorded during review.';
$string['privacy:metadata:field:receptioncourseid'] = 'Responsible reception course.';
$string['privacy:metadata:field:registration'] = 'Registration number displayed in the issued declaration.';
$string['privacy:metadata:field:requestid'] = 'Application associated with the record.';
$string['privacy:metadata:field:reviewerid'] = 'Account responsible for the decision.';
$string['privacy:metadata:field:sha256'] = 'SHA-256 digest of the issued PDF.';
$string['privacy:metadata:field:signaturetype'] = 'Signature type applied to the PDF.';
$string['privacy:metadata:field:socialname'] = 'Applicant preferred name.';
$string['privacy:metadata:field:status'] = 'Current application status.';
$string['privacy:metadata:field:studentname'] = 'Student name displayed in the issued declaration.';
$string['privacy:metadata:field:timecompleted'] = 'Admission completion time.';
$string['privacy:metadata:field:timecreated'] = 'Record creation time.';
$string['privacy:metadata:field:timemodified'] = 'Last modification time.';
$string['privacy:metadata:field:timesent'] = 'Notification delivery or closure time.';
$string['privacy:metadata:field:timeverified'] = 'Time email ownership was verified through a link.';
$string['privacy:metadata:field:userid'] = 'Moodle account linked to the applicant.';
$string['privacy:metadata:field:usermodified'] = 'Account that changed the activity configuration.';
$string['privacy:metadata:field:visualimpairment'] = 'Declared visual impairment; supporting reports are held in a private file area.';
$string['privacy:metadata:field:windowstart'] = 'Start of the rate limiting window.';
$string['privacy:metadata:identity'] = 'Applicant name, contact information, CPF and destination course.';
$string['privacy:metadata:logs'] = 'Admission action and decision history.';
$string['privacy:metadata:mail'] = 'Notification delivery status, attempts and dates.';
$string['privacy:metadata:rate'] = 'Temporary counters and pseudonymous rate limiting keys.';
$string['privacy:metadata:registration'] = 'A unique internal registration number and first credential delivery status. Reserved numbers are not reused after personal data is deleted.';
$string['privacy:metadata:requests'] = 'Admission applications, identity information and staff decisions.';
$string['privacy:metadata:reviewerid'] = 'Staff member responsible for the decision.';
$string['privacy:metadata:status'] = 'Application status, reason and dates.';
$string['privacy:metadata:tokens'] = 'Link hashes and expiry dates. Cleartext bearer tokens are not stored.';
$string['privacy:metadata:userid'] = 'Moodle account linked after verification and approval.';
$string['privacy:metadata:visualimpairment'] = 'Visual impairment declared for admission review; the report is held in a private file area.';
$string['privacyactions'] = 'Lumen10 actions performed';
$string['privacyapplications'] = 'Lumen10 applications';
$string['privacydeclaration'] = 'Declaration issue';
$string['privacynotice'] = 'Application privacy notice';
$string['privacynotice_default'] = 'Reception will use your information, identity document and certificate to review your application and prepare platform access. Remaining details and documents are provided in reception after approval and confirmation.';
$string['privacynotice_desc'] = 'Notice shown before submission; adapt it to the institution\'s policy.';
$string['privacynoticefallback'] = 'The institution will use this information to review your application.';
$string['privacyregistration'] = 'Lumen10 registration';
$string['protocol'] = 'Reference';
$string['publiclink'] = 'Public application link';
$string['queuednotice'] = 'Action completed. The notification remains queued; check SMTP and cron before resending.';
$string['reason'] = 'Reason (required for correction or rejection)';
$string['reasonshort'] = 'Reason';
$string['received'] = 'Received';
$string['receivednotice'] = 'The form has been received for review. Wait for an email decision. If approved, you will receive an enrolment confirmation link. If corrections are needed, you will receive a correction link. Please also check your spam folder.';
$string['receptioncategory'] = 'Reception and category';
$string['receptionenrol'] = 'Reception enrolment';
$string['registration'] = 'Internal registration number';
$string['registrationdata'] = 'Application information';
$string['registrationpending'] = 'Generated after confirmation of approved admission';
$string['reject'] = 'Do not approve';
$string['releaseenrol'] = 'Release course enrolment';
$string['requestcorrection'] = 'Request correction';
$string['resendnotification'] = 'Resend email notification';
$string['restorednotice'] = 'This restored activity has applications closed. Select the destination course in the activity settings.';
$string['resubmit'] = 'Save and resubmit for review';
$string['resubmittednotice'] = 'Information submitted for review.';
$string['retentiondays'] = 'Retention of unapproved applications (days)';
$string['retentiondays_desc'] = '0 disables automatic deletion. Set according to institutional policy; this does not delete Moodle accounts or student records.';
$string['review'] = 'Review';
$string['reviewapplication'] = 'Review application';
$string['scope_course'] = 'Course: {$a->course} · Category: {$a->category}';
$string['scope_reception'] = 'Category: {$a->category} · Reception: {$a->reception}';
$string['scopehint'] = 'This activity receives applications for the category of its course. Approved applicants are enrolled in this reception course after confirmation.';
$string['searchapplication'] = 'Search applications';
$string['searchplaceholder'] = 'Name, email or reference';
$string['selectoption'] = 'Select an option';
$string['sentnotice'] = 'Action completed and notification sent.';
$string['sessionrecovery'] = 'Your session expired or changed. Text fields have been kept. Select your documents again, complete reCAPTCHA and submit again.';
$string['signupclosed'] = 'Applications closed';
$string['signupnotopen'] = 'Applications have not opened yet';
$string['signuppaused'] = 'Applications paused';
$string['signupunavailable'] = 'Applications unavailable';
$string['socialname'] = 'Preferred name (optional)';
$string['socialnamelabel'] = 'Preferred name';
$string['status'] = 'Status';
$string['status_approved'] = 'Admission completed';
$string['status_awaitingconfirmation'] = 'Approved — awaiting applicant confirmation';
$string['status_correction'] = 'Correction requested';
$string['status_expired'] = 'Application expired';
$string['status_pending'] = 'Under review';
$string['status_rejected'] = 'Application not approved';
$string['status_unverified'] = 'Legacy application awaiting review';
$string['status_withdrawn'] = 'Application withdrawn';
$string['student'] = 'Student';
$string['submit'] = 'Submit application';
$string['suspended'] = 'Suspended';
$string['taskdispatch'] = 'Send Lumen10 admission notifications and maintain applications';
$string['technicalissue'] = 'Technical issue date';
$string['trackapplication'] = 'Track application';
$string['underreview'] = 'Your application is under review. Reception will email you its decision.';
$string['uploadbudget'] = 'Application document storage budget (MB)';
$string['uploadbudget_desc'] = 'Global logical limit for this plugin. Keeps at least 2 GB free on disk; does not physically reserve space per applicant.';
$string['verificationheading'] = 'Enrolment verification';
$string['verificationnotice'] = 'Status is checked live in Moodle. A previously issued declaration does not guarantee that the account or enrolment remains active.';
$string['verifiedscope'] = 'Verified enrolment scope';
$string['verifyenrolment'] = 'Verify enrolment';
$string['verifyfile'] = 'Check PDF integrity';
$string['verifyfileintro'] = 'Select your PDF to check that it exactly matches the issued file. Verification takes place in your browser without uploading the file.';
$string['version'] = 'Version';
$string['viewdocument'] = 'View';
$string['visualdeclared'] = 'Visual impairment declared';
$string['visualheading'] = 'Visual impairment';
$string['visualhint'] = 'A trusted person can help complete this form. If you declare a visual impairment, upload the report for authorised staff to review. Voice assistance is not required at this stage.';
$string['visualno'] = 'I do not have a visual impairment';
$string['visualquestion'] = 'Do you have a visual impairment?';
$string['visualreport'] = 'Visual impairment report (required when answering yes)';
$string['visualyes'] = 'I have a visual impairment';
$string['welcometext'] = 'Welcome message';
$string['welcometext_default'] = 'Welcome to {instituicao}! Your admission is complete. Access reception to complete your record and provide the remaining documents.';
$string['welcometext_desc'] = 'Placeholders: {aluno}, {nomesocial}, {matricula}, {turma}, {curso}, {secretaria}, {instituicao}, {data}, {protocolo}.';
$string['withdraw'] = 'Withdraw application and delete data';
$string['withdrawnnotice'] = 'Application withdrawn; your application data has been deleted.';
$string['yes'] = 'Yes';

$string['mail_subject'] = 'Lumen10 — {$a}';
$string['mail_intro'] = 'Hello, {$a->name}.

Course/category: {$a->course}
Reference: {$a->protocol}

';
$string['mail_confirm_body'] = 'Reception approved your application.
Confirm enrolment using the link below. You do not need to fill in the form again or undergo another review.
After confirming, you will receive a welcome message, the platform URL, access instructions and an enrolment declaration when enabled.
The link is valid for 24 hours and can be used once.
';
$string['mail_correction_body'] = 'Reception requested a correction:
{$a}

';
$string['mail_access_body'] = 'Track or correct your application using the link below.
The link is valid for seven days and can be used once.
';
$string['mail_ignore'] = '

If you did not request this application, ignore this message.
';
$string['mail_rejected_body'] = 'Your application was not approved.
Reason: {$a}
Contact reception if you need clarification.
';
$string['mail_attachment'] = 'Your enrolment declaration is attached to this message.

';
$string['mail_enrolled_body'] = 'Your course enrolment has been released.

';
$string['mail_registration_body'] = 'Internal registration: {$a->registration}
Platform: {$a->platform}
';
$string['mail_username_body'] = 'Username: {$a}
';
$string['mail_password_body'] = 'Temporary password: {$a}
Moodle will require you to choose a new password when you sign in.
';
$string['mail_existingpassword_body'] = 'Use your existing password. If you have forgotten it, request a reset in Moodle:
';
$string['mail_reception_body'] = '
Reception access to complete your record and provide documents:
';
$string['mail_receptioncourse_body'] = '
Reception course:
';
$string['mail_category_body'] = '
Your application is linked to this category. Complete the remaining information and documents in reception; teaching courses follow the institutional release procedure.
';
$string['mail_destination_body'] = '
Requested course:
';
$string['mail_teachingpending_body'] = '
Teaching course access is awaiting institutional authorisation.
';
$string['confirmcontinue'] = 'Confirm and continue';
$string['signin'] = 'Sign in to the platform';
$string['noactivities'] = 'No admission activities are available in this course.';
