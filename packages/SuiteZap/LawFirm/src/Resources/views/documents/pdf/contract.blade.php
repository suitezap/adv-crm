<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Contrato de Honorários</title>
    <style>
        @page {
            margin: 1.5cm 2.5cm;
        }

        body {
            font-family: sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }

        h1 {
            text-align: center;
            /* text-transform: uppercase; REMOVED */
            font-size: 14pt;
            margin-bottom: 1.5cm;
            font-weight: bold;
        }

        p {
            text-align: justify;
            text-indent: 0;
            margin-bottom: 15px;
        }

        .label {
            font-weight: bold;
            /* text-transform: uppercase; REMOVED */
        }

        .signature-block {
            margin-top: 2.5cm;
            text-align: center;
            page-break-inside: avoid;
        }

        .line {
            border-top: 1px solid #000;
            width: 60%;
            margin: 0 auto 5px auto;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
    </style>
</head>

<body>

    @php
        // Recupera dados de config
        $companyName = core()->getConfigData('lawfirm.settings.general.company_name') ?? 'Nome do Escritório Não Configurado';

        // Logo Path (Lógica Absoluta)
        $logoRelPath = core()->getConfigData('lawfirm.settings.general.logo');

        // Fallback
        if (!$logoRelPath) {
            $logoRelPath = core()->getConfigData('general.design.admin_logo.logo_image');
        }

        $logoAbsPath = $logoRelPath ? public_path('storage/' . $logoRelPath) : null;
    @endphp

    {{-- HEADER EM TABELA --}}
    <table style="width: 100%; border-bottom: 1px solid #ccc; margin-bottom: 20px; padding-bottom: 10px;">
        <tr>
            {{-- COLUNA 1: LOGO (Esquerda) --}}
            <td style="width: 30%; vertical-align: middle; text-align: left;">
                @if($logoAbsPath && file_exists($logoAbsPath))
                    <img src="{{ $logoAbsPath }}" style="max-height: 60px; width: auto;">
                @else
                    <span style="font-size: 10px; color: #999;">[Sem Logo]</span>
                @endif
            </td>

            {{-- COLUNA 2: NOME DA EMPRESA --}}
            <td style="width: 70%; vertical-align: middle; text-align: left; padding-left: 15px;">
                <h2 style="margin: 0; font-size: 18px; color: #333;">{{ $companyName }}</h2>
            </td>
        </tr>
    </table>

    <h1>CONTRATO DE HONORÁRIOS ADVOCATÍCIOS</h1>

    <p>
        <span class="label">CONTRATANTE:</span>
        <strong>{{ $client['name'] }}</strong>,
        {{ $client['doc_type'] }} nº {{ $client['doc'] }},
        residente e domiciliado(a) em
        {{ $client['address'] ?? '______________________________________________________' }}.
    </p>

    <p>
        <span class="label">CONTRATADO(S):</span><br>
        @if($lawyerSpecificName)
            <strong>{{ $lawyerSpecificName }}</strong>, advogado(a), inscrito(a) na OAB sob nº {{ $lawyerSpecificOAB }},
            integrante da sociedade de advocacia
        @endif
        <strong>{{ $firmName }}</strong>, inscrita na OAB sob nº {{ $firmOAB }},
        com escritório profissional situado na {{ $firmAddress }}.
    </p>

    <p>
        <span class="label">OBJETO:</span>
        O presente contrato tem como objeto a prestação de serviços jurídicos para defesa dos interesses do CONTRATANTE
        na
        <strong>Ação {{ $process->area_direito ?? 'Judicial' }}</strong>
        (Ref: {{ $process->titulo }}).
        @if(!empty($process->numero_cnj) && strlen($process->numero_cnj) > 5)
            Processo nº <strong>{{ $process->numero_cnj }}</strong>.
        @else
            (Ação a ser distribuída/protocolada).
        @endif
    </p>

    <p>
        <span class="label">HONORÁRIOS:</span>
        Em remuneração aos serviços profissionais ora contratados, o CONTRATANTE pagará ao CONTRATADO o valor pactuado
        em proposta anexa, acrescido de honorários de sucumbência que vierem a ser arbitrados pelo Juízo, na forma da
        Lei nº 8.906/94.
    </p>

    <p>
        <span class="label">FORO:</span>
        As partes elegem o foro da Comarca de <strong>{{ $city }}</strong> para dirimir quaisquer dúvidas oriundas do
        presente contrato.
    </p>

    <p style="text-align: right; margin-top: 1.5cm;">
        {{ $city }}, {{ $dateExtenso }}.
    </p>

    <div class="signature-block">
        <div class="line"></div>
        <strong>{{ $client['name'] }}</strong><br>
        Contratante
    </div>

    <div class="signature-block">
        <div class="line"></div>
        @if($lawyerSpecificName)
            <strong>{{ $lawyerSpecificName }}</strong><br>
            OAB nº {{ $lawyerSpecificOAB }}<br>
        @else
            <strong>{{ $firmName }}</strong><br>
            OAB nº {{ $firmOAB }}<br>
        @endif
        Contratado
    </div>

    <div class="footer">
        {{ $firmName }} @if($firmOAB) | {{ $firmOAB }} @endif <br>
        {{ core()->getConfigData('lawfirm.settings.general.website') ?? '' }}
    </div>

</body>

</html>