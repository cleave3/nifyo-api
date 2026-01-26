<!DOCTYPE html>
<html>

<head>
    <title>Wallet Statement</title>
    <style>
        body {
            font-family: sans-serif;
        }

        .header {
            margin-bottom: 20px;
        }

        .title {
            font-weight: bold;
            font-size: 18px;
        }

        .info {
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">{{ $business->business_name ?? 'Business Name' }}</div>
        <div class="info">{{ $business->business_address ?? '' }}</div>
        <div class="info">Reg No: {{ $business->business_reg_no ?? '' }}</div>
        <div class="info">Email: {{ $business->business_email ?? '' }} | Phone: {{ $business->business_phone ?? '' }}
        </div>
    </div>

    <div class="customer-info">
        <h4>CUSTOMER INFORMATION</h4>
        <div class="info">Name: {{ $customer->name ?? '' }}</div>
        <div class="info">Type: {{ $customer->customer_type ?? '' }}</div>
        <div class="info">Code: {{ $customer->customer_code ?? '' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Credit</th>
                <th>Debit</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($wallets as $wallet)
                <tr>
                    <td>{{ $wallet->created_at->format('d M Y h:i A') }}</td>
                    <td>{{ $wallet->credit > 0 ? number_format($wallet->credit, 2) : '-' }}</td>
                    <td>{{ $wallet->debit > 0 ? number_format($wallet->debit, 2) : '-' }}</td>
                    <td>{{ $wallet->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>