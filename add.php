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
</head>

<body>
    <section class="movie-info">
        <label for="title">
            <span>Tytuł: </span>
            <input type="text" id="title" name="title" required>
        </label>
        <label for="description">
            <span>Opis: </span>
            <textarea id="description" name="description" required></textarea>
        </label>
        <label for="relase_year">
            <span>Rok wydania: </span>
            <input type="number" id="relase_year" name="relase_year" required>
        </label>
        <label for="langue">
            <span>Język: </span>
            <select name="langue">
                <?php
                $langues = $baza->query("SELECT language_id, name FROM `language`");
                foreach ($langues as $langue) {
                    echo "<option value='" . $langue['language_id'] . "'>" . $langue['name'] . "</option>";
                }
                ?>
            </select>
        </label>
        <label for="orginalLanguage">
            <span>Język oryginalny: </span>
            <select name="orginalLanguage">
                <option value="">Brak</option>
                <?php
                $langues = $baza->query("SELECT language_id, name FROM `language`");
                foreach ($langues as $langue) {
                    echo "<option value='" . $langue['language_id'] . "'>" . $langue['name'] . "</option>";
                }
                ?>
            </select>
        </label>
        <label for="rentalDuration">
            <span>Czas wypożyczenia (dni): </span>
            <input type="number" id="rentalDuration" name="rentalDuration" required>
        </label>
    </section>
</body>

</html>