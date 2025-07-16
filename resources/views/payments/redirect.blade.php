<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Payment</title>
</head>
<body>
    <p>Redirecting you to the payment page...</p>

    <script>
        // Open in a new tab
        window.open("{{ $paymentUrl }}", "_blank");

        // Optionally, redirect current tab too
        // window.location.href = "/thank-you";
    </script>
</body>
</html>
