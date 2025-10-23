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

    $film = $baza->query("
    SELECT f.title, f.description, f.length, l.name AS lang,
           GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') AS actors,
           f.rental_duration
    FROM film f
    JOIN language l ON f.language_id = l.language_id
    JOIN film_actor fa ON f.film_id = fa.film_id
    JOIN actor a ON fa.actor_id = a.actor_id
    WHERE f.film_id = $fid
    GROUP BY f.film_id
")->fetch_assoc();

    if (!$film) {
        echo "<h1>Film nie znaleziony!</h1>";
        exit;
    }

    $availableQuery = "
    SELECT 
        COUNT(i.inventory_id) AS wszystkie,
        SUM(
            CASE 
                WHEN (
                    SELECT r2.return_date
                    FROM rental r2
                    WHERE r2.inventory_id = i.inventory_id
                    ORDER BY r2.rental_date DESC
                    LIMIT 1
                ) IS NULL THEN 1 ELSE 0 END
        ) AS wypozyczone
    FROM inventory i
    WHERE i.film_id = $fid
";
    $availableData = $baza->query($availableQuery)->fetch_assoc();
    $dostepne = ($availableData['wszystkie'] ?? 0) - ($availableData['wypozyczone'] ?? 0);

    if ($dostepne <= 0) {
        echo "<h1>Brak dostępnych kopii filmu <em>{$film['title']}</em>.</h1>
          <button onclick=\"location.href='index.php'\">Powrót</button>";
        exit;
    }

    $inventoryQuery = "
    SELECT i.inventory_id
    FROM inventory i
    LEFT JOIN rental r 
        ON r.inventory_id = i.inventory_id
        AND r.rental_date = (
            SELECT MAX(r2.rental_date)
            FROM rental r2
            WHERE r2.inventory_id = i.inventory_id
        )
    WHERE i.film_id = $fid 
    AND (r.return_date IS NOT NULL OR r.rental_id IS NULL)
    LIMIT 1
";
    $inventory = $baza->query($inventoryQuery)->fetch_assoc();

    if (!$inventory) {
        echo "<h1>Brak dostępnych egzemplarzy filmu {$film['title']}.</h1>";
        exit;
    }

    $inventoryId = $inventory['inventory_id'];

    $baza->query("
    INSERT INTO rental (rental_date, inventory_id, customer_id, return_date, staff_id, last_update)
    VALUES (NOW(), $inventoryId, 1, NULL, 1, NOW())
");

    echo "
<section class='movie-info'>
    <h1>Pomyślnie wypożyczono film:</h1>
    <h2>{$film['title']}</h2>
    <p>Czas wypożyczenia: {$film['rental_duration']} dni</p>
    <p>Język: {$film['lang']}</p>
    <p>Aktorzy: {$film['actors']}</p>
    <button onclick=\"location.href='index.php'\">Wróć do listy filmów</button>
</section>
";
    ?>
</body>

</html>