# Resumo Executivo e Handoff (07 de Setembro de 2026)

Este documento registra as implementações, correções e descobertas técnicas realizadas na presente sessão, servindo como ponto de partida para os próximos desenvolvimentos e como registro histórico de decisões arquiteturais.

## 1. Correção e Refatoração da Importação do WhatsApp (Evolution API)

**Problema Original:**  
A importação de mensagens do WhatsApp estava trazendo mensagens de outras pessoas (transbordo/vazamento de grupos ou listas de transmissão), e na tentativa de corrigir isso, as mensagens recebidas (inbound) pararam de ser importadas.

**Investigação e Descobertas Técnicas (Evolution API / Baileys):**
- Foi descoberto que a Evolution API não suporta o filtro `fromMe` na cláusula `where`. 
- Ao analisar o banco de dados interno da Evolution API, identificamos um comportamento peculiar do WhatsApp (Baileys) na versão atual:
  - **Mensagens Enviadas (Outbound):** São salvas com a chave `key.remoteJid` igual ao número de telefone real do contato (ex: `5511999999999@s.whatsapp.net`).
  - **Mensagens Recebidas (Inbound):** São salvas usando o ID Oculto da Meta/WhatsApp (conhecido como `@lid`, ex: `127282177925288@lid`) no campo `key.remoteJid`, enquanto o número de telefone real fica armazenado na coluna secundária **`key.remoteJidAlt`**.
- Por conta dessa diferença, uma busca estrita apenas por `remoteJid` ignorava completamente as mensagens recebidas.

**Solução Aplicada:**
- Alteramos a query na API (`fetchMessagesByDateRange` no `EvolutionService`) para usar o operador `$OR` nativo do Prisma, buscando o número do processo tanto em `key.remoteJid` quanto em `key.remoteJidAlt`.
- **Filtro de Segurança (Post-filter):** Foi mantido um filtro rigoroso no PHP que itera sobre o payload retornado e descarta sumariamente qualquer mensagem que não tenha o JID exato da contraparte (evitando contaminação caso o número apareça perdido no array de menções de um grupo).
- **Status:** Testado e validado. O sistema agora puxa perfeitamente os dois lados da conversa de forma isolada e limpa.

## 2. Isolamento Multi-Tenant e Deleção Física no S3

**Problema Original:**  
Arquivos e mídias de WhatsApp estavam sendo baixados no S3 sem separação clara por inquilino, e a exclusão da mensagem não apagava o arquivo físico, gerando lixo no storage.

**Solução Aplicada:**
- **Prefixo de Tenant:** O `DocumentService` e o `WhatsappImportController` foram ajustados para salvar os arquivos dinamicamente na pasta isolada do respectivo tenant (`tenant_{id}/whatsapp/...`).
- **Observer de Exclusão (Cascading):** Foram criados observers no evento `deleting` (método `booted()`) nos Models `Anexo` e `ProcessoWhatsappMessage`. Quando o usuário exclui uma mensagem importada (botão de lixeira individual), o Laravel aciona automaticamente o `Storage::disk('s3')->delete($path)` para remover fisicamente o arquivo correspondente do S3 antes de apagar o registro do banco.

## 3. Exportação do Histórico (PDF + ZIP)

**Solução Aplicada:**
- Adicionado um botão "Exportar" no modal de histórico de conversas do processo.
- Criado o método `exportZip` no `WhatsappImportController`.
- A funcionalidade gera um PDF renderizado via DOMPDF com todas as mensagens (em formato de bolhas de chat), baixa todos os anexos físicos vinculados a essas mensagens no S3, agrupa tudo em um arquivo `.zip` na pasta temporária e faz o download para a máquina do usuário.
- Ao final da requisição, os arquivos temporários criados no servidor são completamente expurgados.

## 4. UI/UX: Painel de Assinatura (Créditos de IA)

**Problema Original:**  
Inconsistência visual e de nomenclaturas nos botões de compra avulsa de IA e problemas de layout no input numérico ("R$" ficava truncado/sobreposto).

**Soluções Aplicadas no `index.blade.php`:**
- **Nomenclatura:** Todos os botões foram alterados para `SuiteCoins Créditos de IA`.
- **Valores Avulsos:** As opções rápidas foram ajustadas para R$ 25,00, R$ 35,00 e R$ 45,00.
- **Formatação de Botões (Main e Modal):** O layout dos 3 botões rápidos e dos 3 botões dentro do Modal de Escolha de Pagamento (PIX, Cartão, Boleto) foram 100% padronizados para usarem o design limpo (`bg-blue-50`, texto `blue-700` e borda `blue-200`).
- **Input de Outro Valor:** O layout do campo `Outro Valor (R$)` foi refatorado. O posicionamento absoluto (que causava truncamento) foi substituído por um container `flex` robusto, onde o "R$" fica isolado numa caixa de fundo cinza, e o campo numérico (agora em **negrito** e com padrão de R$ 50,00) fica perfeitamente legível em qualquer dispositivo. O texto do modal JS também foi atualizado para referenciar "SuiteCoins".

---
**Fim de Expediente:** Tudo documentado, testado e em conformidade. Bom descanso!
