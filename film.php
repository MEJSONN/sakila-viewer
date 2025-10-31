<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: {$baza->connect_error}");

$fid = $_GET['fid'] ?? 0;
$stmt = $baza->prepare("SELECT * FROM film_list WHERE fid = ?");
$stmt->bind_param("i", $fid);
$stmt->execute();
$filmInfo = $stmt->get_result();
if (!$filmInfo) {
    die("Błąd podczas pobierania informacji o filmie");
}

$film = $filmInfo->fetch_assoc();
if ($film) {
    $title = $film['title'];
    $description = $film['description'];
    $length = (int)$film['length'];
    $actors = $film['actors'];
} else {
    die("Film nie został znaleziony");
}

$languageStmt = $baza->prepare("SELECT name FROM language WHERE language_id = (SELECT language_id FROM film WHERE film_id = ?)");
$languageStmt->bind_param("i", $fid);
$languageStmt->execute();
$languageInfo = $languageStmt->get_result();
foreach ($languageInfo as $languageRow) {
    $language = $languageRow['name'];
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
        <button onclick="location.href='index.php'">Wróć do listy filmów</button>
        <h1><?php echo $title; ?></h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>
    <section class="movie-info">
        <?php echo "<p>{$description}</p>"; ?>
    </section>
    <section class="movie-info">
        <p><strong>Długość:</strong> <?php echo $length; ?> minut</p>
        <p><strong>Język:</strong> <?php echo $language; ?></p>
        <p><strong>Aktorzy:</strong> <?php echo $actors; ?></p>
    </section>
    <section class="movie-info">
        <?php
        $datas = $baza->query("
            SELECT 
                COUNT(i.inventory_id) AS wszystkie,
                SUM(CASE WHEN r.rental_id IS NULL OR r.return_date IS NOT NULL THEN 1 ELSE 0 END) AS dostepne
            FROM film f 
            LEFT JOIN inventory i ON f.film_id = i.film_id 
            LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
            WHERE f.film_id = $fid
            GROUP BY f.film_id
        ");

        foreach ($datas as $data) {
            $wszystkie = $data['wszystkie'] ?? 0;
            $dostepne = $data['dostepne'] ?? 0;
            $disabled = ($dostepne <= 0) ? 'disabled' : '';
        }

        echo "<button $disabled onclick=\"location.href='rent.php?fid=$fid'\">Wypożycz ($dostepne/$wszystkie)</button>";
        ?>
    </section>
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

<?php
$baza->close();
?>