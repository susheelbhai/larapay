<html>

<head>
    <title> CCAvanue Payment Gateway </title>
</head>

<body>

    <form method="post" name="redirect" action="{{ $orderData['action_url'] }}">
        <input type="hidden" name="encRequest" value="{{ $orderData['encrypted_data'] }}">
        <input type="hidden" name="access_code" value="{{ $orderData['access_code'] }}">

    </form>
    <script language='javascript'>
        document.redirect.submit();
    </script>
</body>

</html>
