<?php

namespace SuiteZap\LawFirm\DataJud\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\DataJud\Services\DataJudService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class DataJudController extends Controller
{
    protected $dataJudService;

    public function __construct(DataJudService $dataJudService)
    {
        $this->dataJudService = $dataJudService;
    }

    /**
     * Executa a consulta na API do DataJud, validando a request de entrada.
     */
    public function executarServico(Request $request)
    {
        $request->validate([
            'service_type' => 'required|in:DATAJUD_CONSULTA_PUBLICA',
            'data' => 'required|array',
            'data.numero_cnj' => 'required|string',
            'processo_id' => 'sometimes|nullable|integer',
        ]);

        $tenantId = MotherShipService::getTenantId();
        
        $result = $this->dataJudService->consultarProcesso(
            $request->input('data.numero_cnj'), 
            $tenantId
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'message' => 'Consulta realizada com sucesso no DataJud.',
            'async' => false,
            'external_id' => null,
            'request_id' => null
        ]);
    }
}
