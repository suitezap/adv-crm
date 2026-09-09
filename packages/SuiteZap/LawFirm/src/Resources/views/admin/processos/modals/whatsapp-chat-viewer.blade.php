{{-- Chat Bubble Viewer: injected into the history modal body --}}
@if($messages->isEmpty())
    <div style="text-align:center; color:#6b7280; padding:3rem 1rem;">Nenhuma mensagem importada encontrada para este processo.</div>
@else
    <div style="display:flex; flex-direction:column; gap:0.75rem;">
        @foreach($messages as $msg)
            @php
                $isMe = $msg->is_from_me;
                $time = $msg->message_timestamp ? $msg->message_timestamp->format('d/m/Y H:i') : '';
                $name = $isMe ? 'Sistema (Advogado)' : ($msg->sender_name ?: $msg->remote_jid);
                
                $payload = $msg->payload ?? [];
                $msgContent = $payload['message'] ?? [];
                $hasMedia = isset($msgContent['imageMessage']) || isset($msgContent['videoMessage']) || isset($msgContent['audioMessage']) || isset($msgContent['documentMessage']);
            @endphp
            <div class="wa-bubble {{ $isMe ? 'left' : 'right' }}"
                 style="display:flex; width:100%; {{ $isMe ? 'justify-content:flex-start' : 'justify-content:flex-end' }};" id="wa-msg-{{ $msg->id }}">
                <div class="wa-inner"
                     style="max-width:75%; padding:10px 14px; border-radius:10px; font-size:13px; line-height:1.5;
                            {{ $isMe ? 'background:#fff; border:1px solid #e5e7eb;' : 'background:#d1fae5; border:1px solid #a7f3d0;' }}">
                    <div class="wa-name"
                         style="font-size:11px; font-weight:700; margin-bottom:4px;
                                {{ $isMe ? 'color:#374151;' : 'color:#065f46;' }}">
                        {{ $name }}
                    </div>
                    
                    @if($hasMedia)
                        <div class="wa-media-container" style="margin-bottom: 8px;">
                            @if($msg->anexo_id)
                                @php
                                    $proxyUrl = route('admin.processos.download_attachment', $msg->anexo_id);
                                @endphp
                                @if($msg->media_type === 'image')
                                    <a href="{{ $proxyUrl }}" target="_blank">
                                        <img src="{{ $proxyUrl }}" style="max-width: 100%; border-radius: 8px; cursor: pointer;" loading="lazy">
                                    </a>
                                @elseif($msg->media_type === 'audio')
                                    <audio controls style="max-width: 100%;">
                                        <source src="{{ $proxyUrl }}">
                                        Seu navegador não suporta áudio.
                                    </audio>
                                @elseif($msg->media_type === 'video')
                                    <video controls style="max-width: 100%; border-radius: 8px;">
                                        <source src="{{ $proxyUrl }}">
                                    </video>
                                @else
                                    <a href="{{ $proxyUrl }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(0,0,0,0.05); border-radius: 6px; text-decoration: none; color: inherit; font-weight: 600;">
                                        📄 Abrir Documento
                                    </a>
                                @endif
                                <div style="margin-top: 4px; font-size: 10px; color: #6b7280; display: flex; align-items: center; gap: 8px;">
                                    <span><span style="color: #10b981;">✓</span> Salvo no GED</span>
                                    <a href="{{ $proxyUrl }}" target="_blank" style="color: #6b7280; text-decoration: underline;">⬇ Baixar</a>
                                    <span style="color: #d1d5db;">|</span>
                                    <button type="button" onclick="window.lfDeleteWaMedia(this, {{ $msg->id }})" title="Apagar mídia do servidor" style="background:none; border:none; padding:0; color:#ef4444; cursor:pointer; font-size:10px; text-decoration:underline;">🗑️ Apagar</button>
                                </div>
                            @else
                                <button type="button" onclick="window.lfDownloadWaMedia(this, {{ $msg->id }})" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: #e5e7eb; border: 1px solid #d1d5db; border-radius: 6px; color: #374151; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <span>⬇️ Baixar Mídia</span>
                                </button>
                            @endif
                        </div>
                    @endif
                    
                    <div style="color:#111827; word-break:break-word; white-space:pre-wrap;">{{ $msg->message_text }}</div>
                    <div class="wa-time" style="font-size:10px; color:#9ca3af; margin-top:4px; text-align:right;">{{ $time }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif
