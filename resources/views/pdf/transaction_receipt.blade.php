<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header img {
            max-width: 120px;
        }
        .header h2 {
            margin-top: 10px;
            color: #2c3e50;
        }
        .content {
            margin: 20px 0;
        }
        .content p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 5px;
            overflow: hidden;
        }
        td, th {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #555;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ public_path('images/logo.png') }}" alt="Company Logo">
            <h2>Transaction Receipt</h2>
        </div>

        <!-- Greeting -->
        <div class="content">
            <p>Dear <strong>{{ $transaction->member->name }}</strong>,</p>
            <p>We are pleased to confirm that your transaction has been successfully processed. Below are the details of your transaction:</p>
        </div>

        <!-- Transaction Details -->
        <table>
            <tr>
                <th>Transaction Reference</th>
                <td>{{ $transaction->transaction_reference }}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>UGX {{ number_format($transaction->amount, 2) }}</td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ $transaction->created_at->format('d M Y, H:i A') }}</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
            <p><strong>Uniwealth</strong></p>
            <p>123 Ntinda Street, Near Fraine Supermarket</p>
            <p>Website: <a href="https://uniwealthapp.com">www.uniwealthapp.com</a></p>
            <p>Contact: +256 759 983 853  | info@uniwealthapp.com</p>
            <p>Powered by <strong>UniWealth</strong></p>
        </div>
    </div>
</body>
</html>
