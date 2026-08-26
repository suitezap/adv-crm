<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers\PublicPortal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Legal\Models\LawOrganizationDetail;
use SuiteZap\LawFirm\Legal\Models\LawPersonDetail;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;

class CustomerPortalController extends Controller
{
    /**
     * Check if the provided token is valid for the Processo ID.
     */
    private function verifyToken($processoId, $token)
    {
        if (empty($token)) {
            return false;
        }
        $expectedToken = hash_hmac('sha256', $processoId, config('app.key'));

        return hash_equals($expectedToken, (string) $token);
    }

    /**
     * Render the public portal.
     */
    public function index($id, Request $request)
    {
        if (! $this->verifyToken($id, $request->query('token'))) {
            abort(403, 'Acesso negado. Token inválido ou expirado.');
        }

        $processo = Processo::findOrFail($id);
        $processo->load('person', 'organization');

        $clientType = $processo->person_id ? 'PF' : ($processo->organization_id ? 'PJ' : null);

        // Fetch law details manually to prevent undefined relationship errors on base Krayin models
        $lawDetails = null;
        if ($clientType === 'PF' && $processo->person_id) {
            $lawDetails = LawPersonDetail::where('person_id', $processo->person_id)->first();
        } elseif ($clientType === 'PJ' && $processo->organization_id) {
            $lawDetails = LawOrganizationDetail::where('organization_id', $processo->organization_id)->first();
        }

        // Fetch office settings
        $settings = core()->getConfigData('lawfirm.settings.general');
        // CC fix (audit 2026-05-29): Storage::url() direto removido — usa SaasFileService->url() para compliance Regra 2.2.
        $logoUrl = isset($settings['logo']) ? app(SaasFileService::class)->url($settings['logo']) : null;
        $officeName = $settings['company_name'] ?? 'Escritório de Advocacia';
        $officeWebsite = $settings['website'] ?? null;
        $contactWhatsapp = $settings['contact_whatsapp'] ?? null;

        return view('lawfirm::Legal.public.customer-portal', compact(
            'processo',
            'clientType',
            'lawDetails',
            'logoUrl',
            'officeName',
            'officeWebsite',
            'contactWhatsapp'
        ));
    }

    /**
     * Process data update.
     */
    public function update($id, Request $request)
    {
        if (! $this->verifyToken($id, $request->input('token'))) {
            return response()->json(['success' => false, 'message' => 'Token inválido.'], 403);
        }

        $processo = Processo::findOrFail($id);

        try {
            $clientType = $request->input('client_type');
            Log::info('Portal Update Request:', [
                'id'          => $id,
                'client_type' => $clientType,
                'data'        => $request->except(['token', '_token']),
            ]);

            if ($clientType === 'PF' && $processo->person_id) {
                $person = Person::find($processo->person_id);
                if ($person) {
                    Log::info('Updating Person PF:', ['id' => $person->id]);
                    $person->name = $request->input('name');
                    $person->emails = [['value' => $request->input('email'), 'label' => 'work']];
                    $person->contact_numbers = [['value' => $request->input('phone'), 'label' => 'work']];
                    $person->save();

                    // Law person details
                    LawPersonDetail::updateOrCreate(
                        ['person_id' => $person->id],
                        [
                            'cpf'             => $request->input('cpf'),
                            'rg'              => $request->input('rg'),
                            'nacionalidade'   => $request->input('nationality'),
                            'estado_civil'    => $request->input('marital_status'),
                            'profissao'       => $request->input('profession'),
                            'data_nascimento' => $request->input('birth_date'),
                            'nome_mae'        => $request->input('mother_name'),
                            'nome_pai'        => $request->input('father_name'),
                            'cep'             => $request->input('cep'),
                            'logradouro'      => $request->input('street'),
                            'numero'          => $request->input('number'),
                            'complemento'     => $request->input('complement'),
                            'bairro'          => $request->input('neighborhood'),
                            'cidade'          => $request->input('city'),
                            'uf'              => $request->input('state'),
                        ]
                    );
                }
            } elseif ($clientType === 'PJ' && $processo->organization_id) {
                $org = Organization::find($processo->organization_id);
                if ($org) {
                    Log::info('Updating Organization PJ:', ['id' => $org->id]);
                    $org->name = $request->input('name');
                    // Usually addresses in organization are stored either in organization itself or via law_org_details
                    // Assuming Krayin standard addresses or custom fields

                    $org->save();

                    LawOrganizationDetail::updateOrCreate(
                        ['organization_id' => $org->id],
                        [
                            'cnpj'                => $request->input('cnpj'),
                            'razao_social'        => $request->input('name'), // Name is normalized in payload
                            'inscricao_estadual'  => $request->input('state_registration'),
                            'inscricao_municipal' => $request->input('municipal_registration'),
                            'cep'                 => $request->input('cep'),
                            'logradouro'          => $request->input('street'),
                            'numero'              => $request->input('number'),
                            'complemento'         => $request->input('complement'),
                            'bairro'              => $request->input('neighborhood'),
                            'cidade'              => $request->input('city'),
                            'uf'                  => $request->input('state'),
                            'representante_legal' => $request->input('legal_representative_name').($request->input('legal_representative_cpf') ? ' (CPF: '.$request->input('legal_representative_cpf').')' : ''),
                        ]
                    );
                }
            }

            return response()->json(['success' => true, 'message' => 'Dados atualizados com sucesso!']);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar dados no portal: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Erro ao salvar os dados.'], 500);
        }
    }

    /**
     * Process document upload.
     */
    public function upload($id, Request $request, SaasFileService $fileService)
    {
        if (! $this->verifyToken($id, $request->input('token'))) {
            return response()->json(['success' => false, 'message' => 'Token inválido.'], 403);
        }

        $processo = Processo::findOrFail($id);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');

                // Use the standard DocumentService so files show up in CRM's "Arquivos do Processo" (as Anexo model)
                app(DocumentService::class)->storeFile($file, $processo);

                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                Log::error('Erro ao salvar documento pelo portal: '.$e->getMessage());

                return response()->json(['success' => false, 'message' => 'Não foi possível salvar o arquivo.'], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo encontrado.'], 400);
    }
}
