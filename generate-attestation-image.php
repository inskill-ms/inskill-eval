<?php
/**
 * Fichier : generate-attestation-image.php
 * Emplacement : /wp-content/plugins/inskill-eval/
 *
 * Ce fichier génère dynamiquement l’attestation de formation au format image (JPG)
 * en utilisant la librairie GD de PHP. Le texte est superposé sur une image de fond
 * et mis en forme ligne par ligne comme suit :
 *
 * Ligne 1 : "ATTESTATION" – centré, police Book Antiqua, taille 36
 * Ligne 2 : "DE FORMATION PROFESSIONNELLE" – centré, police Book Antiqua, taille 36
 * Ligne 3 : "Ce document atteste que ««PARTICIPANT_NOM»» ««PARTICIPANT_PRÉNOM»»" – centré,
 *          police OldNewspaperTypes, taille 45, avec le nom et le prénom en majuscules
 * Ligne 4 : "a participé à «training_duration» de formation sur la thématique" – centré,
 *          police OldNewspaperTypes, taille 45, en minuscules
 * Ligne 5 : "«module_title»" – centré, police OldNewspaperTypes, taille 45
 * Ligne 6 : "organisée par INSKILL pour «CLIENT_COMPANY»." – centré, police OldNewspaperTypes,
 *          taille 45, avec le nom de l'entreprise en majuscules
 * Ligne 7 : "Ce document atteste que ««PARTICIPANT_NOM»» ««PARTICIPANT_PRÉNOM»»" – centré,
 *          police OldNewspaperTypes, taille 45, avec le nom et le prénom en majuscules
 * Ligne 8 : Ligne vide (espacement)
 * Ligne 9 : "a bien assisté et achevé sa formation" – centré, police OldNewspaperTypes, taille 45
 * Ligne 10 : "conformément aux attentes et exigence du cabinet INSKILL." – centré,
 *          police OldNewspaperTypes, taille 45
 * Ligne 11 : Ligne vide (espacement)
 * Ligne 12 : "Le «creation_date»" – aligné à gauche, police OldNewspaperTypes, taille 45,
 *          avec la date au format "JJ Mois AAAA"
 * Ligne 13 : "MICHEL SOMAS" – aligné à gauche, police OldNewspaperTypes, taille 29
 * Ligne 14 : "«trainer_name»" – centré, police OldNewspaperTypes, taille 29
 */

// Charger l'environnement WordPress en remontant jusqu'à la racine
require_once( dirname(__FILE__) . '/../../../wp-load.php' );

global $wpdb;

// Vérifier la présence du paramètre GET "response_id"
$response_id = isset($_GET['response_id']) ? intval($_GET['response_id']) : 0;
if (!$response_id) {
    wp_die("Paramètre manquant.");
}

// Récupérer la réponse correspondante
$table_responses = $wpdb->prefix . 'inskill_eval_responses';
$response = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_responses WHERE id = %d", $response_id));
if (!$response) {
    wp_die("Réponse non trouvée.");
}

// Récupérer le questionnaire associé
$table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
$questionnaire = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_questionnaires WHERE id = %d", $response->questionnaire_id));
if (!$questionnaire) {
    wp_die("Questionnaire non trouvé.");
}

// Récupérer les données à afficher
$participant_nom    = $response->participant_nom;
$participant_prenom = $response->participant_prenom;
$client_company     = $questionnaire->client_company;
$training_duration  = $questionnaire->training_duration;
$module_title       = $questionnaire->module_title;
$trainer_name       = $questionnaire->trainer_name;
$creation_date      = date("d/m/Y");

// Pour formater la date au format "JJ Mois AAAA", on fixe la locale en français
setlocale(LC_TIME, 'fr_FR.UTF-8');
// Convertir la date (en remplaçant éventuellement les "/" par "-" pour être sûr que strtotime fonctionne correctement)
$creation_date_formatted = strftime("%d %B %Y", strtotime(str_replace('/', '-', $creation_date)));

// Détermination de l'image de fond en fonction de trainer_name (insensible à la casse)
$trainer_name_lower = strtolower($trainer_name);
if ($trainer_name_lower === 'michel somas' || $trainer_name_lower === 'somas michel') {
    $template_image = dirname(__FILE__) . '/templates/Attestation_design_MS.jpg';
} elseif ($trainer_name_lower === 'siham dahman' || $trainer_name_lower === 'dahman siham') {
    $template_image = dirname(__FILE__) . '/templates/Attestation_design_SD.jpg';
} else {
    $template_image = dirname(__FILE__) . '/templates/Attestation_design.jpg';
}

if (!file_exists($template_image)) {
    wp_die("Le template d'attestation est manquant.");
}

// Charger l'image de fond
$img = imagecreatefromjpeg($template_image);
if (!$img) {
    wp_die("Impossible de charger l'image de fond.");
}

// Allouer la couleur noire pour le texte
$black = imagecolorallocate($img, 0, 0, 0);

// Définir les chemins vers les polices
$font_book = dirname(__FILE__) . '/fonts/BKANT.TTF';              // Pour Book Antiqua (titre)
$font_old  = dirname(__FILE__) . '/fonts/OldNewspaperTypes.ttf';     // Pour le reste du texte

if (!file_exists($font_book)) {
    wp_die("La police Book Antiqua (BKANT.TTF) est manquante.");
}
if (!file_exists($font_old)) {
    wp_die("La police OldNewspaperTypes est manquante.");
}

/**
 * Fonction d'aide pour centrer le texte sur l'image.
 *
 * @param resource $img        L'image GD.
 * @param string   $font_path  Chemin complet de la police TTF.
 * @param int      $font_size  Taille de la police.
 * @param int      $angle      Angle du texte.
 * @param string   $text       Texte à afficher.
 * @param int      $y          Coordonnée Y où afficher le texte.
 * @param int      $color      Couleur allouée.
 */
function centerText($img, $font_path, $font_size, $angle, $text, $y, $color) {
    $bbox = imagettfbbox($font_size, $angle, $font_path, $text);
    $text_width = $bbox[2] - $bbox[0];
    $img_width = imagesx($img);
    $x = ($img_width - $text_width) / 2;
    imagettftext($img, $font_size, $angle, $x, $y, $color, $font_path, $text);
}

/**
 * Fonction d'aide pour afficher du texte aligné à gauche.
 *
 * @param resource $img        L'image GD.
 * @param string   $font_path  Chemin complet de la police TTF.
 * @param int      $font_size  Taille de la police.
 * @param int      $angle      Angle du texte.
 * @param string   $text       Texte à afficher.
 * @param int      $x          Coordonnée X où afficher le texte.
 * @param int      $y          Coordonnée Y où afficher le texte.
 * @param int      $color      Couleur allouée.
 */
function leftText($img, $font_path, $font_size, $angle, $text, $x, $y, $color) {
    imagettftext($img, $font_size, $angle, $x, $y, $color, $font_path, $text);
}

// Affichage des lignes selon le modèle souhaité

// Ligne 1 : "ATTESTATION" – centré, Book Antiqua
centerText($img, $font_book, 113, 0, "ATTESTATION", 730, $black);

// Ligne 2 : "DE FORMATION PROFESSIONNELLE" – centré, Book Antiqua
centerText($img, $font_book, 113, 0, "DE FORMATION PROFESSIONNELLE", 917, $black);

// Ligne 3 : "Ce document atteste que «PARTICIPANT_NOM PARTICIPANT_PRÉNOM»"
// avec les noms en majuscules, OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "Ce document atteste que « " . strtoupper($participant_nom) . " " . strtoupper($participant_prenom) . " »", 1072, $black);

// Ligne 4 : "a participé à training_duration de formation sur la thématique" – en minuscules, OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, strtolower("a participé à " . $training_duration . " de formation sur la thématique"), 1145, $black);

// Ligne 5 : "«module_title»" – OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "« " . $module_title . " »", 1218, $black);

// Ligne 6 : "organisée par INSKILL pour CLIENT_COMPANY." – avec le nom de l'entreprise en majuscules, OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "organisée par INSKILL pour " . strtoupper($client_company) . ".", 1291, $black);

// Ligne 7 : "Ce document atteste que «PARTICIPANT_NOM PARTICIPANT_PRÉNOM»"
// avec les noms en majuscules, OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "Ce document atteste que « " . strtoupper($participant_nom) . " " . strtoupper($participant_prenom) . " »", 1422, $black);

// Ligne 8 : Ligne vide (espacement)

// Ligne 9 : "a bien assisté et achevé sa formation" – OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "a bien assisté et achevé sa formation", 1495, $black);

// Ligne 10 : "conformément aux attentes et exigence du cabinet INSKILL." – OldNewspaperTypes, taille 45
centerText($img, $font_old, 45, 0, "conformément aux attentes et exigence du cabinet INSKILL.", 1568, $black);

// Ligne 11 : Ligne vide (espacement)

// Ligne 12 : "Le creation_date" – aligné à gauche, OldNewspaperTypes, taille 45, avec la date formatée
leftText($img, $font_old, 45, 0, "Le " . $creation_date_formatted, 440, 1800, $black);

// Ligne 13 : "MICHEL SOMAS" – aligné à gauche, OldNewspaperTypes, taille 29
leftText($img, $font_old, 29, 0, "MICHEL SOMAS", 910, 2190, $black);

// Ligne 14 : "trainer_name" – centré, OldNewspaperTypes, taille 29
centerText($img, $font_old, 29, 0, $trainer_name, 2190, $black);

// Forcer le téléchargement automatique de l'image
header("Content-Type: image/jpeg");
header("Content-Disposition: attachment; filename=\"attestation.jpg\"");
imagejpeg($img);
imagedestroy($img);
exit;
