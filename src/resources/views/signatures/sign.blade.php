<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assinar Documento - LogísticaJus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .signature-pad {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #fff;
        }
        .signature-pad:hover {
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">LogísticaJus</h1>
                    <p class="text-gray-500 text-sm">Assinatura Eletrônica de Documentos</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($request->signature_type === 'simple') bg-gray-100 text-gray-800
                        @elseif($request->signature_type === 'electronic') bg-blue-100 text-blue-800
                        @elseif($request->signature_type === 'digital') bg-purple-100 text-purple-800
                        @else bg-green-100 text-green-800 @endif">
                        {{ $request->signature_type_label }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Document Info --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Documento para Assinatura</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Nome do Documento</p>
                    <p class="font-medium text-gray-900">{{ $request->document_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Solicitado por</p>
                    <p class="font-medium text-gray-900">{{ $request->requester->name ?? 'Sistema' }}</p>
                </div>
            </div>

            @if($request->message)
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <p class="text-sm text-blue-700">{{ $request->message }}</p>
            </div>
            @endif

            <a href="{{ route('signatures.document', $signer->access_token) }}" 
               target="_blank"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Visualizar Documento
            </a>
        </div>

        {{-- Signer Info --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Seus Dados</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nome</p>
                    <p class="font-medium text-gray-900">{{ $signer->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">E-mail</p>
                    <p class="font-medium text-gray-900">{{ $signer->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Papel</p>
                    <p class="font-medium text-gray-900">{{ $signer->role_label }}</p>
                </div>
            </div>
        </div>

        @if($canSign)
        {{-- Signature Form --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6" id="signatureSection">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Assinatura</h2>

            @if($request->signature_type === 'electronic')
            {{-- Verification Code --}}
            <div class="mb-6" id="verificationSection">
                <label class="block text-sm font-medium text-gray-700 mb-2">Código de Verificação</label>
                <div class="flex gap-2">
                    <input type="text" id="verificationCode" maxlength="6" 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Digite o código de 6 dígitos">
                    <button type="button" onclick="requestCode()" id="requestCodeBtn"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Solicitar Código
                    </button>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    O código será enviado para {{ Str::mask($signer->email, '*', strpos($signer->email, '@') - 4, 4) }}
                </p>
            </div>
            @endif

            {{-- Signature Pad --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Desenhe sua assinatura</label>
                <canvas id="signaturePad" class="signature-pad w-full" height="200"></canvas>
                <div class="flex justify-end mt-2">
                    <button type="button" onclick="clearSignature()" 
                            class="text-sm text-gray-500 hover:text-gray-700">
                        Limpar assinatura
                    </button>
                </div>
            </div>

            {{-- Terms --}}
            <div class="mb-6">
                <label class="flex items-start">
                    <input type="checkbox" id="acceptTerms" class="mt-1 mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-600">
                        Declaro que li e concordo com o documento acima e que esta assinatura eletrônica tem validade jurídica equivalente à assinatura manuscrita, conforme Lei nº 14.063/2020 (Marco Legal das Assinaturas Eletrônicas).
                    </span>
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-4">
                <button type="button" onclick="submitSignature()" id="signBtn"
                        class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Assinar Documento
                </button>
                <button type="button" onclick="showRejectModal()" 
                        class="px-6 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-lg transition">
                    Recusar
                </button>
            </div>
        </div>
        @else
        {{-- Waiting Message --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="font-medium text-yellow-800">Aguardando sua vez</h3>
                    <p class="text-sm text-yellow-700">
                        Este documento requer assinatura em ordem. Por favor, aguarde os signatários anteriores assinarem.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Other Signers --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Todos os Signatários</h2>
            
            <div class="space-y-3">
                @foreach($request->signers as $s)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3
                            @if($s->status === 'signed') bg-green-100 text-green-600
                            @elseif($s->status === 'rejected') bg-red-100 text-red-600
                            @else bg-gray-200 text-gray-500 @endif">
                            @if($s->status === 'signed')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @elseif($s->status === 'rejected')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                {{ $s->signing_order }}
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $s->name }}
                                @if($s->id === $signer->id) <span class="text-blue-600">(Você)</span> @endif
                            </p>
                            <p class="text-sm text-gray-500">{{ $s->role_label }}</p>
                        </div>
                    </div>
                    <span class="text-sm 
                        @if($s->status === 'signed') text-green-600
                        @elseif($s->status === 'rejected') text-red-600
                        @else text-gray-500 @endif">
                        {{ $s->status_label }}
                        @if($s->signed_at) - {{ $s->signed_at->format('d/m/Y H:i') }} @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>Powered by <strong>LogísticaJus</strong> - Gestão Jurídica Inteligente</p>
            <p class="mt-1">Código de verificação: {{ $request->uid }}</p>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recusar Assinatura</h3>
            <p class="text-sm text-gray-600 mb-4">Por favor, informe o motivo da recusa:</p>
            <textarea id="rejectReason" rows="4" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                      placeholder="Descreva o motivo..."></textarea>
            <div class="flex gap-3 mt-4">
                <button type="button" onclick="closeRejectModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="button" onclick="submitReject()" 
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    Confirmar Recusa
                </button>
            </div>
        </div>
    </div>

    <script>
        // Signature Pad
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas?.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        if (canvas) {
            // Resize canvas
            function resizeCanvas() {
                const ratio = window.devicePixelRatio || 1;
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = 200 * ratio;
                ctx.scale(ratio, ratio);
                ctx.strokeStyle = '#1f2937';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
            }
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            // Drawing events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // Touch events
            canvas.addEventListener('touchstart', handleTouchStart);
            canvas.addEventListener('touchmove', handleTouchMove);
            canvas.addEventListener('touchend', stopDrawing);

            function startDrawing(e) {
                isDrawing = true;
                [lastX, lastY] = getPosition(e);
            }

            function draw(e) {
                if (!isDrawing) return;
                const [x, y] = getPosition(e);
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                [lastX, lastY] = [x, y];
            }

            function stopDrawing() {
                isDrawing = false;
            }

            function getPosition(e) {
                const rect = canvas.getBoundingClientRect();
                return [e.clientX - rect.left, e.clientY - rect.top];
            }

            function handleTouchStart(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }

            function handleTouchMove(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }
        }

        function clearSignature() {
            if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }

        function getSignatureImage() {
            return canvas ? canvas.toDataURL('image/png') : null;
        }

        function isSignatureEmpty() {
            if (!canvas) return true;
            const pixelData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            for (let i = 3; i < pixelData.length; i += 4) {
                if (pixelData[i] !== 0) return false;
            }
            return true;
        }

        // Request verification code
        async function requestCode() {
            const btn = document.getElementById('requestCodeBtn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const response = await fetch('{{ route("signatures.request-code", $signer->access_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                alert(data.message);

                if (data.success) {
                    btn.textContent = 'Reenviar Código';
                    // Disable for 60 seconds
                    let countdown = 60;
                    const interval = setInterval(() => {
                        btn.textContent = `Reenviar (${countdown}s)`;
                        countdown--;
                        if (countdown < 0) {
                            clearInterval(interval);
                            btn.disabled = false;
                            btn.textContent = 'Reenviar Código';
                        }
                    }, 1000);
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Solicitar Código';
                }
            } catch (error) {
                alert('Erro ao solicitar código. Tente novamente.');
                btn.disabled = false;
                btn.textContent = 'Solicitar Código';
            }
        }

        // Submit signature
        async function submitSignature() {
            const acceptTerms = document.getElementById('acceptTerms');
            if (!acceptTerms.checked) {
                alert('Você precisa aceitar os termos para continuar.');
                return;
            }

            if (isSignatureEmpty()) {
                alert('Por favor, desenhe sua assinatura.');
                return;
            }

            @if($request->signature_type === 'electronic')
            const code = document.getElementById('verificationCode').value;
            if (!code || code.length !== 6) {
                alert('Por favor, informe o código de verificação de 6 dígitos.');
                return;
            }
            @endif

            const btn = document.getElementById('signBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-5 h-5 inline-block mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Processando...';

            try {
                const response = await fetch('{{ route("signatures.process", $signer->access_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        signature_image: getSignatureImage(),
                        @if($request->signature_type === 'electronic')
                        verification_code: document.getElementById('verificationCode').value
                        @endif
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Assinar Documento';
                }
            } catch (error) {
                alert('Erro ao processar assinatura. Tente novamente.');
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Assinar Documento';
            }
        }

        // Reject modal
        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }

        async function submitReject() {
            const reason = document.getElementById('rejectReason').value;
            if (!reason.trim()) {
                alert('Por favor, informe o motivo da recusa.');
                return;
            }

            try {
                const response = await fetch('{{ route("signatures.reject", $signer->access_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ reason })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Erro ao processar recusa. Tente novamente.');
            }
        }
    </script>
</body>
</html>
