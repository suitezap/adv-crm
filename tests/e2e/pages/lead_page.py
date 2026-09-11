from playwright.sync_api import Page
from .base_page import BasePage

class LeadPage(BasePage):
    def __init__(self, page: Page):
        super().__init__(page)
        self.ai_pre_triagem_button = page.locator("button:has-text('Pré-Triagem de Lead')")
        self.ai_checklist_button = page.locator("button:has-text('Pré-Triagem de Checklist')")
        self.ai_script_vendas_button = page.locator("button:has-text('Script de Vendas')")
        self.ai_proposta_button = page.locator("button:has-text('Gerador de Proposta')")

    def navigate_to_leads(self):
        super().navigate("/admin/leads")
        self.wait_for_load()

    def open_lead(self, lead_id: int):
        super().navigate(f"/admin/leads/view/{lead_id}")
        self.wait_for_load()

    def run_ai_assistant(self, assistant_type: str):
        if assistant_type == "pre-triagem":
            self.ai_pre_triagem_button.click()
        elif assistant_type == "checklist":
            self.ai_checklist_button.click()
        elif assistant_type == "script":
            self.ai_script_vendas_button.click()
        elif assistant_type == "proposta":
            self.ai_proposta_button.click()
        
        # We need to wait for the modal or processing indicator to finish
        # This will need to be adjusted based on the actual UI
        self.page.wait_for_selector(".ai-response-container", state="visible", timeout=10000)
