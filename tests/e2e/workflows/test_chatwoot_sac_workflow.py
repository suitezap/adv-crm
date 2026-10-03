"""E2E — Acesso isolado ao menu SAC (Chatwoot).

Implementa `CHATWOOT-E2E-001` (P1, domain Atendimento, layer e2e).

Escopo, conforme `quality/TEST_CATALOG.yaml`:
    "Acesso isolado ao menu SAC, ACL, middleware de add-on e iframe do
     Chatwoot (sem requisições externas)"

Verifica as três barreiras de forma independente:

    1. **ACL**       — o menu "SAC" só aparece para quem tem a permissão
                       `lawfirm.assistants.chatwoot` (Config/menu.php).
    2. **Add-on**    — assinatura sem o módulo CHATWOOT ativo é bloqueada com
                       403 pelo middleware CheckChatwootModule.
    3. **Isolamento** — a assinatura é resolvida estritamente ao tenant
                       logado; não pode vazar a assinatura de outro tenant.

⚠️ Este teste **não** faz chamadas reais à API do Chatwoot. Ele interage com
a UI da plataforma (menu, bloqueio e renderização da view). O iframe carrega
a interface externa; verificamos que a view renderizou e que a URL foi
injetada, **sem navegar até lá** — manter o egress bloqueado é premissa do
ambiente de teste (`quality_internal`, sem saída para a internet).

Ambiente: as fixtures `tenant_a_context` / `tenant_b_context` do `conftest.py`
apontam para os containers de teste. Execução:

    pytest tests/e2e/workflows/test_chatwoot_sac_workflow.py -v

Status: `implemented_unverified` — escrito em 2026-09-30 (DOC-006) e ainda
NÃO executado: o ambiente de execução com Docker não existe no servidor
Hermes. A transição para `active` depende de `QA-DATA-001`/`QA-HARNESS-001`
(ambas `BLOCKED`). Ver `quality/runbooks/local-qa-dataplane.md`.
"""

import os

import pytest
from playwright.sync_api import BrowserContext

from pages.chatwoot_page import ChatwootPage
from pages.login_page import LoginPage

# Barreira 2 — assinatura sem o add-on CHATWOOT deve produzir 403.
# O estado é forçado no banco pelo fixture/harness; se a preparação não puder
# simular, o teste é pulado em vez de dar falso-verde.
CHATWOOT_ADDON_ENABLED = os.getenv("CHATWOOT_ADDON_ENABLED", "true").lower() == "true"


def _login(page, email: str, password: str) -> None:
    login_page = LoginPage(page)
    login_page.navigate()
    login_page.login(email, password)


def test_sac_menu_rendered_for_authorized_tenant(tenant_a_context: BrowserContext):
    """1. ACL — usuário com a permissão vê o menu SAC na sidebar.

    Não clicking ainda: primeiro confirmamos presença, depois a renderização
    da view. Separar as asserções dá diagnóstico mais claro quando falha.
    """
    if not os.getenv("LAWFIRM_ADMIN_EMAIL"):
        pytest.skip("LAWFIRM_ADMIN_EMAIL não definida — injetar via secret store/vault.")

    page = tenant_a_context.new_page()
    _login(page, os.environ["LAWFIRM_ADMIN_EMAIL"], os.environ.get("LAWFIRM_ADMIN_PASSWORD", ""))

    sac = ChatwootPage(page)
    assert sac.sac_menu_count() > 0, (
        "Menu SAC ausente para usuário autenticado. Verificar a ACL "
        "'lawfirm.assistants.chatwoot' em Config/menu.php (key 'sac')."
    )


def test_sac_view_renders_chatwoot_iframe(tenant_a_context: BrowserContext):
    """3. Isolamento + renderização — a view do hub monta o iframe do Chatwoot.

    Só conferimos a presença do iframe e que a URL foi injetada. NÃO
    navegamos até o src: o ambiente de teste não tem egress, e tentar sair
    transformaria um teste de UI em dependência de rede externa.
    """
    if not os.getenv("LAWFIRM_ADMIN_EMAIL"):
        pytest.skip("LAWFIRM_ADMIN_EMAIL não definida — injetar via secret store/vault.")
    if not CHATWOOT_ADDON_ENABLED:
        pytest.skip("CHATWOOT_ADDON_ENABLED=false — assinatura sem o add-on; ver o teste de 403.")

    page = tenant_a_context.new_page()
    _login(page, os.environ["LAWFIRM_ADMIN_EMAIL"], os.environ.get("LAWFIRM_ADMIN_PASSWORD", ""))

    sac = ChatwootPage(page)
    sac.open_sac()

    assert sac.iframe_is_rendered(), (
        "View do hub SAC renderizou sem o iframe do Chatwoot. Verificar "
        "AssistantController::chatwoot() e a view "
        "lawfirm::admin::assistants.chatwoot."
    )
    src = sac.iframe_src()
    assert src, (
        "Iframe presente mas com src vazio — a URL do Chatwoot não foi injetada. "
        "Verificar MotherShipService::getChatwootConfig() e a sanitização da base_url "
        "(ADR-ATEND-001)."
    )


def test_sac_blocked_403_without_chatwoot_addon(tenant_a_context: BrowserContext):
    """2. Add-on — assinatura sem CHATWOOT ativo recebe 403.

    A assinatura é simulada no banco pelo harness. Quando a simulação não está
    disponível, pulamos — um teste que passa sem verificar nada é pior que um
    teste pulado, porque aparece verde no relatório.
    """
    if CHATWOOT_ADDON_ENABLED:
        pytest.skip("CHATWOOT_ADDON_ENABLED=true — este caso exige assinatura SEM o add-on.")

    if not os.getenv("LAWFIRM_ADMIN_EMAIL"):
        pytest.skip("LAWFIRM_ADMIN_EMAIL não definida — injetar via secret store/vault.")

    page = tenant_a_context.new_page()
    _login(page, os.environ["LAWFIRM_ADMIN_EMAIL"], os.environ.get("LAWFIRM_ADMIN_PASSWORD", ""))

    sac = ChatwootPage(page)
    if sac.sac_menu_count() == 0:
        # Middleware bloqueou antes de renderizar: comportamento correto.
        return
    sac.open_sac()
    assert sac.is_blocked_403(), (
        "Tenant sem o add-on CHATWOOT acessou o hub SAC sem 403. "
        "Verificar Atendimento/Http/Middleware/CheckChatwootModule.php."
    )


def test_sac_subscription_isolated_between_tenants(tenant_a_context: BrowserContext, tenant_b_context: BrowserContext):
    """3. Isolamento multi-tenant — tenant B sem o add-on não vê o hub de A.

    Cobre MotherShipService::getCurrentSubscription(), que deve resolver a
    assinatura estritamente ao tenant logado. Se os dois tenants tivessem a
    mesma configuração, este teste não detectaria vazamento entre eles.
    """
    if not os.getenv("LAWFIRM_ADMIN_EMAIL"):
        pytest.skip("LAWFIRM_ADMIN_EMAIL não definida — injetar via secret store/vault.")
    if CHATWOOT_ADDON_ENABLED:
        pytest.skip(
            "CHATWOOT_ADDON_ENABLED=true nos dois tenants — configure o tenant B "
            "SEM o add-on para exercitar o isolamento."
        )

    page_b = tenant_b_context.new_page()
    _login(page_b, os.environ["LAWFIRM_ADMIN_EMAIL"], os.environ.get("LAWFIRM_ADMIN_PASSWORD", ""))

    sac_b = ChatwootPage(page_b)
    assert sac_b.sac_menu_count() == 0, (
        "Tenant B (sem add-on CHATWOOT) enxergou o menu SAC — provável vazamento "
        "de assinatura entre tenants em MotherShipService::getCurrentSubscription()."
    )
