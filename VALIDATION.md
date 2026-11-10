# Validação — Lumen10 Admissão 1.3.1

| Verificação | Resultado |
| --- | --- |
| Moodle 5.1.8 / PHP 8.2.35 / MariaDB 11.4.13 | 78 testes / 694 verificações; aprovados |
| Moodle 5.2.4+ / PHP 8.3.36 / MariaDB 11.4.13 | 78 testes / 694 verificações; aprovados |
| Navegador sem outros componentes Lumen10 | 40 checagens; aprovadas, sem erros JavaScript |
| Integridade no ambiente isolado atualizado | Versão 2026110901, assinatura e arquivos conferidos |
| Sintaxe e idiomas | 50 arquivos PHP / 418 chaves em cada idioma / 1 módulo AMD |
| ZIP nativo | Pasta única lumen10admissao; index.php e demais arquivos exigidos presentes; CRC e fontes conferidos |
| Manifesto Ed25519 | Assinatura, lista de arquivos e hashes SHA-256 conferidos |

Os mesmos 78 casos foram executados nas duas versões. Dados de teste são sintéticos e e-mails externos ficaram desabilitados nos ambientes de teste. A listagem de atividades foi conferida no navegador; testes nativos verificam curso, visibilidade e capacidade.

O navegador também verificou formulário, CPF, laudo condicional, cinco larguras de tela, documentos privados, aprovação, confirmação por POST, conta/matrícula sem Prontuário, PDF, consulta pública sem CPF/e-mail/laudo, hash local e link de uso único.

Limites: banco executado MariaDB; não se afirma teste em PostgreSQL, carga, certificação independente de segurança/acessibilidade ou aprovação do Marketplace. Os dados de produção e a publicação são verificados separadamente no comprovante de entrega.

SHA-256 do ZIP: `31626c1e3355ae548060e54f3b95e0eebf58ca2a4d36d7704008584c297710a0`.
