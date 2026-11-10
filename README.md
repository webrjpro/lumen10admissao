# Lumen10 Admissions

**Public applications, controlled admission and verifiable enrolment declarations for Moodle.**

Release **1.3.1 / 2026110901** · Activity **mod_lumen10admissao** · Moodle **5.1 and 5.2** · GPL v3 or later.

This repository contains **one independently installable activity**. No auxiliary Lumen10 plugin is required. The optional Student Record integration (`local_lumen10_prontuario`) is used only when installed separately; otherwise applicants use the native reception course. Guardian IA, voice assistance, Prova and its Quiz rule are separate products/components and are not included.

## Download and install

Download **[mod_lumen10admissao-1.3.1.zip](dist/mod_lumen10admissao-1.3.1.zip)** and use Moodle's plugin installer, or extract its `lumen10admissao` directory to `mod/lumen10admissao` (`public/mod/lumen10admissao` with the split checkout layout). Back up code, database and Moodledata before updating. No Composer/npm installation step is required by the packaged plugin.

Do not install GitHub's general repository source archive as one Moodle component. The specific installable ZIP is in `dist`; source code is in [mod_lumen10admissao](mod_lumen10admissao/).

## Highlights

- Public applications with Brazilian CPF validation and private identity documents.
- Confidential visual impairment declaration/report and native labelled forms, keyboard focus, semantic tables and an accessible document dialog.
- Staff review, correction and approval with explicit applicant confirmation before account creation and reception enrolment.
- Random temporary passwords, mandatory first-login change and safe reuse of existing accounts/identifiers.
- Immutable private PDF declarations, optional institutional digital signing, live QR verification and local PDF hash comparison.
- Email queue, action history, privacy export/erasure, configurable retention and configuration-only backup/restore.
- English and Brazilian Portuguese, bundled AMD build/source map and Mustache presentation.

## Documentation and support

- [Complete English documentation](mod_lumen10admissao/README.md)
- [Documentação em português](mod_lumen10admissao/README_pt-BR.md)
- [Validation](VALIDATION.md) and [changes](CHANGELOG.md)
- [Package hashes](dist/SHA256SUMS.txt)
- [Public issue tracker](https://github.com/webrjpro/lumen10admissao/issues)

Configure institutional permissions, SMTP/cron, privacy notices, retention and any optional signing certificate/reCAPTCHA/antivirus. Stored institutional text and issued PDFs are preserved on upgrade. Accessibility depends on the theme, technologies and actual document content; no universal WCAG/PDF-UA certification is claimed.

Publication and automated validation do not imply Moodle Marketplace approval. Submit the single activity ZIP for the component's own review. Never post credentials, applicant data or private certificates/logs in issues.

Copyright © 2026 Carlos Antonio de Oliveira Piquet. [GNU GPL v3 or later](LICENSE.txt). The component's optional offline source-integrity certificate identifies release bytes; private keys and deployment credentials are not distributed.

The 1.3.1 maintenance release adds the required course index and fixes the Marketplace package rejection. Earlier release assets remain available for history; submit the current ZIP linked above.
