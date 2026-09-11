# ADR-004: Portão de Validação Estática da Documentação Viva (validate_test_docs.py)

## Status
Aprovado

## Contexto
Em projetos de longa duração com múltiplos agentes e desenvolvedores, a documentação técnica tende a se degradar rapidamente se não for validada programaticamente. O catálogo de testes (`TEST_CATALOG.yaml`) e os documentos funcionais (`quality/modules/`) precisam estar em sincronia contínua com os arquivos de teste no disco.

## Decisão
1. **Script de Validação Estática**: Implementação do script `validate_test_docs.py` com 12 regras de integridade.
2. **Ciclo de Vida em 6 Estados**:
   - `planned`: Teste especificado, sem arquivo de código obrigatório, `automated: false`.
   - `implemented_unverified`: Arquivo criado no disco, `automated: true`, aguardando baseline.
   - `active`: Teste com execução real comprovada e metadados de versão/data.
   - `quarantined`: Teste instável isolado com justificativa e prazo obrigatórios.
   - `disabled`: Teste intencionalmente desativado com justificativa técnica formal.
   - `retired`: Teste descontinuado com ID preservado e proibição de reuso.
3. **Bloqueio no CI/CD**: O pipeline de PR falha imediatamente se houver divergência entre arquivos, catálogo ou matriz gerada.

## Consequências
- A documentação torna-se um artefato vivo e compilável.
- Zero testes declarados sem existência real ou código sem catalogação.
