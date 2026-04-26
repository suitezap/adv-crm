<?php

namespace SuiteZap\LawFirm\DataJud\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SuiteZap\LawFirm\DataJud\Services\DataJudService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class DataJudController extends Controller
{
    private $service;

    public function __construct(DataJudService $service)
    {
        $this->service = $service;
    }

    /**
     * Endpoint unificado para as 3 consultas DataJud:
     *  - 'numero'       : busca por número do processo CNJ
     *  - 'classe_orgao' : busca por código de classe + código de órgão julgador
     *  - 'paginacao'    : paginação com search_after
     */
    public function consulta(Request $request)
    {
        $data         = $request->input('data', []);
        $tipoConsulta = $data['tipo_consulta'] ?? 'numero';
        $tribunal     = $data['tribunal'] ?? null;

        // Validate required fields
        if (!$tribunal) {
            return response()->json([
                'success' => false,
                'error'   => 'Selecione um Tribunal para continuar.',
            ], 422);
        }

        if ($tipoConsulta === 'numero' && empty($data['numero_cnj'])) {
            return response()->json([
                'success' => false,
                'error'   => 'O Número CNJ do Processo é obrigatório para este tipo de consulta.',
            ], 422);
        }

        if ($tipoConsulta === 'classe_orgao' && empty($data['codigo_classe']) && empty($data['codigo_orgao'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Informe ao menos o Código da Classe ou o Código do Órgão Julgador.',
            ], 422);
        }

        $tenantId = MotherShipService::getTenantId();

        $result = $this->service->consultar($tribunal, $tipoConsulta, $data, $tenantId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
            'async'   => false,
        ]);
    }
}
