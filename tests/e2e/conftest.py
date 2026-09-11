import os
import pytest
from playwright.sync_api import Browser, BrowserContext


@pytest.fixture(scope="session")
def tenant_a_url():
    return os.getenv("TENANT_A_URL", "http://app-tenant-a:8001")


@pytest.fixture(scope="session")
def tenant_b_url():
    return os.getenv("TENANT_B_URL", "http://app-tenant-b:8002")


@pytest.fixture(scope="session")
def browser_type_launch_args(browser_type_launch_args):
    """Override Chromium launch args for Docker compatibility.

    --no-sandbox: required when running Chromium as root inside Docker.
    --disable-dev-shm-usage: prevents /dev/shm size issues in containers.
    DNS resolution is handled by /etc/hosts injection in the Dockerfile CMD.
    """
    return {
        **browser_type_launch_args,
        "args": [
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--disable-gpu",
        ],
    }


@pytest.fixture
def tenant_a_context(browser: Browser, tenant_a_url: str):
    context = browser.new_context(base_url=tenant_a_url)
    yield context
    context.close()


@pytest.fixture
def tenant_b_context(browser: Browser, tenant_b_url: str):
    context = browser.new_context(base_url=tenant_b_url)
    yield context
    context.close()


