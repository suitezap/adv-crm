from playwright.sync_api import Page
from .base_page import BasePage

class LoginPage(BasePage):
    def __init__(self, page: Page):
        super().__init__(page)
        self.email_input = page.locator("input[name='email']")
        self.password_input = page.locator("input[name='password']")
        self.submit_button = page.locator("button[type='submit']")

    def navigate(self):
        super().navigate("/admin/login")
        self.wait_for_load()

    def login(self, email: str, password: str):
        self.email_input.fill(email)
        self.password_input.fill(password)
        self.submit_button.click()
        self.wait_for_load()
