@extends('layouts.app')

@section('title', 'Contato • ' . config('juris.software_name'))

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold">Entre em contato</h1>

    <p class="mt-3">Para consultas, diligências e agendamento, entre em contato com nosso escritório:</p>

    <div class="mt-4 bg-white rounded-md p-4 shadow">
        <p><strong>Telefone/WhatsApp:</strong> <a href="tel:{{ config('juris.phone') }}">{{ config('juris.phone') }}</a></p>
        <p><strong>E-mail:</strong> <a href="mailto:{{ config('juris.emails.contact') }}">{{ config('juris.emails.contact') }}</a></p>
        <p><strong>Endereço:</strong> {{ config('juris.address') }}</p>
        <p class="mt-2 text-sm text-slate-500">Atendimento presencial com hora marcada.</p>
    </div>

    <div class="mt-6">
        <h3 class="font-semibold">Formulário de contato</h3>
        <form method="post" action="{{ route('contact.send', [], false) }}" class="mt-3">
            @csrf
            <label class="block mt-2">Nome
                <input required name="name" class="w-full border rounded p-2 mt-1" />
            </label>
            <label class="block mt-2">E-mail
                <input required name="email" type="email" class="w-full border rounded p-2 mt-1" />
            </label>
            <label class="block mt-2">Mensagem
                <textarea required name="message" class="w-full border rounded p-2 mt-1" rows="5"></textarea>
            </label>

            <button type="submit" class="mt-3 bg-[var(--juris-gold,#CBA135)] px-4 py-2 rounded font-semibold">Enviar</button>
        </form>
    </div>
</div>

@include('partials.office-footer')
@endsection