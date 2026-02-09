<?php

namespace SuiteZap\LawFirm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use SuiteZap\LawFirm\Models\AssistantTemplate;
use SuiteZap\LawFirm\Models\AiExecution;

class AssistantController extends Controller
{
    /**
     * Get templates filtered by category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTemplates(Request $request)
    {
        // Filter by Labor Law module as requested
        $templates = AssistantTemplate::where('required_module', 'lawfirm_labor')
            ->where('is_active', true)
            ->get();

        return response()->json($templates);
    }

    /**
     * Execute the assistant prompt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(Request $request)
    {
        $request->validate([
            'template_id' => 'required|integer',
            'lead_id' => 'required|integer',
            'inputs' => 'required|array',
        ]);

        $template = AssistantTemplate::find($request->input('template_id'));

        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        // Create execution record
        $execution = AiExecution::create([
            'lead_id' => $request->input('lead_id'),
            'template_id' => $template->id,
            'prompt_version' => 'v1', // Assuming v1 for now
            'input_data' => $request->input('inputs'),
            'output_data' => ['status' => 'processing'], // Placeholder for status
            'risk_level' => null,
            'confidence' => null,
        ]);

        // TODO: Integrate with N8N or external AI service here.
        // For now, we return a fake success response.

        return response()->json([
            'status' => 'success',
            'execution_id' => $execution->id,
            'message' => 'Execution started successfully.',
        ]);
    }
}
