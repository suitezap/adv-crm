from playwright.sync_api import Page
from .base_page import BasePage


class ChatwootPage(BasePage):
    """Page Object para o menu SAC (Chatwoot) do LawFirm CRM.

    Cobre o caminho de acesso ao hub SAC, que tem três barreiras
    independentes verificadas por `test_chatwoot_sac_workflow`:

        1. ACL       — menu visível apenas com `lawfirm.assistants.chatwoot`
                       (Config/menu.php, key 'sac')
        2. Add-on    — assinatura do tenant precisa ter o módulo CHATWOOT
                       ativo; caso contrário, middleware retorna 403
                       (Atendimento/Http/Middleware/CheckChatwootModule.php)
        3. Isolamento — a assinatura é resolvida estritamente ao tenant
                       logado (MotherShipService::getCurrentSubscription)

    O teste interage apenas com a UI: **não** faz chamadas reais à API do
    Chatwoot. O iframe carrega a interface externa; verificamos que a view
    renderizou e que a URL foi injetada, sem navegar até lá.
    """

    MENU_SAC = "a[href*='assistants/chatwoot'], a:has-text('SAC')"
    VIEW_IFRAME = "iframe"

    def __init__(self, page: Page):
        super().__init__(page)
        self.sac_link = page.locator(self.MENU_SAC)
        self.iframe = page.locator(self.VIEW_IFRAME)

    def sac_menu_is_visible(self) -> bool:
        return self.sac_link.first.is_visible()

    def sac_menu_count(self) -> int:
        return self.sac_link.count()

    def open_sac(self) -> None:
        """Clique no menu SAC e espera a navegação."""
        self.sac_link.first.click()
        self.wait_for_load()

    def is_blocked_403(self) -> bool:
        """Detecta o bloqueio do middleware de add-on.

        O Laravel renderiza a página de erro 403 com o status HTTP; conferimos
        tanto o status quanto o texto, porque o renderizador varia entre
        environments de teste.
        """
        content = self.page.content()
        return "403" in content or "Forbidden" in content or "Acesso Negado" in content

    def iframe_src(self) -> str:
        """URL injetada no iframe do Chatwoot.

        Vazio significa que a view renderizou sem a credencial/URL — o que
        indica falha de configuração, não de teste.
        """
        return self.iframe.first.get_attribute("src") or ""

    def iframe_is_rendered(self) -> bool:
        return self.iframe.count() > 0
