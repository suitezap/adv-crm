<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

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
