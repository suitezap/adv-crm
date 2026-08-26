"""
test_validate_test_docs.py
Testes unitários para o script de validação documental (quality/scripts/validate_test_docs.py)
"""

import tempfile
from pathlib import Path
import yaml
import pytest
import sys

# Adiciona o diretório de scripts ao PATH para importação direta
SCRIPTS_DIR = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(SCRIPTS_DIR))

from validate_test_docs import validate_catalog


def create_temp_catalog(tmp_path, tests_data):
    cat_file = tmp_path / "TEST_CATALOG.yaml"
    with open(cat_file, "w", encoding="utf-8") as f:
        yaml.dump({"tests": tests_data}, f)
    return cat_file


def test_valid_planned_test(tmp_path):
    # Cria doc fake
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    tests_data = [{
        "id": "AUTH-001",
        "name": "Teste planejado",
        "domain": "SaaS",
        "layer": "platform",
        "module": "auth",
        "type": "Feature",
        "priority": "P1",
        "status": "planned",
        "automated": False,
        "test_file": None,
        "documentation": "quality/modules/auth.md",
        "last_verified_version": None,
        "last_verified_date": None,
        "owner": "unassigned"
    }]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert len(errors) == 0


def test_duplicate_id_detection(tmp_path):
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    tests_data = [
        {
            "id": "DUP-001",
            "name": "Teste 1",
            "domain": "Legal",
            "layer": "domain",
            "module": "auth",
            "type": "Feature",
            "priority": "P1",
            "status": "planned",
            "automated": False,
            "documentation": "quality/modules/auth.md",
            "last_verified_version": None,
            "last_verified_date": None
        },
        {
            "id": "DUP-001",
            "name": "Teste 2 duplicado",
            "domain": "Legal",
            "layer": "domain",
            "module": "auth",
            "type": "Feature",
            "priority": "P1",
            "status": "planned",
            "automated": False,
            "documentation": "quality/modules/auth.md",
            "last_verified_version": None,
            "last_verified_date": None
        }
    ]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert any("Duplicidade" in e for e in errors)


def test_missing_test_file_for_implemented_test(tmp_path):
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    tests_data = [{
        "id": "MISSING-FILE-001",
        "name": "Teste implementado sem arquivo no disco",
        "domain": "Legal",
        "layer": "domain",
        "module": "auth",
        "type": "Feature",
        "priority": "P1",
        "status": "implemented_unverified",
        "automated": True,
        "test_file": "tests/Feature/NonExistentTest.php",
        "documentation": "quality/modules/auth.md",
        "last_verified_version": None,
        "last_verified_date": None
    }]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert any("Arquivo Inexistente" in e for e in errors)


def test_active_test_missing_verification_metadata(tmp_path):
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    test_file_path = tmp_path / "tests" / "Feature" / "ValidTest.php"
    test_file_path.parent.mkdir(parents=True, exist_ok=True)
    test_file_path.write_text("<?php // test", encoding="utf-8")

    tests_data = [{
        "id": "ACTIVE-NO-META-001",
        "name": "Teste ativo sem versão",
        "domain": "Legal",
        "layer": "domain",
        "module": "auth",
        "type": "Feature",
        "priority": "P1",
        "status": "active",
        "automated": True,
        "test_file": "tests/Feature/ValidTest.php",
        "documentation": "quality/modules/auth.md",
        "last_verified_version": None,
        "last_verified_date": None
    }]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert any("Metadados Ausentes" in e for e in errors)


def test_quarantined_p0_requires_owner_and_notes(tmp_path):
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    test_file_path = tmp_path / "tests" / "Feature" / "ValidTest.php"
    test_file_path.parent.mkdir(parents=True, exist_ok=True)
    test_file_path.write_text("<?php // test", encoding="utf-8")

    tests_data = [{
        "id": "QUARANTINED-P0-001",
        "name": "Teste P0 em quarentena sem owner e sem notes",
        "domain": "Legal",
        "layer": "domain",
        "module": "auth",
        "type": "Feature",
        "priority": "P0",
        "status": "quarantined",
        "automated": True,
        "test_file": "tests/Feature/ValidTest.php",
        "documentation": "quality/modules/auth.md",
        "last_verified_version": "v3.54.0",
        "last_verified_date": "2026-08-01",
        "owner": "unassigned",
        "notes": None
    }]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert any("Justificativa Obrigatória" in e for e in errors)
    assert any("Responsável P0" in e for e in errors)


def test_obsolete_component_rule13(tmp_path):
    doc_path = tmp_path / "quality" / "modules" / "auth.md"
    doc_path.parent.mkdir(parents=True, exist_ok=True)
    doc_path.write_text("# Doc Auth", encoding="utf-8")

    tests_data = [{
        "id": "STATIC-INTEGRITY-001",
        "name": "Teste de componente descontinuado",
        "domain": "Legal",
        "layer": "domain",
        "module": "auth",
        "type": "Feature",
        "priority": "P0",
        "status": "planned",
        "automated": False,
        "test_file": None,
        "documentation": "quality/modules/auth.md",
        "last_verified_version": None,
        "last_verified_date": None,
        "source_references": [
            {
                "path": "packages/SuiteZap/Whaticket/Something.php",
                "symbol": "foo",
                "purpose": "bar"
            }
        ]
    }]
    cat_file = create_temp_catalog(tmp_path, tests_data)
    errors, warnings = validate_catalog(cat_file, repo_root=tmp_path)
    assert any("Componente Obsoleto" in e for e in errors)
    assert any("Caminho Obsoleto" in e for e in errors)

