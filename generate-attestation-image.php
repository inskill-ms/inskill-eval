<?php
/**
 * Fichier : generate-attestation-image.php
 * Emplacement : /wp-content/plugins/inskill-eval/generate-attestation-image.php
 *
 * Génère dynamiquement une attestation de formation au format JPG,
 * à la fois pour les réponses validées (response_id) et pour les inscrits
 * n’ayant pas validé (learner_id), en conservant exactement la même mise
 * en page que votre version existante.
 */

// Charger l’environnement WordPress si nécessaire
if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

global $wpdb;

// 1) Récupération des paramètres
$response_id = isset($_GET['response_id']) ? intval($_GET['response_id']) : 0;
$learner_id  = isset($_GET['learner_id'])  ? intval($_GET['learner_id'])  : 0;
if ( ! $response_id && ! $learner_id ) {
    wp_die("Paramètre manquant (response_id ou learner_id).");
}

// Table names
$table_q         = $wpdb->prefix . 'inskill_eval_questionnaires';
$table_responses = $wpdb->prefix . 'inskill_eval_responses';
$table_learners  = $wpdb->prefix . 'inskill_eval_learners';

// 2) Chargement des données du participant
if ( $response_id ) {
    // Cas front-end : réponse validée
    $resp = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_responses WHERE id = %d",
        $response_id
    ) );
    if ( ! $resp ) {
        wp_die("Réponse non trouvée.");
    }
    $participant_nom    = $resp->participant_nom;
    $participant_prenom = $resp->participant_prenom;
    $questionnaire_id   = $resp->questionnaire_id;
} else {
    // Cas bulk : inscrit sans réponse
    $learner = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table_learners WHERE id = %d",
        $learner_id
    ) );
    if ( ! $learner ) {
        wp_die("Apprenant non trouvé.");
    }
    $participant_nom    = $learner->participant_nom;
    $participant_prenom = $learner->participant_prenom;
    $questionnaire_id   = $learner->questionnaire_id;
}

// 3) Chargement du questionnaire
$q = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $table_q WHERE id = %d",
    $questionnaire_id
) );
if ( ! $q ) {
    wp_die("Questionnaire non trouvé.");
}

// 4) Récupération des attributs
$client_company    = $q->client_company;
$training_duration = $q->training_duration;
$module_title      = $q->module_title;
$trainer_name      = $q->trainer_name;
// On récupère d'abord la date finale stockée (ou on retombe sur training_dates par défaut)
$raw = !empty($q->training_date_finale) ? $q->training_date_finale : $q->training_dates;
// On normalise pour strtotime
$ts = strtotime( str_replace('/', '-', $raw) );
setlocale(LC_TIME, 'fr_FR.UTF-8');
$creation_date_f = $ts ? strftime("%d %B %Y", $ts) : $raw;

// 5) Sélection du template selon le formateur
$tn = mb_strtolower( $trainer_name, 'UTF-8' );
if ( strpos($tn, 'michel somas') !== false ) {
    $template_image = INSKILL_EVAL_DIR . 'templates/Attestation_design_MS.jpg';
} elseif ( strpos($tn, 'siham dahman') !== false ) {
    $template_image = INSKILL_EVAL_DIR . 'templates/Attestation_design_SD.jpg';
} else {
    $template_image = INSKILL_EVAL_DIR . 'templates/Attestation_design.jpg';
}
if ( ! file_exists( $template_image ) ) {
    wp_die("Template manquant : $template_image");
}

// 6) Création de l’image GD
$img = imagecreatefromjpeg( $template_image );
if ( ! $img ) {
    wp_die("Impossible de charger l'image de fond.");
}
$black     = imagecolorallocate($img, 0, 0, 0);
$font_book = INSKILL_EVAL_DIR . 'fonts/BKANT.TTF';
$font_old  = INSKILL_EVAL_DIR . 'fonts/OldNewspaperTypes.ttf';
if ( ! file_exists($font_book) || ! file_exists($font_old) ) {
    wp_die("Police manquante.");
}

// Helpers pour centrer et aligner le texte
function centerText($img, $font_path, $font_size, $angle, $text, $y, $color) {
    $bbox = imagettfbbox($font_size, $angle, $font_path, $text);
    $text_width = $bbox[2] - $bbox[0];
    $img_width = imagesx($img);
    $x = ($img_width - $text_width) / 2;
    imagettftext($img, $font_size, $angle, $x, $y, $color, $font_path, $text);
}
function leftText($img, $font_path, $font_size, $angle, $text, $x, $y, $color) {
    imagettftext($img, $font_size, $angle, $x, $y, $color, $font_path, $text);
}

// 7) Affichage des lignes (mêmes positions que votre version originale)
// Ligne 1
centerText($img, $font_book, 113, 0, "ATTESTATION", 730, $black);
// Ligne 2
centerText($img, $font_book, 113, 0, "DE FORMATION PROFESSIONNELLE", 917, $black);
// Ligne 3
centerText(
    $img, $font_old, 45, 0,
    "Ce document atteste que « " . mb_strtoupper($participant_nom) . " " . mb_strtoupper($participant_prenom) . " »",
    1072, $black
);
// Ligne 4
centerText(
    $img, $font_old, 45, 0,
    strtolower("a participé à " . $training_duration . " de formation sur la thématique"),
    1145, $black
);
// Ligne 5
centerText($img, $font_old, 45, 0, "« " . $module_title . " »", 1218, $black);
// Ligne 6
centerText(
    $img, $font_old, 45, 0,
    "organisée par INSKILL pour " . mb_strtoupper($client_company) . ".",
    1291, $black
);
// Ligne 7
centerText(
    $img, $font_old, 45, 0,
    "Ce document atteste que « " . mb_strtoupper($participant_nom) . " " . mb_strtoupper($participant_prenom) . " »",
    1422, $black
);
// Ligne 9
centerText($img, $font_old, 45, 0, "a bien assisté et achevé sa formation", 1495, $black);
// Ligne 10
centerText(
    $img, $font_old, 45, 0,
    "conformément aux attentes et exigence du cabinet INSKILL.",
    1568, $black
);
// Ligne 12
leftText($img, $font_old, 45, 0, "Le " . $creation_date_f, 440, 1800, $black);
// Ligne 13
leftText($img, $font_old, 29, 0, "MICHEL SOMAS", 910, 2190, $black);
// Ligne 14
centerText($img, $font_old, 29, 0, $trainer_name, 2190, $black);

// 8) Préparation du nom de fichier et sortie JPEG
$suffix = $response_id ? '' : '_NO-EVAL';
$filename = sanitize_file_name(
    "attestation_{$participant_nom}_{$participant_prenom}{$suffix}.jpg"
);

header("Content-Type: image/jpeg");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
imagejpeg($img);
imagedestroy($img);
exit;
