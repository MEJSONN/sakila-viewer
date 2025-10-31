<?php
$baza = new mysqli('localhost', 'root', '', 'sakila');
if ($baza->connect_error) die("Błąd połączenia z bazą danych: {$baza->connect_error}");

$fid = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
if ($fid <= 0) {
    die("Niepoprawne ID filmu.");
}
$baza->query("DELETE r FROM rental r JOIN inventory i ON r.inventory_id = i.inventory_id WHERE i.film_id = $fid");
$baza->query("DELETE FROM inventory WHERE film_id = $fid");
$baza->query("DELETE FROM film_actor WHERE film_id = $fid");
$baza->query("DELETE FROM film_category WHERE film_id = $fid");
$baza->query("DELETE FROM film WHERE film_id = $fid");
$baza->close();
header('Location: ./admin.php');
exit;
?>