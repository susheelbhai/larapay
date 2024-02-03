<html>

<head>
    <title> Phonepe Payment Gateway </title>
</head>

<body>

    <form method="post" name="redirect" action="{{ $orderData['action_url'] }}">
        {{-- <input type="hidden" name="encRequest" value="{{ $orderData['encrypted_data'] }}"> --}}
        <input type="hidden" name="param1" value="tgwtrw">

    </form>
    <script language='javascript'>
        document.redirect.submit();
    </script>
</body>

</html>
