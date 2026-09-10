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
     *
     * Formato novo (PRIV-AUDIT-001): `exp.{timestamp}.{hmac}` com expiração
     * (padrão 30 dias). Formato legado (hmac puro) ainda aceito com log de
     * depreciação — links antigos enviados via WhatsApp seguem funcionando.
     */
    private function verifyToken($processoId, $token)
    {
        if (empty($token)) {
            return false;
        }

        $token = (string) $token;

        if (str_starts_with($token, 'exp.')) {
            $parts = explode('.', $token);
            if (count($parts) !== 3 || ! ctype_digit($parts[1])) {
                return false;
            }

            if ((int) $parts[1] < time()) {
                Log::warning('[Portal] Token expirado.', ['processo_id' => $processoId]);

                return false;
            }

            $expected = hash_hmac('sha256', $processoId.'|'.$parts[1], config('app.key'));

            return hash_equals($expected, $parts[2]);
        }

        // Legado: sem expiração — aceito com aviso para renovação do link.
        $expectedLegacy = hash_hmac('sha256', $processoId, config('app.key'));

        if (hash_equals($expectedLegacy, $token)) {
            Log::info('[Portal] Token legado (sem expiração) utilizado.', ['processo_id' => $processoId]);

            return true;
        }

        return false;
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
            // Whitelist estrita (PRIV-AUDIT-001): só campos do formulário do portal.
            $validated = $request->validate([
                'client_type'               => 'required|in:PF,PJ',
                'name'                      => 'nullable|string|max:255',
                'email'                     => 'nullable|email|max:255',
                'phone'                     => 'nullable|string|max:30',
                'cpf'                       => 'nullable|string|max:20',
                'cnpj'                      => 'nullable|string|max:20',
                'rg'                        => 'nullable|string|max:30',
                'nationality'               => 'nullable|string|max:100',
                'marital_status'            => 'nullable|string|max:50',
                'profession'                => 'nullable|string|max:100',
                'birth_date'                => 'nullable|date',
                'mother_name'               => 'nullable|string|max:255',
                'father_name'               => 'nullable|string|max:255',
                'cep'                       => 'nullable|string|max:20',
                'street'                    => 'nullable|string|max:255',
                'number'                    => 'nullable|string|max:50',
                'complement'                => 'nullable|string|max:100',
                'neighborhood'              => 'nullable|string|max:100',
                'city'                      => 'nullable|string|max:100',
                'state'                     => 'nullable|string|max:10',
                'state_registration'        => 'nullable|string|max:50',
                'municipal_registration'    => 'nullable|string|max:50',
                'legal_representative_name' => 'nullable|string|max:255',
                'legal_representative_cpf'  => 'nullable|string|max:20',
            ]);

            // Log operacional sem PII (PRIV-AUDIT-001: nunca logar dados do formulário).
            $clientType = $validated['client_type'];
            Log::info('Portal Update Request:', ['id' => $id, 'client_type' => $clientType]);

            $input = function (string $key) use ($validated) {
                return $validated[$key] ?? null;
            };

            if ($clientType === 'PF' && $processo->person_id) {
                $person = Person::find($processo->person_id);
                if ($person) {
                    Log::info('Updating Person PF:', ['id' => $person->id]);
                    $person->name = $input('name');
                    $person->emails = [['value' => $input('email'), 'label' => 'work']];
                    $person->contact_numbers = [['value' => $input('phone'), 'label' => 'work']];
                    $person->save();

                    // Law person details
                    LawPersonDetail::updateOrCreate(
                        ['person_id' => $person->id],
                        [
                            'cpf'             => $input('cpf'),
                            'rg'              => $input('rg'),
                            'nacionalidade'   => $input('nationality'),
                            'estado_civil'    => $input('marital_status'),
                            'profissao'       => $input('profession'),
                            'data_nascimento' => $input('birth_date'),
                            'nome_mae'        => $input('mother_name'),
                            'nome_pai'        => $input('father_name'),
                            'cep'             => $input('cep'),
                            'logradouro'      => $input('street'),
                            'numero'          => $input('number'),
                            'complemento'     => $input('complement'),
                            'bairro'          => $input('neighborhood'),
                            'cidade'          => $input('city'),
                            'uf'              => $input('state'),
                        ]
                    );
                }
            } elseif ($clientType === 'PJ' && $processo->organization_id) {
                $org = Organization::find($processo->organization_id);
                if ($org) {
                    Log::info('Updating Organization PJ:', ['id' => $org->id]);
                    $org->name = $input('name');
                    // Usually addresses in organization are stored either in organization itself or via law_org_details
                    // Assuming Krayin standard addresses or custom fields

                    $org->save();

                    LawOrganizationDetail::updateOrCreate(
                        ['organization_id' => $org->id],
                        [
                            'cnpj'                => $input('cnpj'),
                            'razao_social'        => $input('name'), // Name is normalized in payload
                            'inscricao_estadual'  => $input('state_registration'),
                            'inscricao_municipal' => $input('municipal_registration'),
                            'cep'                 => $input('cep'),
                            'logradouro'          => $input('street'),
                            'numero'              => $input('number'),
                            'complemento'         => $input('complement'),
                            'bairro'              => $input('neighborhood'),
                            'cidade'              => $input('city'),
                            'uf'                  => $input('state'),
                            'representante_legal' => $input('legal_representative_name').($input('legal_representative_cpf') ? ' (CPF: '.$input('legal_representative_cpf').')' : ''),
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

        // Upload restrito a documentos (PRIV-AUDIT-001): antes aceitava qualquer tipo.
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
        ]);

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
