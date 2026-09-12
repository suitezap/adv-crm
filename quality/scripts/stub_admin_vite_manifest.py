#!/usr/bin/env python3
"""Gera stub de public/admin/build/manifest.json para testes de status HTTP.

O build Vite real nunca é commitado (higiene); sem manifest, qualquer teste
que dispare 401/403/404 quebra na página de erro (ViteManifestNotFoundException).
O conteúdo é irrelevante — testes asseveram status; render real é do E2E.

Uso: python quality/scripts/stub_admin_vite_manifest.py  (na raiz do repo)
"""

import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
VIEWS = os.path.join(ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Resources', 'views')
ASSETS = os.path.join(ROOT, 'public', 'admin', 'build', 'assets')
OUT = os.path.join(ROOT, 'public', 'admin', 'build', 'manifest.json')


def main() -> int:
    if not os.path.isdir(ASSETS):
        print('assets dir ausente: ' + ASSETS, file=sys.stderr)
        return 1
    built = sorted(os.listdir(ASSETS))
    logo = next((b for b in built
                 if 'logo-' in b and b.endswith('.svg')
                 and 'dark' not in b and 'mobile' not in b), built[0])
    keys = set()
    for root, _, files in os.walk(VIEWS):
        for f in files:
            if not f.endswith('.blade.php'):
                continue
            content = open(os.path.join(root, f), encoding='utf-8', errors='ignore').read()
            keys.update(re.findall(r"vite\(\)->asset\('([^']+)'\)", content))
    manifest = {}
    for key in sorted(keys):
        stem = os.path.splitext(os.path.basename(key))[0]
        match = next((b for b in built
                      if b.startswith(stem + '-') or b == os.path.basename(key)), None) or logo
        manifest[key] = {'file': 'assets/' + match, 'src': key}
        manifest['src/Resources/assets/' + key] = {'file': 'assets/' + match,
                                                   'src': 'src/Resources/assets/' + key}
    for entry in ['src/Resources/assets/css/app.css',
                  'src/Resources/assets/js/app.js',
                  'src/Resources/assets/js/chart.js']:
        manifest[entry] = {'file': 'assets/app.js', 'src': entry}
    open(OUT, 'w', encoding='utf-8').write(json.dumps(manifest, indent=2))
    print('stub keys: %d -> %s' % (len(manifest), OUT))
    return 0


if __name__ == '__main__':
    sys.exit(main())
