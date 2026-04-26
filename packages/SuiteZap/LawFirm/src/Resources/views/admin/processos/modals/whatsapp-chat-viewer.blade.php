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
            @endphp
            <div class="wa-bubble {{ $isMe ? 'left' : 'right' }}"
                 style="display:flex; width:100%; {{ $isMe ? 'justify-content:flex-start' : 'justify-content:flex-end' }};">
                <div class="wa-inner"
                     style="max-width:75%; padding:10px 14px; border-radius:10px; font-size:13px; line-height:1.5;
                            {{ $isMe ? 'background:#fff; border:1px solid #e5e7eb;' : 'background:#d1fae5; border:1px solid #a7f3d0;' }}">
                    <div class="wa-name"
                         style="font-size:11px; font-weight:700; margin-bottom:4px;
                                {{ $isMe ? 'color:#374151;' : 'color:#065f46;' }}">
                        {{ $name }}
                    </div>
                    <div style="color:#111827; word-break:break-word; white-space:pre-wrap;">{{ $msg->message_text }}</div>
                    <div class="wa-time" style="font-size:10px; color:#9ca3af; margin-top:4px; text-align:right;">{{ $time }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif
