#!/usr/bin/env python3
"""
generate_coverage_matrix.py
Gerador da Matriz de Cobertura e Rastreabilidade (quality/COVERAGE_MATRIX.md)
"""

import sys
import yaml
from pathlib import Path
from datetime import datetime

# Configuração de encoding para Windows console
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except Exception:
        pass

SCRIPT_DIR = Path(__file__).resolve().parent
QUALITY_DIR = SCRIPT_DIR.parent
CATALOG_PATH = QUALITY_DIR / "TEST_CATALOG.yaml"
OUTPUT_MATRIX_PATH = QUALITY_DIR / "COVERAGE_MATRIX.md"


def generate_matrix(catalog_path=CATALOG_PATH, output_path=OUTPUT_MATRIX_PATH):
    if not catalog_path.exists():
        print(f"Erro: Catálogo não encontrado em {catalog_path}")
        sys.exit(1)

    with open(catalog_path, "r", encoding="utf-8") as f:
        data = yaml.safe_load(f)

    tests = data.get("tests", [])

    # Agrupamentos estatísticos
    total_tests = len(tests)
    status_counts = {"active": 0, "implemented_unverified": 0, "planned": 0, "quarantined": 0, "disabled": 0, "retired": 0}
    priority_counts = {"P0": 0, "P1": 0, "P2": 0}
    domain_counts = {}

    for t in tests:
        st = t.get("status", "planned")
        status_counts[st] = status_counts.get(st, 0) + 1

        prio = t.get("priority", "P1")
        priority_counts[prio] = priority_counts.get(prio, 0) + 1

        dom = t.get("domain") or "Plataforma / Governança"
        domain_counts[dom] = domain_counts.get(dom, 0) + 1

    # Cálculo de percentual de cobertura ativa certificada
    active_count = status_counts.get("active", 0)
    planned_count = status_counts.get("planned", 0) + status_counts.get("implemented_unverified", 0)
    certification_rate = (active_count / (active_count + planned_count) * 100) if (active_count + planned_count) > 0 else 0.0

    lines = []
    lines.append("# 📊 Matriz de Cobertura e Rastreabilidade de Testes (COVERAGE_MATRIX.md)")
    lines.append("")
    lines.append(f"> **Gerado automaticamente por `quality/scripts/generate_coverage_matrix.py`**  ")
    lines.append(f"> **Última geração:** {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}  ")
    lines.append(f"> **Fonte da verdade:** `quality/TEST_CATALOG.yaml`")
    lines.append("")
    lines.append("---")
    lines.append("")
    lines.append("## 1. Sumário Executivo de Cobertura")
    lines.append("")
    lines.append("| Métrica | Quantidade | Percentual |")
    lines.append("|:---|:---:|:---:|")
    lines.append(f"| **Total de Testes Cadastrados** | **{total_tests}** | 100% |")
    lines.append(f"| 🟢 Ativos e Certificados (`active`) | {active_count} | {(active_count/total_tests*100):.1f}% |")
    lines.append(f"| 🟡 Implementados Não-Verificados (`implemented_unverified`) | {status_counts.get('implemented_unverified', 0)} | {(status_counts.get('implemented_unverified', 0)/total_tests*100):.1f}% |")
    lines.append(f"| ⚪ Planejados (`planned`) | {status_counts.get('planned', 0)} | {(status_counts.get('planned', 0)/total_tests*100):.1f}% |")
    lines.append(f"| 🟠 Em Quarentena (`quarantined`) | {status_counts.get('quarantined', 0)} | {(status_counts.get('quarantined', 0)/total_tests*100):.1f}% |")
    lines.append(f"| 🔴 Desativados (`disabled`) | {status_counts.get('disabled', 0)} | {(status_counts.get('disabled', 0)/total_tests*100):.1f}% |")
    lines.append(f"| 📦 Aposentados / Histórico (`retired`) | {status_counts.get('retired', 0)} | {(status_counts.get('retired', 0)/total_tests*100):.1f}% |")
    lines.append("")
    lines.append("---")
    lines.append("")
    lines.append("## 2. Distribuição por Domínio e Prioridade")
    lines.append("")
    lines.append("### Por Domínio")
    lines.append("| Domínio | Quantidade de Testes |")
    lines.append("|:---|:---:|")
    for dom, count in sorted(domain_counts.items()):
        lines.append(f"| **{dom}** | {count} |")
    lines.append("")
    lines.append("### Por Prioridade")
    lines.append("| Prioridade | Quantidade de Testes |")
    lines.append("|:---|:---:|")
    for p in ["P0", "P1", "P2"]:
        lines.append(f"| **{p}** | {priority_counts.get(p, 0)} |")
    lines.append("")
    lines.append("---")
    lines.append("")
    lines.append("## 3. Catálogo Completo de Rastreabilidade")
    lines.append("")
    lines.append("| ID | Nome / Intenção | Domínio | Camada | Tipo | Prio | Status | Arquivo de Teste | Documentação |")
    lines.append("|:---|:---|:---:|:---:|:---:|:---:|:---:|:---|:---|")

    for t in tests:
        tid = t.get("id", "")
        name = t.get("name", "").replace("|", "-")
        domain = t.get("domain") or "-"
        layer = t.get("layer", "-")
        ttype = t.get("type", "-")
        prio = t.get("priority", "-")
        status = t.get("status", "-")
        test_file = f"`{t.get('test_file')}`" if t.get("test_file") else "-"
        doc = f"`{t.get('documentation')}`" if t.get("documentation") else "-"

        # Status badge visual
        status_badge = {
            "active": "🟢 active",
            "implemented_unverified": "🟡 implemented_unverified",
            "planned": "⚪ planned",
            "quarantined": "🟠 quarantined",
            "disabled": "🔴 disabled",
            "retired": "📦 retired"
        }.get(status, status)

        lines.append(f"| **{tid}** | {name} | {domain} | {layer} | {ttype} | {prio} | {status_badge} | {test_file} | {doc} |")

    lines.append("")

    with open(output_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))

    print(f"✅ Matriz de cobertura gerada com sucesso em: {output_path}")


if __name__ == "__main__":
    generate_matrix()
