<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento - {{ class_basename($document->model_type) }} #{{ $document->model_id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #2c3e50;
        }
        
        .header .subtitle {
            font-size: 10pt;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .content {
            margin: 20px 0;
            min-height: 400px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 9pt;
            color: #95a5a6;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .document-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .document-info .info-row {
            margin: 5px 0;
        }
        
        .document-info .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        @page {
            size: A4;
            margin: 2cm;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Stili per il contenuto HTML del documento */
        .content h1, .content h2, .content h3 {
            color: #2c3e50;
        }
        
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .content table th,
        .content table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .content table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .content ul, .content ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        
        .content p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Documento Generato</h1>
        <div class="subtitle">
            {{ class_basename($document->model_type) }} - ID: {{ $document->model_id }} | 
            Template ID: {{ $document->document_template_id }} |
            Generato il: {{ $document->created_at->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="document-info">
        <div class="info-row">
            <span class="label">Tipo Documento:</span>
            <span>{{ class_basename($document->model_type) }}</span>
        </div>
        <div class="info-row">
            <span class="label">ID Record:</span>
            <span>{{ $document->model_id }}</span>
        </div>
        <div class="info-row">
            <span class="label">Template:</span>
            <span>{{ $document->document_template_id }}</span>
        </div>
        <div class="info-row">
            <span class="label">Data Generazione:</span>
            <span>{{ $document->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>

    <div class="content">
        {!! $body !!}
    </div>

    <div class="footer">
        <p>Documento generato automaticamente da UnicoWord - {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
