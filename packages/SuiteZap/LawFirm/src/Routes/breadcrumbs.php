<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard > Casos
Breadcrumbs::for('lawfirm.casos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Casos', route('admin.lawfirm.casos.index'));
});

// Dashboard > Casos > Create
Breadcrumbs::for('lawfirm.casos.create', function (BreadcrumbTrail $trail) {
    $trail->parent('lawfirm.casos.index');
    $trail->push('Novo Caso', route('admin.lawfirm.casos.create'));
});

// Dashboard > Casos > Edit
Breadcrumbs::for('lawfirm.casos.edit', function (BreadcrumbTrail $trail, $caso) {
    $trail->parent('lawfirm.casos.index');
    $trail->push('Editar Caso', route('admin.lawfirm.casos.edit', $caso->id));
});

// Dashboard > Casos > View
Breadcrumbs::for('lawfirm.casos.show', function (BreadcrumbTrail $trail, $caso) {
    $trail->parent('lawfirm.casos.index');
    $trail->push($caso->titulo, route('admin.lawfirm.casos.show', $caso->id));
});

// Dashboard > Processos
Breadcrumbs::for('lawfirm.processos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('lawfirm::app.processos.title'), route('admin.processos.index'));
});

// Dashboard > Processos > Create
Breadcrumbs::for('lawfirm.processos.create', function (BreadcrumbTrail $trail) {
    $trail->parent('lawfirm.processos.index');
    $trail->push(trans('lawfirm::app.processos.create'), route('admin.processos.create'));
});

// Dashboard > Processos > Edit
Breadcrumbs::for('lawfirm.processos.edit', function (BreadcrumbTrail $trail, $processo) {
    $trail->parent('lawfirm.processos.index');
    $trail->push(trans('lawfirm::app.processos.edit'), route('admin.processos.edit', $processo->id));
});

// Dashboard > Processos > View
Breadcrumbs::for('lawfirm.processos.show', function (BreadcrumbTrail $trail, $processo) {
    $trail->parent('lawfirm.processos.index');
    $trail->push($processo->titulo, route('admin.processos.show', $processo->id));
});

// Dashboard > Prazos
Breadcrumbs::for('lawfirm.prazos.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('lawfirm::app.prazos.title'), route('admin.prazos.index'));
});

// Dashboard > WhatsApp
Breadcrumbs::for('lawfirm.whatsapp.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('WhatsApp', route('admin.lawfirm.whatsapp.index'));
});

// Dashboard > Minha Assinatura
Breadcrumbs::for('lawfirm.saas.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Minha Assinatura', route('admin.lawfirm.saas.index'));
});

// Dashboard > Assistentes IA
Breadcrumbs::for('lawfirm.assistants.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Assistentes IA', route('lawfirm.assistants.index'));
});

// Dashboard > Assistentes IA > [Template]
Breadcrumbs::for('lawfirm.assistants.show', function (BreadcrumbTrail $trail) {
    $trail->parent('lawfirm.assistants.index');
    $trail->push('Executar Assistente');
});

// Dashboard > Assistente Jurídico (Escavador)
Breadcrumbs::for('lawfirm.escavador.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push('Assistente Jurídico', route('lawfirm.escavador.index'));
});

// Dashboard > Assistentes IA > EscavAI
Breadcrumbs::for('lawfirm.assistants.escavai', function (BreadcrumbTrail $trail) {
    $trail->parent('lawfirm.assistants.index');
    $trail->push('EscavAI', route('lawfirm.assistants.escavai'));
});
