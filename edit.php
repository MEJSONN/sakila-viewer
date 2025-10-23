<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: " . $baza->connect_error);

$fid = $_GET['fid'];

$filmInfo = $baza->query("SELECT f.*, l.name AS language, GROUP_CONCAT(c.name) AS categories
                            FROM film f
                            JOIN language l ON f.language_id = l.language_id
                            LEFT JOIN film_category fc ON f.film_id = fc.film_id
                            LEFT JOIN category c ON fc.category_id = c.category_id
                            WHERE f.film_id = $fid
                            GROUP BY f.film_id;
                        ");
foreach ($filmInfo as $film) {
    $title = $film['title'];
}

?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1><?php echo $title; ?></h1>
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