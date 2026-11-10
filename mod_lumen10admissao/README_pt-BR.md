# Lumen10 Inscrições e Admissão

**Inscrição pública, análise pela Secretaria, confirmação segura e declaração de matrícula verificável.**

Componente `mod_lumen10admissao` · Versão **1.3.0 / 2026110900** · Moodle **5.1 e 5.2** · GPL v3 ou posterior.

[Documentação completa em inglês](README.md) · [Código e versões](https://github.com/webrjpro/lumen10admissao) · [Suporte](https://github.com/webrjpro/lumen10admissao/issues)

## Um único plugin para instalar e enviar

Envie **somente `mod_lumen10admissao-1.3.0.zip`** no cadastro deste componente no Marketplace. Não há plugin auxiliar obrigatório. Este pacote funciona sem Prova, regra de acesso do Quiz, Guardian IA, Prontuário, Arquivados ou Carteira Acadêmica.

O **Lumen10 Prontuário (`local_lumen10_prontuario`) é uma integração opcional**: quando instalado, acrescenta a ficha e o encaminhamento ao arquivo institucional. Sem ele, o aluno acessa o curso nativo da Secretaria e o fluxo de admissão continua funcionando. A consulta de CPF em tabelas de prontuários/arquivos só ocorre se essas tabelas existirem. O ZIP não inclui esses produtos separados.

## Destaques

- Formulário sem necessidade de conta Moodle prévia: nome, CPF validado, filiação, nome social opcional e confirmação de e-mail.
- RG e certidão obrigatórios; informação de deficiência visual e laudo privado quando declarada.
- Análise, aprovação, correção com preservação de documentos, não aprovação e histórico por Secretaria/turma.
- Aprovação envia link de uso único válido por 24 horas; confirmação exige ação por POST e chave de sessão válida. Apenas abrir o link não cria conta.
- Conta nova com senha temporária aleatória de 20 caracteres e troca obrigatória. Conta existente conserva senha e identificador externo importado.
- Matrícula interna única, sem reutilização do número reservado após anonimização.
- Matrícula na Secretaria e autorização explícita para aulas; proteção contra conflitos de identidade e reativação indevida de vínculos suspensos.
- Fila de e-mail com tentativas e cron nativo do Moodle; declaração PDF privada e imutável, QR Code com situação atual e comparação SHA-256 local no navegador.
- Assinatura visual opcional e assinatura digital com certificado A1 válido da própria instituição; nenhum certificado privado é incluído.
- Inglês e português separados, AMD compilado com mapa de fontes, templates Mustache, formulários nativos e eventos de visualização.
- Privacy API, retenção configurável e backup/restauração somente da configuração da atividade.

## Instalação e configuração

1. Faça backup de código, banco e Moodledata; teste primeiro em homologação.
2. Instale o ZIP por Administração do site → Plugins → Instalar plugins, ou extraia `lumen10admissao` em `mod/lumen10admissao` / `public/mod/lumen10admissao` conforme sua estrutura.
3. Conclua a atualização nativa e limpe caches. Não é necessário executar Composer/npm nem SQL manual para instalar o pacote.
4. Configure SMTP, cron, aviso de uso de dados, retenção, armazenamento e declaração em Plugins → Atividades → Lumen10 Inscrições e Admissão.
5. Adicione a atividade no curso Secretaria da categoria/turma. Mantenha o destino da categoria ou escolha um curso da mesma categoria. Configure datas e abra as inscrições quando estiver pronto.
6. Autorize a equipe com as capacidades de consultar, aprovar e, quando cabível, excluir. A liberação de matrícula exige também a autorização nativa de matrícula manual/papel de estudante.
7. Copie o link público da própria atividade: `/mod/lumen10admissao/cadastro.php?id=CMID`, usando o ID do módulo do curso.

O envio entra em análise. A Secretaria aprova, pede correção ou não aprova. O aluno confirma a aprovação pelo link recebido e recebe as instruções de acesso. Documentos não substituídos permanecem no pedido de correção. Matrícula nas disciplinas segue a decisão da instituição. E-mail na fila não equivale a entrega na caixa do destinatário; confira SMTP/cron.

## Acessibilidade e deficiência visual

Formulários com rótulos e validação nativa, navegação por teclado, foco visível, mensagens textuais, tabelas com cabeçalhos e diálogo identificado ajudam o uso com tecnologias assistivas. Ao fechar a visualização do documento, o foco volta ao controle de abertura. Uma pessoa de confiança pode ajudar no preenchimento.

Laudos ficam na área privada da inscrição. Não é obrigatório usar voz e o pacote não inclui assistente de voz ou Guardian IA. A acessibilidade de PDFs/anexos depende do conteúdo; não há afirmação de PDF/UA completo, conformidade WCAG universal ou certificação independente. Valide os leitores de tela, navegadores e tema usados pela instituição.

## Privacidade, documentos e limites

O plugin trata identificação, CPF, filiação, contato, nome social, deficiência visual declarada, documentos privados, consentimento, situação/motivos, equipe responsável, histórico, notificações, declarações, hashes de links e contadores pseudônimos. A Privacy API descreve os campos e permite exportação/exclusão de dados vinculados a usuários Moodle. Candidatos sem conta contam com retirada elegível pelo acompanhamento; demais solicitações precisam de atendimento institucional.

O prazo de retenção **0, padrão, desativa a exclusão automática**. A tarefa não apaga automaticamente admissões aprovadas. Excluir a inscrição apaga seus arquivos e histórico, preservando a conta e matrículas nativas. O número de matrícula permanece reservado sem vínculo pessoal quando anonimizado. Cópias feitas por integrações opcionais seguem a política do respectivo componente.

PDFs aceitos até 10 MB e JPG/PNG/WebP até 5 MB, com verificação de tipo/conteúdo e orçamento de armazenamento; o plugin conserva reserva de 2 GB livres. Antivírus é o recurso configurado no Moodle. reCAPTCHA nativo é opcional e utiliza o serviço Google quando habilitado. Este plugin não envia dados para voz ou IA.

O QR Code revela nome declarado, instituição, matrícula interna, turma, emissão e situação atual de conta/vínculo; não libera CPF, e-mail, documentos, laudo ou PDF privado. Compartilhar o link de verificação compartilha esses dados exibidos. PDFs emitidos não são reescritos ao editar o modelo. A confiança de assinatura digital depende do certificado institucional e do validador.

Backups da atividade não incluem candidatos, laudos, documentos de identidade, declarações ou matrículas globais. A restauração mantém as inscrições fechadas até reconfiguração. A versão mínima de instalação preservada é 4.5, mas o suporte declarado desta entrega é 5.1/5.2; consulte o relatório de testes antes de escolher outra linha.

## Publicação, suporte e licença

Consulte `VALIDATION.md` e as instruções na pasta de entrega para os resultados efetivamente executados. Publicação no GitHub, testes locais e certificado técnico não equivalem à aprovação do Marketplace. A submissão e análise humana são etapas externas.

Relate problemas sem dados pessoais em https://github.com/webrjpro/lumen10admissao/issues. Nunca publique credenciais, dados de candidatos, certificado A1 ou logs privados. Código sob **GPL v3 ou posterior**, copyright 2026 Carlos Antonio de Oliveira Piquet. O certificado técnico Ed25519 confere os bytes do pacote; não é registro INPI, assinatura ICP-Brasil ou licença remota.
