#!/usr/bin/env python3
"""
validate_test_docs.py
Validador de Integridade Documental e Governança de Qualidade do LawFirm CRM.

Executa 12 verificações estritas entre o catálogo (TEST_CATALOG.yaml),
a documentação modular (quality/modules/) e a base de código (tests/).
"""

import os
import sys
import yaml
from pathlib import Path

# Configuração de encoding para Windows console
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except Exception:
        pass

# Diretórios base
SCRIPT_DIR = Path(__file__).resolve().parent
REPO_ROOT = SCRIPT_DIR.parent.parent
QUALITY_DIR = REPO_ROOT / "quality"
CATALOG_PATH = QUALITY_DIR / "TEST_CATALOG.yaml"
MODULES_DIR = QUALITY_DIR / "modules"

# Domínios e Camadas permitidos
VALID_DOMAINS = {
    "Legal", "Financial", "GED", "SaaS", "AI",
    "Escavador", "DataJud", "Whatsapp", "TenantFinance",
    "Atendimento", None
}
VALID_LAYERS = {"domain", "platform", "governance", "e2e"}
VALID_TYPES = {"Unit", "Feature", "Security", "E2E"}
VALID_PRIORITIES = {"P0", "P1", "P2"}
VALID_STATUSES = {
    "planned", "implemented_unverified", "active",
    "quarantined", "disabled", "retired"
}
VALID_EXTERNAL_MODES = {"none", "mock", "real_controlled"}


class ValidationError(Exception):
    pass


def validate_catalog(catalog_path=CATALOG_PATH, repo_root=REPO_ROOT):
    errors = []
    warnings = []

    if not catalog_path.exists():
        return [f"Arquivo de catálogo não encontrado: {catalog_path}"], []

    try:
        with open(catalog_path, "r", encoding="utf-8") as f:
            data = yaml.safe_load(f)
    except Exception as e:
        return [f"Erro ao parsear YAML do catálogo: {e}"], []

    if not isinstance(data, dict) or "tests" not in data or not isinstance(data["tests"], list):
        return ["TEST_CATALOG.yaml deve conter uma lista na chave raiz 'tests'."], []

    tests = data["tests"]
    seen_ids = set()

    for idx, test in enumerate(tests):
        test_id = test.get("id")

        # Regra 1: Campos obrigatórios presentes
        required_fields = ["id", "name", "layer", "module", "type", "priority", "status", "automated", "documentation"]
        for field in required_fields:
            if field not in test or test[field] is None:
                errors.append(f"Teste #{idx} ({test_id or 'SEM ID'}): Campo obrigatório ausente ou nulo: '{field}'")

        if not test_id:
            continue

        # Regra 2: Unicidade de IDs
        if test_id in seen_ids:
            errors.append(f"Regra 2 (Duplicidade): ID duplicado encontrado no catálogo: '{test_id}'")
        seen_ids.add(test_id)

        # Regra 3: Validação de Domínio e Camada
        domain = test.get("domain")
        if domain not in VALID_DOMAINS:
            errors.append(f"Regra 3 (Domínio): Teste '{test_id}' possui domínio inválido: '{domain}'. Permitidos: {VALID_DOMAINS}")

        layer = test.get("layer")
        if layer not in VALID_LAYERS:
            errors.append(f"Regra 3 (Camada): Teste '{test_id}' possui camada inválida: '{layer}'. Permitidos: {VALID_LAYERS}")

        test_type = test.get("type")
        if test_type not in VALID_TYPES:
            errors.append(f"Regra 3 (Tipo): Teste '{test_id}' possui tipo inválido: '{test_type}'. Permitidos: {VALID_TYPES}")

        priority = test.get("priority")
        if priority not in VALID_PRIORITIES:
            errors.append(f"Regra 3 (Prioridade): Teste '{test_id}' possui prioridade inválida: '{priority}'. Permitidos: {VALID_PRIORITIES}")

        status = test.get("status")
        if status not in VALID_STATUSES:
            errors.append(f"Regra 3 (Status): Teste '{test_id}' possui status inválido: '{status}'. Permitidos: {VALID_STATUSES}")
            continue

        # Regra 4: Existência de arquivo para status que exigem código
        test_file = test.get("test_file")
        if status in {"implemented_unverified", "active", "quarantined", "disabled"}:
            if not test_file:
                errors.append(f"Regra 4 (Arquivo Obrigatório): Teste '{test_id}' com status '{status}' deve declarar 'test_file'.")
            else:
                full_test_path = repo_root / test_file
                if not full_test_path.exists():
                    errors.append(f"Regra 4 (Arquivo Inexistente): Teste '{test_id}' aponta para arquivo inexistente: '{test_file}'")

        # Regra 5: Metadados para 'planned' e 'implemented_unverified'
        if status in {"planned", "implemented_unverified"}:
            if test.get("last_verified_version") is not None or test.get("last_verified_date") is not None:
                errors.append(f"Regra 5 (Metadados Prematuros): Teste '{test_id}' com status '{status}' deve ter last_verified_version e last_verified_date como null.")

        # Regra 6: Metadados para 'active'
        if status == "active":
            if not test.get("last_verified_version") or not test.get("last_verified_date"):
                errors.append(f"Regra 6 (Metadados Ausentes): Teste '{test_id}' com status 'active' deve declarar 'last_verified_version' e 'last_verified_date'.")

        # Regra 7: Justificativa para 'quarantined' e 'disabled'
        if status in {"quarantined", "disabled"}:
            notes = test.get("notes")
            if not notes or not str(notes).strip():
                errors.append(f"Regra 7 (Justificativa Obrigatória): Teste '{test_id}' com status '{status}' deve preencher o campo 'notes'.")

        # Regra 8: Existência do documento do módulo
        doc_rel = test.get("documentation")
        if doc_rel:
            full_doc_path = repo_root / doc_rel
            if not full_doc_path.exists():
                errors.append(f"Regra 8 (Documento Inexistente): Teste '{test_id}' aponta para documentação inexistente: '{doc_rel}'")

        # Regra 9: Validação de Serviços Externos
        ext_services = test.get("external_services", [])
        if isinstance(ext_services, list):
            for svc in ext_services:
                if isinstance(svc, dict):
                    mode = svc.get("mode")
                    if mode not in VALID_EXTERNAL_MODES:
                        errors.append(f"Regra 9 (Modo Externo): Teste '{test_id}' serviço '{svc.get('name')}' possui modo inválido: '{mode}'.")

        # Regra 10: Responsabilidade para testes P0 quarentenados ou desativados
        owner = test.get("owner", "unassigned")
        if priority == "P0" and status in {"quarantined", "disabled"}:
            if not owner or owner == "unassigned":
                errors.append(f"Regra 10 (Responsável P0): Teste P0 '{test_id}' em estado '{status}' deve possuir 'owner' atribuído.")

        # Regra 11: Rastreabilidade de código (source_references)
        source_refs = test.get("source_references")
        if source_refs and isinstance(source_refs, list):
            for sref in source_refs:
                if isinstance(sref, dict) and "path" in sref:
                    ref_path = repo_root / sref["path"]
                    if not ref_path.exists():
                        warnings.append(f"Aviso: Teste '{test_id}' source_reference path inexistente: '{sref['path']}'")

    # Regra 12: Validação de todos os arquivos de módulos em quality/modules/
    if MODULES_DIR.exists():
        for mod_file in MODULES_DIR.glob("*.md"):
            if mod_file.stat().st_size == 0:
                errors.append(f"Regra 12 (Módulo Vazio): Documento de módulo '{mod_file.name}' está vazio.")

    # Regra 13: Verificação contra componentes obsoletos/descontinuados
    # Componentes removidos do projeto não podem ser declarados como ativos ou exigir testes de preservação
    PROHIBITED_COMPONENTS = ["packages/SuiteZap/Whaticket", "STATIC-INTEGRITY-001", "LEGACY-CHAT-001"]
    for test in tests:
        t_id = test.get("id", "")
        if t_id in PROHIBITED_COMPONENTS:
            errors.append(f"Regra 13 (Componente Obsoleto): Teste '{t_id}' pertence a componente removido e não deve constar no catálogo.")
        for sref in test.get("source_references", []):
            if isinstance(sref, dict) and any(p in sref.get("path", "") for p in PROHIBITED_COMPONENTS):
                errors.append(f"Regra 13 (Caminho Obsoleto): Teste '{t_id}' referencia caminho descontinuado: '{sref.get('path')}'.")

    return errors, warnings


def main():
    print("=" * 70)
    print("🛡️  Executando Validador de Integridade Documental (LawFirm Quality)")
    print("=" * 70)

    errors, warnings = validate_catalog()

    if warnings:
        print(f"\n⚠️  {len(warnings)} Aviso(s) Encontrado(s):")
        for w in warnings:
            print(f"  - {w}")

    if errors:
        print(f"\n❌  {len(errors)} Erro(s) de Integridade Encontrado(s):")
        for e in errors:
            print(f"  - {e}")
        print("\n🚫 Validação FALHOU! Corrija as inconsistências acima.")
        sys.exit(1)
    else:
        print("\n✅ Validação APROVADA com 0 erros de integridade documental!")
        print("=" * 70)
        sys.exit(0)


if __name__ == "__main__":
    main()
