<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Pencetakan Nota Digital Citroroso - Dokumentasi Penjualan Harian {{ $date }}.">
    <meta name="robots" content="noindex, nofollow">
    <title>Nota Penjualan Citroroso - {{ $date }}</title>
    
    <!-- JSON-LD for Structured Data (SEO/Semantic) -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@type": "Invoice",
      "identifier": "B-{{ $date }}",
      "datePublished": "{{ $date }}",
      "provider": {
        "@type": "Organization",
        "name": "Citroroso Heritage",
        "url": "https://citroroso.com"
      }
    }
    </script>

    @include('admin.nota.partials.styles')

    <!-- Alpine.js (Required for Drawer Interactivity) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap');

        @page {
            size: 330mm 215mm landscape;
            margin: 0;
        }

        body {
            background: #fff;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #121212;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .mono { font-family: 'Outfit', 'Courier New', monospace; font-variant-numeric: tabular-nums; }
        
        /* Responsive grid fix for print view */
        .nota-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3mm;
            width: 100%;
        }
        
        .nota-card {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        @media print {
            .no-print { display: none !important; }
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    </style>
</head>
<body>
    @include('admin.nota.partials.content')
</body>
</html>
