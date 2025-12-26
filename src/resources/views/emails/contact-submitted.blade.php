<p>Você recebeu uma nova mensagem de contato via JURIS MAIDEN:</p>
<ul>
    <li><strong>Nome:</strong> {{ $data['name'] }}</li>
    <li><strong>E-mail:</strong> {{ $data['email'] }}</li>
</ul>
<p><strong>Mensagem:</strong></p>
<p>{{ nl2br(e($data['message'])) }}</p>
