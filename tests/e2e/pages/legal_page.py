from playwright.sync_api import Page
from .base_page import BasePage

class LegalPage(BasePage):
    def __init__(self, page: Page):
        super().__init__(page)
        self.casos_menu = page.locator("a:has-text('Casos')")
        self.processos_menu = page.locator("a:has-text('Processos')")

    def navigate_to_casos(self):
        super().navigate("/admin/lawfirm/casos")
        self.wait_for_load()

    def navigate_to_processos(self):
        super().navigate("/admin/lawfirm/processos")
        self.wait_for_load()
