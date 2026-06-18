<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Preview - {{ $date }}</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background: #fff;
            font-family: 'Outfit', sans-serif;
            color: #121212;
            font-size: 10px;
            padding: 8px;
        }
        
        .nota-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .nota-card {
            width: 170px;
            border: 1px solid #000;
            background: #fff;
            padding: 2px;
        }
        
        .nota-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        
        .nota-header-table td {
            border: 1px solid black;
            padding: 2px 3px;
            font-size: 9px;
        }
        
        .nota-header-table .no-cell {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            width: 25px;
        }
        
        .nota-header-table .date-cell {
            font-size: 8px;
            text-align: center;
            width: 55px;
        }
        
        .nota-table {
            width: 100%;
            table-layout: fixed;
            font-size: 8px;
            border-collapse: collapse;
        }
        
        .nota-table th, .nota-table td {
            text-align: center;
            padding: 1px 2px;
        }
        
        .nota-table th { font-weight: bold; background: #eee; }
        
        .nota-table .nama-col { text-align: left; width: 60px; overflow: hidden; white-space: nowrap; }
        
        .nota-table tfoot td {
            font-weight: bold;
            border-top: 1px solid #000;
            background: #f5f5f5;
        }
        
        .nota-summary {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 2px;
            margin-top: 2px;
        }
        
        .summary-left, .summary-right {
            font-size: 7px;
        }
        
        .summary-left div, .summary-right div {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        
        .payout-container {
            background: #10b981;
            color: #fff;
            padding: 3px 4px;
            border-radius: 2px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .nota-footer {
            text-align: center;
            font-size: 6px;
            color: #666;
            margin-top: 2px;
        }
        
        .m { font-variant-numeric: tabular-nums; }
        .b { font-weight: bold; }
        .r { color: #999; }
    </style>
</head>
<body>
    @include('admin.nota.partials.styles')
    @include('admin.nota.partials.content')
</body>
</html>