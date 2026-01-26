<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f8fa;
            margin: 0;
            padding: 0;
        }

        .container {
            background: #fff;
            max-width: 500px;
            margin: 40px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 32px 24px;
        }

        .header {
            font-size: 22px;
            font-weight: bold;
            color: #22223b;
            margin-bottom: 16px;
        }

        .content {
            font-size: 16px;
            color: #444;
            margin-bottom: 24px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .info-table td {
            padding: 8px 0;
        }

        .info-label {
            color: #888;
            width: 120px;
        }

        .footer {
            font-size: 13px;
            color: #888;
            margin-top: 32px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 24px;
            background: #0078d4;
            color: #ffffff !important;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 16px;
        }
    </style>
</head>

<body>
    <div class="container">
        @yield('content')
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Corpman') }}. All rights reserved.
        </div>
    </div>
</body>

</html>