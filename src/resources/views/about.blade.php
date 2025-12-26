@extends('layouts.app')

@section('title', 'Sobre • ' . config('juris.software_name'))

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="flex gap-6 items-start">
        <img src="{{ asset('img/juris-logo.png') }}" alt="{{ config('juris.office_name') }}" style="height:70px; object-fit:contain;" />
        <div>
            <h1 class="text-2xl font-bold">Sobre {{ config('juris.software_name') }}</h1>
            <p class="mt-3 text-slate-700">JURIS MAIDEN é um software de gestão jurídica e operacional desenvolvido para atender especificamente às necessidades do escritório <strong>{{ config('juris.office_name') }}</strong>. Inspirado no visual institucional do site, o sistema organiza processos, documentos, agenda e integrações de forma segura e prática.</p>

            <h3 class="mt-4 font-semibold">Filosofia</h3>
            <p class="text-slate-700">Atendimento humanizado, eficiência e foco em resultados. O JURIS MAIDEN foi projetado para trazer confiança operacional ao time jurídico, com integração a Google Drive/Calendar, controle de prazos e gestão documental.</p>
        </div>
    </div>

    <div class="mt-6">
        <h4 class="font-semibold">Serviços</h4>
        <ul class="list-disc ml-5 mt-2 text-slate-700">
            <li>Gestão de processos e prazos</li>
            <li>Sincronização de documentos com Google Drive</li>
            <li>Agenda e compromissos integrados ao Google Calendar</li>
            <li>Controle de diligências e tarefas</li>
        </ul>
    </div>
</div>

@include('partials.office-footer')
@endsection