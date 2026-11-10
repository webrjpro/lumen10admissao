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
 * Progressive admission form and private document enhancements.
 * @module mod_lumen10admissao/form
 * @copyright 2026 Carlos Antonio de Oliveira Piquet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {getStrings} from 'core/str';
export const init = async() => {
    const keys = ['copied', 'copyfallback', 'pdfsizelimit', 'hashmatch', 'hashmismatch', 'hashunavailable'];
    const values = await getStrings(keys.map((key) => ({key, component: 'mod_lumen10admissao'})));
    const strings = Object.fromEntries(keys.map((key, index) => [key, values[index]]));
    const dialog = document.querySelector('.l10adm-dialog');
    if (dialog) {
        const body = dialog.querySelector('.l10adm-dialog-body');
        let opener;
        const close = function() {
            dialog.close();
        };
        dialog.querySelector('button').addEventListener('click', close);
        dialog.addEventListener('close', function() {
            body.replaceChildren();
            if (opener) {
                opener.focus();
            }
        });
        document.querySelectorAll('.l10adm-preview').forEach(function(link) {
            link.addEventListener('click', function(event) {
                if (typeof dialog.showModal !== 'function') {
                    return;
                }
                event.preventDefault();
                opener = link;
                dialog.querySelector('strong').textContent = link.dataset.label;
                const documentView = document.createElement(
                    link.dataset.mime === 'application/pdf' ? 'iframe' : 'img',
                );
                documentView.src = link.href;
                if (documentView.tagName === 'IFRAME') {
                    documentView.title = link.dataset.label;
                } else {
                    documentView.alt = link.dataset.label;
                }
                body.replaceChildren(documentView);
                dialog.showModal();
            });
        });
    }
    document.querySelectorAll('.l10adm input[name="cpf"]').forEach(function(input) {
        const mask = function() {
            const digits = input.value.replace(/\D/g, '').slice(0, 11);
            input.value = digits
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
        };
        input.addEventListener('input', mask);
        mask();
    });
    document.querySelectorAll('.l10adm-copy').forEach(function(button) {
        button.addEventListener('click', async function() {
            const input = document.getElementById(button.dataset.target);
            input.focus();
            input.select();
            try {
                await navigator.clipboard.writeText(input.value);
                button.textContent = strings.copied;
            } catch (error) {
                button.textContent = strings.copyfallback;
            }
        });
    });
    const verification = document.querySelector('[data-l10verifyhash]');
    if (verification) {
        verification.addEventListener('change', async function() {
            const result = document.getElementById('l10adm-verify-result');
            const file = verification.files[0];
            if (!file) {
                result.textContent = '';
                return;
            }
            if (file.size > 20 * 1024 * 1024) {
                result.textContent = strings.pdfsizelimit;
                return;
            }
            try {
                const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
                const actual = Array.from(new Uint8Array(digest), (byte) =>
                    byte.toString(16).padStart(2, '0'),
                ).join('');
                result.textContent =
                    actual === verification.dataset.l10verifyhash ? strings.hashmatch : strings.hashmismatch;
            } catch (error) {
                result.textContent = strings.hashunavailable;
            }
        });
    }
};
