<?php
// Gastro de Gama — formularz kontaktowy
// Plik: send.php

$TO      = 'mariusz@gastrodegama.pl';
$SUBJECT_PREFIX = '[gastrodegama.pl] ';

// Tylko POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot — bot trap (ukryte pole w formularzu)
if (!empty($_POST['website'])) {
    // Bot wypełnił ukryte pole — udajemy sukces
    header('Location: kontakt.html?status=ok');
    exit;
}

// Funkcja czyszcząca dane
function clean($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Zbierz dane z formularza
$imie      = clean($_POST['imie']      ?? '');
$telefon   = clean($_POST['telefon']   ?? '');
$email     = clean($_POST['email']     ?? '');
$lokal     = clean($_POST['lokal']     ?? '');
$typ       = clean($_POST['typ']       ?? '');
$lokalizacja = clean($_POST['lokalizacja'] ?? '');
$urzadzenie  = clean($_POST['urzadzenie']  ?? '');
$opis      = clean($_POST['opis']      ?? '');
$zgoda     = isset($_POST['zgoda']) ? true : false;

// Walidacja wymaganych pól
$errors = [];
if (empty($imie))    $errors[] = 'Brak imienia';
if (empty($telefon)) $errors[] = 'Brak telefonu';
if (empty($opis))    $errors[] = 'Brak opisu problemu';
if (!$zgoda)         $errors[] = 'Brak zgody RODO';

// Walidacja emaila (opcjonalny, ale jeśli podany — musi być poprawny)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Niepoprawny adres e-mail';
}

if (!empty($errors)) {
    http_response_code(400);
    header('Location: kontakt.html?status=error');
    exit;
}

// Buduj treść maila
$subject = $SUBJECT_PREFIX . $typ . ' — ' . $imie;

$body  = "Nowe zgłoszenie z formularza gastrodegama.pl\n";
$body .= str_repeat('=', 50) . "\n\n";
$body .= "DANE KONTAKTOWE\n";
$body .= "Imię i nazwisko:  $imie\n";
$body .= "Telefon:          $telefon\n";
$body .= "E-mail:           " . ($email ?: '—') . "\n";
$body .= "Lokal / firma:    " . ($lokal ?: '—') . "\n\n";
$body .= "ZGŁOSZENIE\n";
$body .= "Typ:              $typ\n";
$body .= "Lokalizacja:      $lokalizacja\n";
$body .= "Urządzenie:       " . ($urzadzenie ?: '—') . "\n\n";
$body .= "OPIS PROBLEMU\n";
$body .= "$opis\n\n";
$body .= str_repeat('-', 50) . "\n";
$body .= "Wysłano: " . date('Y-m-d H:i:s') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n";

// Nagłówki maila
$headers  = "From: formularz@gastrodegama.pl\r\n";
$headers .= "Reply-To: " . ($email ?: $TO) . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Wyślij
$sent = mail($TO, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

if ($sent) {
    header('Location: kontakt.html?status=ok');
} else {
    header('Location: kontakt.html?status=error');
}
exit;
