# ADR-002: Unificação da Stack E2E em Playwright Python e Desacoplamento de Produção

## Status
Aprovado

## Contexto
A plataforma exige validação end-to-end de fluxos completos no navegador (login, ficha de lead, disparo de IA, renderização Markdown via `marked.js` e sanitização DOMPurify no DOM). A imagem de produção `suitezap/lawfirm` deve permanecer limpa (`php:8.3-apache`), sem navegadores Chromium ou runtimes adicionais instalados.

## Decisão
1. **Stack Unificada em Python 3 / Pytest / Playwright**: A automação E2E adota exclusivamente Python 3, `pytest-playwright` e Page Objects modulares em `tests/e2e/pages/`.
2. **Contêiner Dedicado**: A execução E2E ocorre isoladamente através de `docker/testing/Dockerfile.playwright` baseado na imagem oficial imutável `mcr.microsoft.com/playwright/python:v1.45.0-jammy`.
3. **Produção Intacta**: A imagem de produção `suitezap/lawfirm` permanece sem qualquer dependência ou biblioteca de teste.

## Consequências
- Separação completa de responsabilidades de runtime.
- Facilidade de escrita de testes E2E com fixtures Pytest e geração automática de relatórios HTML, screenshots e traces.
