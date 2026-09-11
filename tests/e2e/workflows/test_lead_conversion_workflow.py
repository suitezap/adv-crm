import pytest
from playwright.sync_api import BrowserContext
from pages.login_page import LoginPage
from pages.lead_page import LeadPage

def test_tenant_a_basic_reachability(tenant_a_context: BrowserContext):
    page = tenant_a_context.new_page()
    login_page = LoginPage(page)
    login_page.navigate()
    assert page.title() != ""
    # We will expand this test later

def test_tenant_b_basic_reachability(tenant_b_context: BrowserContext):
    page = tenant_b_context.new_page()
    login_page = LoginPage(page)
    login_page.navigate()
    assert page.title() != ""

