# GED — Documentos, Privilégios e Propriedade (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Onda 1e)

## Escopo
Upload, download, exclusão e PDFs (procuração/contrato) de documentos do processo.

## Invariantes
- Permissões `lawfirm.documentos.view/create/delete` (novas `view`/`delete` no ACL).
- Propriedade via `assertTenantProcesso`: documento/anexo cujo processo não resolve no tenant → 404.
- `DocumentService::deleteFile` verifica propriedade antes de apagar do S3.

## Testes
| ID | Nome | Status |
|---|---|---|
| `GED-SEC-001` | Gates + fim do IDOR em downloads | `active` |
