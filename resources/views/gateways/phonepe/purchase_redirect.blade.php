<html>

<head>
    <title> Phonepe Payment Gateway </title>
</head>

<body>

    <form method="post" name="redirect" action="{{ $orderData['action_url'] }}">
        @csrf

    </form>
    <script language='javascript'>
        document.redirect.submit();
    </script>
</body>

</html>
