<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Expirada - LogísticaJus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            {{-- Clock Icon --}}
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Solicitação Indisponível</h1>
            <p class="text-gray-600 mb-6">Esta solicitação de assinatura não está mais disponível.</p>

            {{-- Details --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Documento</p>
                        <p class="font-medium text-gray-900">{{ $request->document_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status</p>
                        <p class="font-medium text-gray-900">{{ $request->status_label }}</p>
                    </div>
                    @if($request->expires_at)
                    <div>
                        <p class="text-gray-500">Expirou em</p>
                        <p class="font-medium text-gray-900">{{ $request->expires_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if($request->status === 'completed')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">
                    <strong>Boas notícias!</strong> Este documento já foi completamente assinado.
                </p>
            </div>
            @elseif($request->status === 'cancelled')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    Esta solicitação foi cancelada pelo solicitante.
                </p>
            </div>
            @elseif($request->status === 'expired')
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800">
                    O prazo para assinatura expirou. Entre em contato com o solicitante para uma nova solicitação.
                </p>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-3">
                <a href="/" class="block w-full px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Voltar ao Início
                </a>
            </div>

            {{-- Footer --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Powered by <strong>LogísticaJus</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
