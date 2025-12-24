<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Assinado com Sucesso - LogísticaJus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            {{-- Success Icon --}}
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Documento Assinado!</h1>
            <p class="text-gray-600 mb-6">Sua assinatura foi registrada com sucesso.</p>

            {{-- Details --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Documento</p>
                        <p class="font-medium text-gray-900">{{ $request->document_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Assinado em</p>
                        <p class="font-medium text-gray-900">{{ $signer->signed_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Signatário</p>
                        <p class="font-medium text-gray-900">{{ $signer->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Código de Verificação</p>
                        <p class="font-medium text-gray-900">{{ $request->uid }}</p>
                    </div>
                </div>
            </div>

            @if($request->isCompleted())
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">
                    <strong>Documento completo!</strong> Todas as assinaturas foram coletadas.
                </p>
            </div>
            @else
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>{{ $request->signed_count }} de {{ $request->total_signers }}</strong> assinaturas concluídas. 
                    Aguardando os demais signatários.
                </p>
            </div>
            @endif

            {{-- Actions --}}
            <div class="space-y-3">
                <a href="{{ route('signatures.document', $signer->access_token) }}" 
                   class="block w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    Baixar Documento
                </a>
                <a href="/" class="block w-full px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Voltar ao Início
                </a>
            </div>

            {{-- Footer --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Esta assinatura eletrônica tem validade jurídica conforme Lei nº 14.063/2020.<br>
                    Powered by <strong>LogísticaJus</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
