<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczenie filmu</title>
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

    $filmQuery = "SELECT f.title, f.description, f.length, l.name AS lang, f.rental_duration 
                  FROM film f 
                  JOIN language l ON f.language_id = l.language_id 
                  WHERE f.film_id = $fid";
    $films = $baza->query($filmQuery);

    if (!$films || $films->num_rows === 0) {
        echo "<h1>Film nie znaleziony!</h1>";
        exit;
    }

    $actorsResult = $baza->query("
        SELECT GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') AS actors
        FROM film_actor fa 
        JOIN actor a ON fa.actor_id = a.actor_id 
        WHERE fa.film_id = $fid
    ");
    $actors = $actorsResult->fetch_assoc()['actors'] ?? 'Brak informacji o aktorach';

    $availableDatas = $baza->query("
        SELECT 
            COUNT(i.inventory_id) AS wszystkie,
            SUM(CASE WHEN r.rental_id IS NULL OR r.return_date IS NOT NULL THEN 1 ELSE 0 END) AS dostepne
        FROM film f 
        LEFT JOIN inventory i ON f.film_id = i.film_id 
        LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
        WHERE f.film_id = $fid
        GROUP BY f.film_id
    ");
    foreach ($availableDatas as $availableData) {
        $dostepne = $availableData['dostepne'] ?? 0;
    }

    if ($dostepne <= 0) {
        echo "<h1>Brak dostępnych kopii filmu</h1>
          <button onclick=\"location.href='index.php'\">Powrót</button>";
        exit;
    }

    $inventorys = $baza->query("
        SELECT i.inventory_id
        FROM inventory i
        LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
        WHERE i.film_id = $fid
        AND (r.rental_id IS NULL OR r.return_date IS NOT NULL)
        LIMIT 1
    ");

    if (!$inventorys) {
        echo "<h1>Brak dostępnych egzemplarzy filmu {$film['title']}.</h1>";
        exit;
    }

    if (!$inventorys || $inventorys->num_rows === 0) {
        echo "<h1>Brak dostępnych egzemplarzy filmu.</h1>";
        echo "<button onclick=\"location.href='index.php'\">Powrót</button>";
        exit;
    }

    $inventory = $inventorys->fetch_assoc();
    $inventoryId = $inventory['inventory_id'];

    $rentalResult = $baza->query("INSERT INTO rental (rental_date, inventory_id, customer_id, return_date, staff_id, last_update) VALUES (NOW(), $inventoryId, 1, NULL, 1, NOW())");
    
    if (!$rentalResult) {
        echo "<h1>Błąd podczas wypożyczania filmu: {$baza->error}</h1>";
        echo "<button onclick=\"location.href='index.php'\">Powrót</button>";
        exit;
    }

    $film = $films->fetch_assoc();
    
    if ($film) {
        echo "
            <section class='movie-info'>
                <h1>Pomyślnie wypożyczono film:</h1>
                <h2>{$film['title']}</h2>
                <p>Czas wypożyczenia: {$film['rental_duration']} dni</p>
                <p>Język: {$film['lang']}</p>
                <p>Aktorzy: {$actors}</p>
            </section>
            <section class='movie-info'>
                <button onclick=\"location.href='film.php?fid={$fid}'\">Wróć do szczegółów filmu</button>
            </section>
        ";
    } else {
        echo "<h1>Błąd podczas pobierania informacji o filmie</h1>";
        echo "<button onclick=\"location.href='film.php?fid={$fid}'\">Powrót</button>";
    }
    ?>
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