<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            padding: 20mm;
        }

        h1, h2, h3, h4, h5, h6 {
            margin-top: 1em;
            margin-bottom: 0.5em;
            font-weight: bold;
        }

        h1 { font-size: 18pt; }
        h2 { font-size: 16pt; }
        h3 { font-size: 14pt; }

        p {
            margin-bottom: 1em;
            text-align: justify;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1em 0;
        }

        table td, table th {
            border: 1px solid #ccc;
            padding: 8px;
        }

        ul, ol {
            margin: 1em 0;
            padding-left: 2em;
        }

        li {
            margin-bottom: 0.5em;
        }

        .header {
            text-align: center;
            margin-bottom: 2em;
            border-bottom: 2px solid #333;
            padding-bottom: 1em;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            font-size: 10pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5mm;
        }

        .page-number:after {
            content: counter(page);
        }

        code {
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 10pt;
        }

        blockquote {
            border-left: 3px solid #666;
            padding-left: 1em;
            margin: 1em 0;
            font-style: italic;
            color: #555;
        }

        .signature-area {
            margin-top: 3em;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 60%;
            margin: 0 auto 5px auto;
        }
    </style>
</head>
<body>
    {{-- Conteúdo do documento --}}
    <div class="content">
        {!! $content !!}
    </div>

    {{-- Rodapé --}}
    <div class="footer">
        <small>
            Documento gerado por LogísticaJus em {{ now()->format('d/m/Y H:i') }} | 
            Template: {{ $template->name }}
        </small>
    </div>
</body>
</html>
