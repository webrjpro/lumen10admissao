# Validation — 9 November 2026

| Environment / check | Result |
| --- | --- |
| Moodle 5.1.8, PHP 8.2.35, MariaDB 11.4.13 | 76 PHPUnit tests / 689 assertions passed |
| Moodle 5.2.4+, PHP 8.3.36, MariaDB 11.4.13 | 76 PHPUnit tests / 689 assertions passed |
| Browser workflow without other Lumen10 components | 37 checks passed; no JavaScript runtime errors |
| Independent native installation/upgrade | Only mod_lumen10admissao 2026110900 among Lumen10 components |
| PHP syntax / language parity / source maps | 48 PHP files, 417 keys in each language, 1 AMD module; passed |
| Standard Grunt AMD build | Passed with zero allowed lint warnings |
| ZIP and signed manifest | CRC, exact file bytes, SHA-256 and Ed25519 verified |

The same 76 tests ran on both branches; they are not 152 distinct test cases. All fixture users/data were synthetic. Browser tests used disabled external email; PHPUnit captured email without external delivery. No external voice or AI request was required or made by the admission workflow checks.

Browser checks cover English forms, conditional visual impairment reports, five widths from 320 to 1366 pixels, CPF rejection/formatting, pending applications without accounts, private documents, staff review, dialog naming/focus, approval, one-use POST confirmation, independent account/reception enrolment, private declaration PDF, live public verification without CPF/email/medical reports, local SHA-256 matching/mismatch and rejected token replay. Identical scan filenames in separate fields were accepted with native upload checks retained.

Native tests cover identity, authorisation, enrolment, password generation/delivery, consent, controlled CAPTCHA cases, interrupted uploads, application periods, private reports, privacy export/erasure, retention, backup/restore, real metadata fields, standard view events and public admission without course catalogue browsing permission.

Limits: MariaDB was exercised; this report does not claim PostgreSQL execution, load testing, universal accessibility compliance, independent security certification or Marketplace approval. No production update was performed in this admissions delivery.

## Package

`mod_lumen10admissao-1.3.0.zip` — SHA-256 `464a2010239fe2beea2fea0bebfffe56d1b9c259b2ad2903e6bba19a6b305c17`.
