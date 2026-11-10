# Lumen10 Admissions

**Public applications, controlled admission and verifiable enrolment declarations for Moodle.**

Component: `mod_lumen10admissao` · Release **1.3.0** · Build **2026110900** · GNU GPL v3 or later.

[Português brasileiro](README_pt-BR.md) · [Source](https://github.com/webrjpro/lumen10admissao) · [Issues](https://github.com/webrjpro/lumen10admissao/issues) · [Downloads](https://github.com/webrjpro/lumen10admissao/tree/main/dist)

## What it does

Lumen10 Admissions connects a public application form to an institution's reception team. Applicants can submit identity documents without an existing Moodle account. Authorised staff review an application, request corrections or approve it. Approval sends a one-use confirmation link; the applicant's explicit confirmation completes account creation or safe account reuse and reception enrolment.

Each activity belongs to one reception course and category. An institution can receive applications for the category as a whole, or choose a teaching course in the same category. Teaching course access is an explicit institutional decision. Existing accounts keep their password and imported external identifiers.

## Highlights

- Public form with Brazilian CPF validation, parent/guardian information, optional preferred name and matching email confirmation.
- Required identity document and birth/marriage certificate; a private supporting report is required when visual impairment is declared.
- Separate review and approval capabilities, category boundaries, searchable paginated staff list and action history.
- Correction requests preserve files that are not replaced. Eligible applicants can withdraw through their authenticated tracking session.
- One-use confirmation links valid for 24 hours and tracking/correction links valid for seven days. GET requests never consume these links; confirmation requires POST and a valid Moodle session key.
- New accounts receive a cryptographically random 20-character temporary password and must change it on first login. Existing passwords are preserved.
- Unique internal registration numbers are reserved and never reused, even after personal data is erased.
- Durable email queue with retries and Moodle cron integration. No cleartext password or bearer token is stored in the queue.
- Immutable private PDF declarations, QR verification of live account/enrolment status, optional visual signature and optional institutional A1 PKCS#12 digital signing.
- Local browser SHA-256 comparison against the issued PDF; selecting a file for this check does not upload it.
- English and Brazilian Portuguese language packs, Moodle AMD JavaScript with bundled minified output/source maps, Mustache presentation and native forms.
- Privacy API metadata/export/erasure, private File API storage, scheduled retention and configuration-only activity backup/restore.

## Dependencies and optional integrations

**This is a single independently installable activity. No auxiliary Lumen10 plugin is required. Submit only `mod_lumen10admissao-1.3.0.zip` for this component.**

The optional **Lumen10 Student Record (`local_lumen10_prontuario`)** integration provides a richer reception record and archival hand-off when that plugin is installed. Without it, the student is directed to the native reception course and admission still works. CPF consistency checks consult compatible record/archive tables only when those tables exist.

The Student Record plugin, Archive, Academic Card, Guardian IA, Prova and its Quiz access rule are separate products/components. They are not bundled with this activity ZIP and are not dependencies of this submission. Installing this plugin does not install them.

Moodle's native manual enrolment method, email facilities, scheduled tasks, File API, form framework and PDF library provide the underlying services. Moodle's optional configured antivirus scans uploads; this plugin does not ship a separate antivirus. No external subscription, commercial licence server or AI key is needed.

## Compatibility

The declared supported branches for this release are **Moodle 5.1 and 5.2**. Validation details are included in `VALIDATION.md` in the public repository and the delivery folder. The retained installer floor in `requires` is Moodle 4.5; that floor is not a claim that this release was validated on all intervening branches. Use a declared supported branch for this release.

Use a PHP version and extensions supported by your Moodle branch, including `fileinfo`, image decoding and the native PDF dependencies. Institutional digital signing additionally needs OpenSSL and a valid institution-supplied certificate/password. Offline package signature verification uses Sodium; inability to verify the technical package certificate does not lock out learners.

## Installation and upgrade

1. Back up the database, Moodledata and code before updating. Try the update on a staging copy first.
2. Download the component ZIP from the repository’s `dist` directory. Upload it through **Site administration → Plugins → Install plugins**, or extract its `lumen10admissao` directory into `mod/lumen10admissao` (`public/mod/lumen10admissao` with the split checkout layout).
3. Complete Moodle's native plugin installation/upgrade and purge caches. Do not upload GitHub's general repository source archive as a Moodle plugin package.
4. Configure site settings under **Plugins → Activity modules → Lumen10 Admissions**. Check email delivery, scheduled cron, storage and the application privacy notice.
5. No Composer/npm command, private key or manual SQL installation step is required by the distributed ZIP. Development source is included and AMD output is already built.

## Setting up an application period

1. Create or select a reception course inside the intended category. Add the Lumen10 Admissions activity.
2. Keep the category/reception destination for category-wide admission, or select a teaching course in the same category. Courses moved to another category must be reconfigured before admission can proceed.
3. Configure opening/closing dates, visibility and public applications. New and restored activities do not open registrations automatically.
4. Give designated staff the `review` and, where appropriate, `approve` capability in the activity/course context. Deletion has its own capability. Grant native manual enrolment and assignable student-role permissions only to authorised staff.
5. Copy the activity's public URL: `/mod/lumen10admissao/cadastro.php?id=CMID`. `CMID` is the course module ID, not the activity instance ID.
6. Staff review attachments and approve, request a correction or reject with a reason. Approval queues a confirmation email; the account is completed only after the applicant confirms.
7. Check notification delivery in the application history. Configure SMTP and Moodle cron; queued delivery is not proof that the recipient has received the message.

## Declarations and signatures

Configure the welcome text, institution name, logo, watermark, subtitle, study mode, time zone, signatory, QR code and signatures in site settings. Supported placeholders are listed alongside the settings; their identifiers remain stable in both languages. Institution-configured text is preserved on upgrade and is not automatically translated when the interface language changes.

Generated PDFs remain unchanged after settings are edited. New settings affect new documents. The public random verification code reveals the declared student name, institution, internal registration number, category, issue time and live account/enrolment state. It does not grant access to the private PDF, CPF, email, identity documents or medical report. Treat sharing a verification URL as sharing the displayed verification information.

A signature image is a visual mark. A certificate-based signature requires the institution's valid PKCS#12 A1 certificate and password. Certificate trust and chain validation depend on the institution's certificate and the reader's validator. No demonstration or private signing certificate is included. The separate offline source-integrity certificate identifies package bytes and is not an ICP-Brasil/INPI certification.

## Accessibility

Native Moodle form labels and validation, keyboard-operable controls, visible focus, text status labels, semantic table headers and a named native document dialog support assistive technology use. The document dialog returns focus to its opener. A trusted person may help an applicant complete the form. Visual impairment information and supporting reports remain private to the applicant's permitted session/account and authorised reviewers.

Voice assistance is not required to apply. This standalone package does not provide Guardian IA or a voice assistant. Document uploads and generated PDFs depend on the accessibility of their content; the PDF generator does not establish fully tagged PDF/UA conformance. Test the institutional theme, actual files, screen reader and browser combination used by your learners. These features are not a claim of universal WCAG compliance or an independent accessibility certification.

## Personal data and retention

The plugin stores applicant identity/contact/CPF, parent/guardian names, preferred name, declared visual impairment, private documents, consent time, application state/reasons, staff identifiers, token hashes/expiry dates, action history, notification delivery metadata, issued declarations and pseudonymous abuse counters. All personal fields are described through the Privacy API. Email messages are delivered through the mail server configured in Moodle.

If the site enables native reCAPTCHA, Google's service processes the usual challenge/browser information under the site's configured integration. ReCAPTCHA is optional; no key is supplied by this plugin. Configure the privacy notice and institutional policy accordingly. No voice or AI provider is contacted by this activity.

The retention setting applies to eligible unapproved applications. **Default 0 disables automatic deletion.** Approved admissions are not automatically erased by that task. Institutions must set an appropriate retention period and handle subject requests, including applicants who do not yet have a Moodle account. The Privacy API handles linked Moodle users; the public tracking withdrawal flow covers eligible unapproved applications.

Deleting an application removes its private files, declaration, notification queue and history, but preserves the Moodle account and native course enrolments. Global registration erasure removes the personal account link while keeping an anonymous reserved-number tombstone. Optional Student Record/archive copies are controlled by their own component and privacy policies.

Activity backups restore configuration and intro files only: applicants, identity/medical files, declarations and global registration numbers are not copied. Restored public applications stay closed until an administrator selects a valid destination and enables them.

## Security and operational limits

- Native context/capability checks protect review, decisions, deletion and private file endpoints. State-changing actions require a valid session key.
- File extensions, detected MIME type, signatures, image decoding and size are checked. PDFs are limited to 10 MB; images to 5 MB. Supported uploads are PDF/JPG/PNG/WebP.
- A global logical storage budget and at least 2 GB free disk protect admission uploads. Monitor server capacity; these controls do not replace backups or an antivirus policy.
- Existing suspended, privileged or unconfirmed accounts and inactive enrolments are not silently changed or reactivated. Conflicting CPF/email identities require staff resolution.
- Rate limiting, honeypot and optional native reCAPTCHA reduce abuse; they do not guarantee protection against every attack or email-delivery failure.
- This release does not add public JSON business endpoints; native Moodle forms and POST controllers remain authoritative. AMD translation loading uses Moodle's native `core/str` service.
- Viewing the administrative activity records Moodle's native view event; it does not approve an applicant or count as completion of an academic admission.

## Development, support and licence

Run the native plugin tests through Moodle's PHPUnit environment with `--test-suffix _test.php`. Build JavaScript from a Moodle checkout with `grunt amd --root=public/mod/lumen10admissao --max-lint-warnings=0` (adjust `public/` for the checkout). The release ZIP already includes the built files.

Report reproducible, redacted problems in the public issue tracker. Never post applicant information, passwords, SMTP details, PKCS#12 files or private logs. Source publication and local tests do not imply Moodle Marketplace approval; submission and human review are separate steps.

Copyright © 2026 Carlos Antonio de Oliveira Piquet. **GNU General Public License v3 or later**; see [LICENSE.txt](LICENSE.txt), [licence notes](LICENCA_USO.md) and [authorship notes](PROPRIEDADE_INTELECTUAL.md). The optional package integrity certificate can be checked at `mod/lumen10admissao/certificado.php` by a site administrator. No private signing key, account credential, production configuration or learner record is included in the ZIP.
