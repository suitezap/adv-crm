{{-- ===================================================================
     WHATSAPP HISTORY MODAL - Self-contained, SPA-safe portal dialog
     Uses inline styles + unique IDs to avoid Vue/Krayin SPA interceptors
     =================================================================== --}}
<div id="lf-wa-hist-portal"
     style="display:none; position:fixed; inset:0; z-index:99999; overflow:hidden;"
     role="dialog" aria-modal="true" aria-labelledby="lf-wa-hist-title">

    {{-- Backdrop --}}
    <div onclick="window.lfCloseWaHistory()"
         style="position:absolute; inset:0; background:rgba(0,0,0,0.55);"></div>

    {{-- Dialog --}}
    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; padding:1rem; pointer-events:none;">
        <div style="pointer-events:auto; background:#fff; border-radius:0.75rem; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); display:flex; flex-direction:column; width:100%; max-width:72rem; max-height:90vh; overflow:hidden;">

            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1.25rem; border-bottom:1px solid #e5e7eb; background:#f9fafb; flex-shrink:0;">
                <span id="lf-wa-hist-title" style="font-size:1rem; font-weight:600; color:#111827;">💬 Histórico do WhatsApp — Processo #{{ $processo->id }}</span>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <a id="lf-wa-export-btn" href="{{ route('admin.lawfirm.whatsapp.import.export_zip', $processo->id) }}" target="_blank"
                            style="padding:0.375rem 0.875rem; font-size:0.8rem; font-weight:600; border-radius:0.375rem; border:1px solid #7c3aed; background:#f5f3ff; color:#7c3aed; cursor:pointer; text-decoration:none;">
                        📦 Baixar PDF + Mídias (.zip)
                    </a>
                    <button onclick="window.lfPrintWaHistory()"
                            style="padding:0.375rem 0.875rem; font-size:0.8rem; font-weight:600; border-radius:0.375rem; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer;">
                        📄 Imprimir / PDF
                    </button>
                    <button onclick="window.lfCloseWaHistory()"
                            style="padding:0.375rem 0.875rem; font-size:0.8rem; font-weight:600; border-radius:0.375rem; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer;">
                        ✕ Fechar
                    </button>
                </div>
            </div>

            {{-- Import Tabs Bar --}}
            <div id="lf-wa-hist-tabs"
                 style="display:flex; gap:0.5rem; padding:0.5rem 1.25rem; background:#f3f4f6; border-bottom:1px solid #e5e7eb; flex-shrink:0; overflow-x:auto; flex-wrap:nowrap; align-items:center;">
                <button onclick="window.lfLoadWaImport(null)"
                        id="lf-wa-tab-all"
                        data-import-id="all"
                        style="padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:9999px; border:1px solid #7c3aed; background:#7c3aed; color:#fff; cursor:pointer; white-space:nowrap;">
                    📋 Todas
                </button>
                {{-- Dynamic tabs will be injected here by JS --}}
            </div>

            {{-- Body (scrollable) --}}
            <div id="lf-wa-hist-body"
                 style="flex:1; overflow-y:auto; padding:1.25rem; background:#f3f4f6;">
                <div style="text-align:center; color:#6b7280; margin:3rem 0;">Carregando histórico...</div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var _processoId  = '{{ $processo->id }}';
    var _msgRoute    = "{{ route('admin.lawfirm.whatsapp.messages', 'REPLACE_ID') }}".replace('REPLACE_ID', _processoId);
    var _importsRoute= "{{ route('admin.lawfirm.whatsapp.imports', 'REPLACE_ID') }}".replace('REPLACE_ID', _processoId);
    var _deleteRoute = "{{ route('admin.lawfirm.whatsapp.import.delete', ['REPLACE_PID', 'REPLACE_IID']) }}";
    var _csrfToken   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var _printContent= '';
    var _activeImportId = null;

    function getPortal()  { return document.getElementById('lf-wa-hist-portal'); }
    function getBody()    { return document.getElementById('lf-wa-hist-body'); }
    function getTabs()    { return document.getElementById('lf-wa-hist-tabs'); }

    function ensurePortal() {
        var p = getPortal();
        if (p && p.parentNode !== document.body) {
            document.body.appendChild(p);
        }
        return p;
    }

    function setActiveTab(importId) {
        _activeImportId = importId;
        var tabs = getTabs();
        if (!tabs) return;
        var items = tabs.querySelectorAll('[data-import-id]');
        items.forEach(function(el) {
            var btnId = el.getAttribute('data-import-id');
            var isActive = (importId === null && btnId === 'all') || (btnId == importId);
            var btn = el.querySelector('.lf-wa-tab-btn') || el;
            if (isActive) {
                btn.style.background = '#7c3aed';
                btn.style.color = '#fff';
                btn.style.borderColor = '#7c3aed';
            } else {
                btn.style.background = '#fff';
                btn.style.color = '#374151';
                btn.style.borderColor = '#d1d5db';
            }
        });
    }

    window.lfDeleteWaImport = async function(importId, contactName) {
        if (!confirm('Tem certeza que deseja remover a importação de "' + contactName + '"?\n\nTodas as mensagens desta importação serão excluídas permanentemente.')) {
            return;
        }

        var url = _deleteRoute.replace('REPLACE_PID', _processoId).replace('REPLACE_IID', importId);
        try {
            var resp = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': _csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await resp.json();
            if (data.success) {
                // Reload tabs and show all messages
                await loadImportTabs();
                window.lfLoadWaImport(null);
            } else {
                alert(data.error || 'Erro ao remover importação.');
            }
        } catch(e) {
            alert('Erro de rede: ' + e.message);
        }
    };

    var _deleteMediaRoute = "{{ route('admin.lawfirm.whatsapp.messages.delete_media', 'REPLACE_ID') }}";

    window.lfDeleteWaMedia = async function(btn, messageId) {
        if (!confirm('Deseja excluir este arquivo do servidor (S3)?\n\nO arquivo será apagado, mas a mensagem continuará e você poderá baixá-lo novamente se ainda estiver disponível no WhatsApp.')) return;
        var btnText = btn.innerHTML;
        btn.innerHTML = '⏳';
        btn.disabled = true;

        var url = _deleteMediaRoute.replace('REPLACE_ID', messageId);
        try {
            var resp = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': _csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await resp.json();
            if (data.success) {
                // Reload active tab to show the download button again
                window.lfLoadWaImport(_activeImportId);
            } else {
                alert(data.error || 'Erro ao excluir mídia.');
                btn.innerHTML = btnText;
                btn.disabled = false;
            }
        } catch(e) {
            alert('Erro de rede: ' + e.message);
            btn.innerHTML = btnText;
            btn.disabled = false;
        }
    };

    async function loadImportTabs() {
        try {
            // Evitar cache do navegador nas requisições GET
            var fetchUrl = _importsRoute + (_importsRoute.indexOf('?') >= 0 ? '&' : '?') + 't=' + new Date().getTime();
            var resp = await fetch(fetchUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
            });
            var data = await resp.json();

            var tabs = getTabs();
            var allBtn = document.getElementById('lf-wa-tab-all');

            // Always reset the tab bar — ensures deleted tabs disappear even when list is empty
            tabs.innerHTML = '';
            tabs.appendChild(allBtn);

            if (data.success && data.imports && data.imports.length > 0) {
                data.imports.forEach(function(imp) {
                    var statusIcon = imp.status === 'completed' ? '✅' : (imp.status === 'processing' ? '⏳' : '❌');

                    // Wrapper div for tab + delete button
                    var wrapper = document.createElement('div');
                    wrapper.setAttribute('data-import-id', imp.id);
                    wrapper.style.cssText = 'display:inline-flex; align-items:center; gap:2px;';

                    // Tab button
                    var btn = document.createElement('button');
                    btn.className = 'lf-wa-tab-btn';
                    btn.style.cssText = 'padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:500; border-radius:9999px 0 0 9999px; border:1px solid #d1d5db; border-right:none; background:#fff; color:#374151; cursor:pointer; white-space:nowrap;';
                    btn.innerHTML = statusIcon + ' ' + (imp.contact_name || 'Contato') + ' <span style="font-size:0.65rem;color:#6b7280;">(' + imp.period + ' · ' + imp.message_count + ' msgs)</span>';
                    btn.onclick = function() { window.lfLoadWaImport(imp.id); };

                    // Delete button
                    var delBtn = document.createElement('button');
                    delBtn.style.cssText = 'padding:0.375rem 0.5rem; font-size:0.7rem; border-radius:0 9999px 9999px 0; border:1px solid #fca5a5; background:#fef2f2; color:#dc2626; cursor:pointer; white-space:nowrap;';
                    delBtn.title = 'Remover esta importação';
                    delBtn.innerHTML = '🗑️';
                    delBtn.onclick = function(e) { e.stopPropagation(); window.lfDeleteWaImport(imp.id, imp.contact_name || 'Contato'); };

                    wrapper.appendChild(btn);
                    wrapper.appendChild(delBtn);
                    tabs.appendChild(wrapper);
                });
            }
        } catch(e) {
            console.error('Failed to load import tabs', e);
        }
    }

    window.lfLoadWaImport = async function(importId) {
        setActiveTab(importId);
        var bd = getBody();
        bd.innerHTML = '<div style="text-align:center;padding:3rem;color:#3b82f6;">⏳ Buscando mensagens...</div>';

        var url = _msgRoute;
        var exportUrl = "{{ route('admin.lawfirm.whatsapp.import.export_zip', $processo->id) }}";
        if (importId) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'import_id=' + importId;
            exportUrl += '?import_id=' + importId;
        }
        
        var exportBtn = document.getElementById('lf-wa-export-btn');
        if (exportBtn) exportBtn.href = exportUrl;
        url += (url.indexOf('?') >= 0 ? '&' : '?') + 't=' + new Date().getTime();

        try {
            var resp = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
            });
            var data = await resp.json();
            var bd2 = getBody();
            if (data && data.success) {
                _printContent = data.html;
                bd2.innerHTML = data.html;
            } else {
                bd2.innerHTML = '<div style="text-align:center;color:#ef4444;padding:2rem;">Erro ao carregar mensagens.</div>';
            }
        } catch(e) {
            var bd3 = getBody();
            bd3.innerHTML = '<div style="text-align:center;color:#ef4444;padding:2rem;">Erro de rede: ' + e.message + '</div>';
        }
    };

    window.lfOpenWaHistory = async function() {
        var portal = ensurePortal();
        portal.style.display = 'block';
        // Load tabs first, then load all messages
        await loadImportTabs();
        window.lfLoadWaImport(null);
    };

    window.lfCloseWaHistory = function() {
        var portal = getPortal();
        if (portal) { portal.style.display = 'none'; }
    };

    window.lfDownloadWaMedia = async function(btn, messageId) {
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<span>⏳ Baixando...</span>';
        btn.disabled = true;
        
        var url = "{{ route('admin.lawfirm.whatsapp.messages.download_media', 'REPLACE_MID') }}".replace('REPLACE_MID', messageId);
        try {
            var resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': _csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var data = await resp.json();
            if (data.success) {
                var container = btn.parentNode;
                var proxyUrl = data.download_url;
                var mediaHtml = '';
                if (data.media_type === 'image') {
                    mediaHtml = '<a href="' + proxyUrl + '" target="_blank"><img src="' + proxyUrl + '" style="max-width: 100%; border-radius: 8px; cursor: pointer;" loading="lazy"></a>';
                } else if (data.media_type === 'audio') {
                    mediaHtml = '<audio controls style="max-width: 100%;"><source src="' + proxyUrl + '">Áudio</audio>';
                } else if (data.media_type === 'video') {
                    mediaHtml = '<video controls style="max-width: 100%; border-radius: 8px;"><source src="' + proxyUrl + '"></video>';
                } else {
                    mediaHtml = '<a href="' + proxyUrl + '" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(0,0,0,0.05); border-radius: 6px; text-decoration: none; color: inherit; font-weight: 600;">📄 Abrir Documento</a>';
                }
                // Badge GED
                mediaHtml += '<div style="margin-top: 4px; font-size: 10px; color: #6b7280; display: flex; align-items: center; gap: 3px;"><span style="color: #10b981;">✓</span> Salvo no GED <a href="' + proxyUrl + '" target="_blank" style="color: #6b7280; text-decoration: underline; margin-left: 4px;">⬇ Baixar</a></div>';
                container.innerHTML = mediaHtml;
            } else {
                alert(data.error || 'Erro ao baixar mídia.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        } catch(e) {
            alert('Erro de rede: ' + e.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    };

    window.lfPrintWaHistory = function() {
        var w = window.open('', '_blank');
        w.document.write('<!DOCTYPE html><html><head><title>Histórico WhatsApp - Processo #' + _processoId + '</title>' +
            '<style>' +
            'body{font-family:sans-serif;padding:20px;color:#333;background:#f3f4f6;}' +
            '.wa-bubble{display:flex;width:100%;margin-bottom:12px;}' +
            '.wa-bubble.left{justify-content:flex-start;}' +
            '.wa-bubble.right{justify-content:flex-end;}' +
            '.wa-inner{max-width:75%;padding:10px 14px;border-radius:10px;font-size:13px;line-height:1.5;}' +
            '.wa-bubble.left .wa-inner{background:#fff;border:1px solid #e5e7eb;}' +
            '.wa-bubble.right .wa-inner{background:#d1fae5;border:1px solid #a7f3d0;}' +
            '.wa-name{font-size:11px;font-weight:700;margin-bottom:4px;}' +
            '.wa-time{font-size:10px;color:#9ca3af;margin-top:4px;text-align:right;}' +
            '</style></head><body>' +
            '<h2>Processo #' + _processoId + ' — Histórico WhatsApp</h2>' +
            '<div>' + _printContent + '</div>' +
            '</body></html>');
        w.document.close();
        setTimeout(function(){ w.print(); }, 600);
    };
})();
</script>
@endpush
