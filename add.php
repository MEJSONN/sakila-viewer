<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("<h1>Błąd połączenia z bazą danych</h1>");
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodawanie filmu</title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1>Dodawanie filmu</h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>
</body>
<script>
    const theme = localStorage.getItem('theme');
    if (theme === 'dark') document.body.classList.add('dark');

    function changeTheme() {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }
</script>

</html>