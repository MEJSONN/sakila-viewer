<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details</title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <?php
    $baza = new mysqli('localhost', 'root', '', 'sakila');
    if ($baza->connect_error) die("<h1>Błąd połączenia z bazą danych</h1>");

    $fid = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
    if ($fid <= 0) {
        echo "<h1>Nieznany film.</h1>";
        exit;
    }

    $film = $baza->query("SELECT f.title, f.description, f.length, l.name AS lang, GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') AS actors, f.rental_duration FROM film f JOIN language l ON f.language_id = l.language_id JOIN film_actor fa ON f.film_id = fa.film_id JOIN actor a ON fa.actor_id = a.actor_id WHERE f.film_id = $fid GROUP BY f.film_id")->fetch_assoc();

    if (!$film) {
        echo "<h1>Film nie znaleziony!</h1>";
        exit;
    }

    $rentedNow = $baza->query("SELECT COUNT(r.rental_id) AS rented FROM inventory i LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL WHERE i.film_id = $fid")->fetch_assoc()['rented'] ?? 0;

    $available = max(0, $film['rental_duration'] - $rentedNow);
    ?>

    <h1 class="movie-title"><?= $film['title'] ?></h1>
    <section class="movie-description"><?= $film['description'] ?></section>

    <section class="movie-info">
        <p><strong>Długość filmu:</strong> <?= $film['length'] ?> min</p>
        <p><strong>Język:</strong> <?= $film['lang'] ?></p>
        <p><strong>Aktorzy:</strong> <?= $film['actors'] ?></p>
        <p><strong>Dostępna ilość do wypożyczenia:</strong> <?= $available ?></p>
    </section>

    <section class="buttons movie-info">
        <button onclick="location.href='index.php'">Wróć do listy filmów</button>
        <button <?= ($available <= 0) ? 'disabled' : "onclick=\"location.href='rent.php?fid=$fid'\"" ?>>Wypożycz</button>
    </section>


</body>

</html>