<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Recusada - LogísticaJus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 to-red-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            {{-- Warning Icon --}}
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Assinatura Recusada</h1>
            <p class="text-gray-600 mb-6">Você recusou assinar este documento.</p>

            {{-- Details --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Documento</p>
                        <p class="font-medium text-gray-900">{{ $request->document_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Motivo da Recusa</p>
                        <p class="font-medium text-gray-900">{{ $signer->rejection_reason }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    O solicitante foi notificado sobre sua decisão.
                </p>
            </div>

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
