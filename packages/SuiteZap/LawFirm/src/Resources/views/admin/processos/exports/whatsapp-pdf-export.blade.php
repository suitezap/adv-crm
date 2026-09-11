<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Histórico de WhatsApp</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .message {
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            max-width: 80%;
            page-break-inside: avoid;
        }
        .message.left {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            margin-right: auto;
        }
        .message.right {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            margin-left: auto;
            text-align: right;
        }
        .header {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .left .header {
            color: #4b5563;
        }
        .right .header {
            color: #065f46;
        }
        .time {
            font-size: 9px;
            color: #9ca3af;
        }
        .content {
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .media-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 8px;
            font-size: 10px;
            background-color: #e5e7eb;
            color: #374151;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body>

    <h2>Histórico de Conversa - Processo #{{ $processo->id }}</h2>

    @if($messages->isEmpty())
        <p style="text-align: center; color: #6b7280;">Nenhuma mensagem importada encontrada para este processo.</p>
    @else
        @foreach($messages as $msg)
            @php
                $isMe = $msg->is_from_me;
                $time = $msg->message_timestamp ? $msg->message_timestamp->format('d/m/Y H:i') : '';
                $name = $isMe ? 'Sistema (Advogado)' : ($msg->sender_name ?: $msg->remote_jid);
                
                $payload = $msg->payload ?? [];
                $msgContent = $payload['message'] ?? [];
                
                $text = '';
                if (isset($msgContent['conversation'])) {
                    $text = $msgContent['conversation'];
                } elseif (isset($msgContent['extendedTextMessage']['text'])) {
                    $text = $msgContent['extendedTextMessage']['text'];
                } elseif (isset($msgContent['imageMessage']['caption'])) {
                    $text = $msgContent['imageMessage']['caption'];
                } elseif (isset($msgContent['videoMessage']['caption'])) {
                    $text = $msgContent['videoMessage']['caption'];
                } elseif (isset($msgContent['documentMessage']['caption'])) {
                    $text = $msgContent['documentMessage']['caption'];
                }
            @endphp
            
            <div class="message {{ $isMe ? 'left' : 'right' }}">
                <div class="header">
                    {{ $name }} <span class="time">({{ $time }})</span>
                </div>
                
                @if($msg->anexo_id)
                    @php
                        $anexo = $msg->anexo;
                        $filename = $anexo ? $anexo->nome_original : 'Arquivo Mídia';
                    @endphp
                    <div class="media-badge">
                        📎 Mídia Anexada: {{ $filename }} (Incluso no ZIP)
                    </div>
                @elseif($msg->media_type && !$msg->anexo_id)
                    <div class="media-badge">
                        ⚠️ Mídia não baixada do WhatsApp.
                    </div>
                @endif
                
                @if($text)
                    <div class="content" style="{{ ($msg->anexo_id || $msg->media_type) ? 'margin-top: 8px;' : '' }}">
                        {{ $text }}
                    </div>
                @endif
            </div>
        @endforeach
    @endif

</body>
</html>
