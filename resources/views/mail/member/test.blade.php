<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; color: #333; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
    <!-- Logo -->
    <table width="100%" style="text-align: center; margin-bottom: 20px;">
        <tr>
            <td>
                <img src="{{ $logo }}" alt="Company Logo" style="max-width: 150px;">
            </td>
        </tr>
    </table>

    <!-- Header -->
    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 10px;">Transaction Receipt</h2>
    <p style="text-align: center; font-size: 14px; color: #555;">Transaction successfully completed.</p>

    <!-- Greeting -->
    <p style="font-size: 16px;">Dear <strong>{{ $name }}</strong>,</p>
    <p style="font-size: 14px; color: #555;">We are pleased to inform you that your transaction has been successfully processed.</p>

    <!-- Transaction Details -->
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #fff; border-radius: 5px; overflow: hidden;">
        <tr>
            <td style="border: 1px solid #ddd; padding: 12px; background: #f2f2f2; font-weight: bold; width: 50%;">Transaction Reference</td>
            <td style="border: 1px solid #ddd; padding: 12px; text-align: right;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd; padding: 12px; background: #f2f2f2; font-weight: bold;">Amount</td>
            <td style="border: 1px solid #ddd; padding: 12px; text-align: right;">UGX {{ number_format($amount, 2) }}</td>
        </tr>
    </table>

    <!-- Message -->
    <p style="font-size: 14px; color: #555;">A PDF receipt has been attached for your records.</p>
    <p style="font-size: 14px; color: #555;">If you have any questions, feel free to contact our support team.</p>

    <!-- Closing -->
    <p style="font-size: 14px; color: #333; font-weight: bold;">Thank you for choosing our service.</p>
    
    <!-- Footer -->
    <p style="text-align: center; font-size: 12px; color: #888; margin-top: 20px;">© {{ date('Y') }} Uniwealth. All rights reserved.</p>
</div>
