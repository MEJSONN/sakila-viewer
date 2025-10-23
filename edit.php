<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("<h1>Błąd połączenia z bazą danych</h1>");

$fid = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
if ($fid <= 0) {
    echo "<h1>Nieznany film.</h1>";
    exit;
}

// Obsługa zapisu zmian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $baza->real_escape_string($_POST['title']);
    $description = $baza->real_escape_string($_POST['description']);
    $length = (int)$_POST['length'];
    $rental_duration = (int)$_POST['rental_duration'];
    $language_id = (int)$_POST['language_id'];

    $baza->query("
        UPDATE film SET 
            title = '$title',
            description = '$description',
            length = $length,
            rental_duration = $rental_duration,
            language_id = $language_id,
            last_update = NOW()
        WHERE film_id = $fid
    ");
}

// Pobranie danych filmu
$film = $baza->query("
    SELECT f.title, f.description, f.length, f.rental_duration, l.language_id, l.name AS lang,
           GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name) SEPARATOR ', ') AS actors
    FROM film f
    JOIN language l ON f.language_id = l.language_id
    LEFT JOIN film_actor fa ON f.film_id = fa.film_id
    LEFT JOIN actor a ON fa.actor_id = a.actor_id
    WHERE f.film_id = $fid
    GROUP BY f.film_id
")->fetch_assoc();

if (!$film) {
    echo "<h1>Film nie znaleziony!</h1>";
    exit;
}

// Sprawdzenie dostępnych egzemplarzy
$availableData = $baza->query("
    SELECT COUNT(i.inventory_id) AS wszystkie,
           SUM(CASE WHEN (
               SELECT r2.return_date
               FROM rental r2
               WHERE r2.inventory_id = i.inventory_id
               ORDER BY r2.rental_date DESC
               LIMIT 1
           ) IS NULL THEN 1 ELSE 0 END) AS wypozyczone
    FROM inventory i
    WHERE i.film_id = $fid
")->fetch_assoc();

$wszystkie = $availableData['wszystkie'] ?? 0;
$wypozyczone = $availableData['wypozyczone'] ?? 0;
$dostepne = $wszystkie - $wypozyczone;

// Pobranie wszystkich języków do selecta
$languages = $baza->query("SELECT * FROM language ORDER BY name");
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film: <?= htmlspecialchars($film['title']) ?></title>
    <link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <button onclick="location.href='admin.php'">Panel administracyjny</button>
        <h1><?= htmlspecialchars($film['title']) ?></h1>
        <button onclick="changeTheme()">Zmień motyw</button>
    </header>

    <section class="movie-info">
        <h2>Edytuj informacje o filmie</h2>
        <form method="post">
            <label>Tytuł:<br>
                <input type="text" name="title" value="<?= htmlspecialchars($film['title']) ?>" required>
            </label><br>
            <label>Opis:<br>
                <textarea name="description" rows="4" required><?= htmlspecialchars($film['description']) ?></textarea>
            </label><br>
            <label>Długość filmu (min):<br>
                <input type="number" name="length" value="<?= $film['length'] ?>" required>
            </label><br>
            <label>Czas wypożyczenia (dni):<br>
                <input type="number" name="rental_duration" value="<?= $film['rental_duration'] ?>" required>
            </label><br>
            <label>Język:<br>
                <select name="language_id">
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?= $lang['language_id'] ?>" <?= ($lang['language_id'] == $film['language_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lang['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <button type="submit" class="button-save">Zapisz zmiany</button>
        </form>
    </section>

    <section class="movie-info">
        <?php
        // Wypożyczenia filmu
        $rentedInfos = $baza->query("
            SELECT r.rental_id, r.rental_date, r.return_date,
                   c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                   s.staff_id, CONCAT(s.first_name, ' ', s.last_name) AS staff_name,
                   i.inventory_id
            FROM rental r
            JOIN inventory i ON r.inventory_id = i.inventory_id
            JOIN customer c ON r.customer_id = c.customer_id
            JOIN staff s ON r.staff_id = s.staff_id
            WHERE i.film_id = $fid
            ORDER BY r.rental_date DESC
        ");

        if ($rentedInfos->num_rows > 0):
        ?>
            <h3>Wypożyczenia filmu</h3>
            <table>
                <tr>
                    <th>ID wypożyczenia</th>
                    <th>Data wypożyczenia</th>
                    <th>Data zwrotu</th>
                    <th>ID klienta</th>
                    <th>Klient</th>
                    <th>ID pracownika</th>
                    <th>Pracownik</th>
                    <th>ID egzemplarza</th>
                </tr>
                <?php foreach ($rentedInfos as $r): ?>
                    <tr>
                        <td><?= $r['rental_id'] ?></td>
                        <td><?= $r['rental_date'] ?></td>
                        <td><?= $r['return_date'] ?? 'Nie zwrócono' ?></td>
                        <td><?= $r['customer_id'] ?></td>
                        <td><?= htmlspecialchars($r['customer_name']) ?></td>
                        <td><?= $r['staff_id'] ?></td>
                        <td><?= htmlspecialchars($r['staff_name']) ?></td>
                        <td><?= $r['inventory_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Brak wypożyczeń dla tego filmu.</p>
        <?php endif; ?>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') document.body.classList.add('dark');
        });

        function changeTheme() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }
    </script>
</body>

</html>