<?php

namespace SuiteZap\LawFirm\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SuiteZap\LawFirm\Events\PrazoCreated;
use SuiteZap\LawFirm\Whatsapp\Listeners\SendPrazoWhatsapp;

class LawFirmServiceProvider extends ServiceProvider
{
    /**
     * Versão do pacote LawFirm.
     */
    public const VERSION = '3.25';

    /**
     * Bootstrap services.
     *
     * Método executado após todos os Service Providers serem registrados.
     * Responsável por carregar rotas, views, migrações, traduções, observers e listeners.
     *
     * @return void
     */
    public function boot()
    {
        // 1. Configuração Dinâmica de Storage (Multi-Tenant)
        try {
            // Só tenta configurar se não estiver rodando no console (artisan) para evitar erros em migrations
            if (!app()->runningInConsole()) {
                \SuiteZap\LawFirm\SaaS\Services\MotherShipService::configureTenantStorage();
            }
        } catch (\Exception $e) {
            // Falha silenciosa para não derrubar o sistema se o MotherShip estiver offline
            // O sistema usará o bucket padrão do .env como fallback
            Log::error('SAAS ERROR: Falha ao configurar storage dinâmico: ' . $e->getMessage());
        }

        // ====================================================================
        // VERIFICAÇÃO DE ASSINATURA SAAS
        // ====================================================================
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', \SuiteZap\LawFirm\Http\Middleware\CheckSubscriptionStatus::class);

        // ====================================================================
        // 1. CARREGAR ROTAS
        // ====================================================================
        $routesPath = __DIR__ . '/../Http/routes.php';

        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        } else {
            Log::error('LawFirm: Arquivo de rotas não encontrado!', ['path' => $routesPath]);
        }

        // Rotas API
        $apiRoutesPath = __DIR__ . '/../Routes/api.php';
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }

        // ====================================================================
        // 2. CARREGAR VIEWS
        // ====================================================================
        $viewsPath = __DIR__ . '/../Resources/views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'lawfirm');
        }

        // ====================================================================
        // 3. CARREGAR MIGRAÇÕES
        // ====================================================================
        $migrationsPath = __DIR__ . '/../Database/Migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // ====================================================================
        // 4. CARREGAR TRADUÇÕES
        // ====================================================================
        $langPath = __DIR__ . '/../Resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'lawfirm');
        }

        // ====================================================================
        // 4.5 REGISTRAR COMANDOS DO CONSOLE
        // ====================================================================
        if ($this->app->runningInConsole()) {
            $this->commands([
                \SuiteZap\LawFirm\Console\Commands\CalculateStorageUsage::class,
                // Gerenciamento de templates de IA no Mothership (zero-deploy sync)
                \SuiteZap\LawFirm\Console\Commands\PublishAiTemplatesCommand::class,
            ]);
        }

        // ====================================================================
        // 5. REGISTRAR OBSERVERS
        // ====================================================================
        $this->registerObservers();

        // ====================================================================
        // 6. EVENT LISTENERS - Injeções de Views
        // ====================================================================
        $this->registerEventListeners();

        // PrazoCreated -> WhatsApp notification
        Event::listen(PrazoCreated::class, SendPrazoWhatsapp::class);

        // ====================================================================
        // BANNER DE ASSINATURA (aviso de vencimento no topo de todas as telas)
        // Usa o hook admin.layout.content.before para injetar o banner global
        // ====================================================================
        Event::listen('admin.layout.content.before', function ($viewRenderEventManager) {
            if (auth()->guard('user')->check()) {
                $viewRenderEventManager->addTemplate('lawfirm::admin.layouts.subscription-warning');
            }
        });

        // Injeta o script de CEP auto-fill na página de configurações do LawFirm
        Event::listen('admin.layout.content.before', function ($viewRenderEventManager) {
            $uri = request()->path();
            if (str_contains($uri, 'configuration/lawfirm')) {
                $viewRenderEventManager->addTemplate('lawfirm::SaaS.settings.cep-autofill');
            }
        });

        // ====================================================================
        // 7. VIEW COMPOSERS
        // ====================================================================
        $this->registerViewComposers();

        // ====================================================================
        // CONFIGURAÇÃO DO MENU LATERAL
        // ====================================================================
        $breadcrumbsPath = __DIR__ . '/../Routes/breadcrumbs.php';
        if (file_exists($breadcrumbsPath)) {
            require $breadcrumbsPath;
        }

        // ====================================================================
        // 8. BLADE COMPONENTS
        // ====================================================================
        // Register anonymous components path for package components
        $componentsPath = __DIR__ . '/../Resources/views/components';
        if (is_dir($componentsPath)) {
            \Illuminate\Support\Facades\Blade::anonymousComponentPath($componentsPath, 'lawfirm');
        }

        \Illuminate\Support\Facades\Blade::component('lawfirm::assistant-panel', 'assistant-panel');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        // Merge Config (Menu)
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/menu.php',
            'menu.admin'
        );

        // Merge Config (ACL)
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/acl.php',
            'acl'
        );

        // Merge Config (System) - Configurações do Painel
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/system.php',
            'core_config'
        );

        // Merge Config (LawFirm) - Configurações gerais do pacote (mothership_secret, etc.)
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/lawfirm.php',
            'lawfirm'
        );

        $this->app->register(EventServiceProvider::class);

        // ✅ OVERRIDE: ActivityDataGrid (Defensive Coding)
        $this->app->bind(
            \Webkul\Admin\DataGrids\Activity\ActivityDataGrid::class,
            \SuiteZap\LawFirm\Legal\DataGrids\SafeActivityDataGrid::class
        );
    }

    /**
     * Register model observers.
     *
     * @return void
     */
    protected function registerObservers()
    {
        \SuiteZap\LawFirm\Legal\Models\Processo::observe(\SuiteZap\LawFirm\Legal\Observers\ProcessoObserver::class);
        \SuiteZap\LawFirm\Legal\Models\Prazo::observe(\SuiteZap\LawFirm\Legal\Observers\PrazoObserver::class);


        // ✅ REGISTRO DO OBSERVER SAAS
        // Intercepta qualquer criação de usuário no sistema para validar limites do plano
        \Webkul\User\Models\User::observe(\SuiteZap\LawFirm\SaaS\Observers\UserObserver::class);

        // ✅ REGISTRO DO OBSERVER DE LIMPEZA S3
        // Apaga arquivos do S3/MinIO quando um Lead/Processo é excluído
        \Webkul\Lead\Models\Lead::observe(\SuiteZap\LawFirm\GED\Observers\LeadObserver::class);

        // ✅ REGISTRO DO OBSERVER DE LIMPEZA S3 (Anexos Individuais)
        // Apaga arquivos do S3/MinIO quando um anexo individual é removido
        \Webkul\Lead\Models\LeadAttachment::observe(\SuiteZap\LawFirm\GED\Observers\LeadAttachmentObserver::class);
    }

    /**
     * Register event listeners for view injection.
     *
     * @return void
     */
    protected function registerEventListeners()
    {
        // ---------------------------------------------------------------------
        // Lead: Atualização pós-save
        // ---------------------------------------------------------------------
        Event::listen('sales.lead.update.after', 'SuiteZap\\LawFirm\\Legal\\Listeners\\LeadUpdatedListener@handle');
        Event::listen('lead.update.after', \SuiteZap\LawFirm\Legal\Listeners\LeadWonListener::class);

        // ---------------------------------------------------------------------
        // CONTATOS: Persistência de Dados (Substituindo Observers)
        // ---------------------------------------------------------------------

        // Pessoas (Create/Update) - VALIDAÇÃO NOS EVENTOS BEFORE
        Event::listen('contacts.person.create.before', function () {
            if (request()->has('law_details')) {
                $validator = Validator::make(request()->all(), [
                    'law_details.cpf' => ['nullable', new \SuiteZap\LawFirm\Legal\Rules\Cpf],
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        });

        Event::listen('contacts.person.update.before', function () {
            if (request()->has('law_details')) {
                $validator = Validator::make(request()->all(), [
                    'law_details.cpf' => ['nullable', new \SuiteZap\LawFirm\Legal\Rules\Cpf],
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        });

        // Pessoas - SALVAMENTO NOS EVENTOS AFTER (Validação já foi feita)
        Event::listen('contacts.person.create.after', function ($person) {
            if (request()->has('law_details')) {
                $data = request('law_details');
                $data['person_id'] = $person->id;
                if (!isset($data['type']))
                    $data['type'] = 'PF';

                \SuiteZap\LawFirm\Legal\Models\LawPersonDetail::updateOrCreate(['person_id' => $person->id], $data);
            }
        });

        Event::listen('contacts.person.update.after', function ($person) {
            if (request()->has('law_details')) {
                $data = request('law_details');
                $data['person_id'] = $person->id;

                \SuiteZap\LawFirm\Legal\Models\LawPersonDetail::updateOrCreate(['person_id' => $person->id], $data);
            }
        });

        // Organizações (Create/Update) - VALIDAÇÃO NOS EVENTOS BEFORE
        Event::listen('contacts.organization.create.before', function () {
            if (request()->has('law_org_details')) {
                $validator = Validator::make(request()->all(), [
                    'law_org_details.cnpj' => ['nullable', new \SuiteZap\LawFirm\Legal\Rules\Cnpj],
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        });

        Event::listen('contacts.organization.update.before', function () {
            if (request()->has('law_org_details')) {
                $validator = Validator::make(request()->all(), [
                    'law_org_details.cnpj' => ['nullable', new \SuiteZap\LawFirm\Legal\Rules\Cnpj],
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        });

        // Organizações - SALVAMENTO NOS EVENTOS AFTER (Validação já foi feita)
        Event::listen('contacts.organization.create.after', function ($organization) {
            if (request()->has('law_org_details')) {
                $data = request('law_org_details');
                $data['organization_id'] = $organization->id;
                \SuiteZap\LawFirm\Legal\Models\LawOrganizationDetail::updateOrCreate(['organization_id' => $organization->id], $data);
            }
        });

        Event::listen('contacts.organization.update.after', function ($organization) {
            if (request()->has('law_org_details')) {
                $data = request('law_org_details');
                $data['organization_id'] = $organization->id;
                \SuiteZap\LawFirm\Legal\Models\LawOrganizationDetail::updateOrCreate(['organization_id' => $organization->id], $data);
            }
        });

        // ---------------------------------------------------------------------
        // Lead: Aba Processos
        // ---------------------------------------------------------------------
        Event::listen('admin.leads.view.activities.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::admin.leads.tab_processos');
            Log::debug('LawFirm: View injetada em admin.leads.view.activities.after');
        });

        // ---------------------------------------------------------------------
        // Pessoa: Aba Processos (View)
        // ---------------------------------------------------------------------
        // Event::listen('admin.contact.persons.view.right.after', function ($viewRenderEventManager) {
        //     $viewRenderEventManager->addTemplate('lawfirm::admin.contacts.persons.tab_processos');
        //     Log::debug('LawFirm: View injetada em admin.contact.persons.view.right.after');
        // });

        // ---------------------------------------------------------------------
        // Organização: Aba Processos (View - Show/Edit Tab if applicable)
        // ---------------------------------------------------------------------
        // Nota: Krayin às vezes usa nomes diferentes, ajustando se necessário.
        // Event::listen('admin.organizations.edit.form.after', function ($viewRenderEventManager) {
        //     // Este hook geralmente é fora do form, se fosse tab.
        //     // Mantendo se for aba de processos.
        //     $viewRenderEventManager->addTemplate('lawfirm::admin.contacts.organizations.tab_processos');
        //     Log::debug('LawFirm: View injetada em admin.organizations.edit.form.after');
        // });

        // ---------------------------------------------------------------------
        // PESSOA: Campos Avançados (PF) - EDIT/CREATE forms
        // ---------------------------------------------------------------------
        Event::listen('admin.contacts.persons.edit.form_controls.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::contacts.persons.edit-extension');
        });

        Event::listen('admin.persons.create.form_controls.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::contacts.persons.edit-extension');
        });

        // ---------------------------------------------------------------------
        // ORGANIZAÇÃO: Campos Avançados (PJ) - CREATE/EDIT forms
        // ---------------------------------------------------------------------
        Event::listen('admin.contacts.organizations.create.form_controls.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::contacts.organizations.edit-extension');
        });

        Event::listen('admin.contacts.organizations.edit.form_controls.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::contacts.organizations.edit-extension');
        });

        // ---------------------------------------------------------------------
        // Dashboard Widget
        // ---------------------------------------------------------------------
        Event::listen('admin.dashboard.index.open_leads_by_states.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::admin.dashboard.widgets.law-firm-overview');
        });

        // ---------------------------------------------------------------------
        // Lead: Aba Processos (REMOVIDO por solicitação - Causando erro 500 e duplicidade)
        // ---------------------------------------------------------------------
        // Antiga injeção: admin.leads.view.activities.after -> lawfirm::admin.leads.tab_processos

        // ---------------------------------------------------------------------
        // Lead: Assistant Injection (Global/Layout)
        // ---------------------------------------------------------------------
        // ---------------------------------------------------------------------
        // Lead: Assistant Injection (Global/Layout) - REMOVIDO
        // ---------------------------------------------------------------------
        // O código foi removido para limpar a interface e manter apenas o menu Jurídico.
    }

    /**
     * Register View Composers.
     *
     * @return void
     */
    protected function registerViewComposers()
    {
        // Dashboard Widget - Dados
        View::composer('lawfirm::admin.dashboard.widgets.law-firm-overview', function ($view) {
            $activeCount = \SuiteZap\LawFirm\Legal\Models\Processo::where('status', 'Ativo')->count();
            $totalValorCausa = \SuiteZap\LawFirm\Legal\Models\Processo::where('status', 'Ativo')->sum('valor_causa');
            $totalValorGanho = \SuiteZap\LawFirm\Legal\Models\Processo::whereIn('status', ['Encerrado', 'Arquivado', 'Concluído'])->sum('valor_causa');

            $upcomingHearings = \SuiteZap\LawFirm\Legal\Models\Processo::query()
                ->where('status', 'Ativo')
                ->whereNotNull('data_audiencia')
                ->whereBetween('data_audiencia', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->orderBy('data_audiencia', 'asc')
                ->limit(5)
                ->get();

            $view->with([
                'activeCount' => $activeCount,
                'totalValorCausa' => $totalValorCausa,
                'totalValorGanho' => $totalValorGanho,
                'upcomingHearings' => $upcomingHearings,
            ]);
        });
    }
}