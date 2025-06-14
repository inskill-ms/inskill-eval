<?php
/*
Plugin Name: InSkill Eval
Description: Plugin pour évaluer la satisfaction des participants en formation.
Version: 1.1
Author: Michel SOMAS (InSkill)
*/

// Sécurité
if ( ! defined( 'ABSPATH' ) ) exit;

// Constantes
define( 'INSKILL_EVAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'INSKILL_EVAL_URL', plugin_dir_url( __FILE__ ) );

// Installation / shortcodes / réglages
include_once INSKILL_EVAL_DIR . 'inskill-eval-install.php';
include_once INSKILL_EVAL_DIR . 'shortcodes.php';
include_once INSKILL_EVAL_DIR . 'settings.php';

/**
 * 1) Handler pour générer le ZIP d’attestations
 */
add_action( 'admin_post_download_attestations', 'inskill_eval_download_attestations' );
function inskill_eval_download_attestations() {
    // Sécurité
    if ( ! current_user_can('manage_options') ) {
        wp_die('Vous n’avez pas la permission.');
    }

    // Récupérer l’ID du questionnaire
    $qid = isset($_GET['questionnaire_id']) ? intval($_GET['questionnaire_id']) : 0;
    if ( ! $qid ) {
        wp_die('Questionnaire non spécifié.');
    }

    global $wpdb;
    $tbl_learners  = $wpdb->prefix . 'inskill_eval_learners';
    $tbl_responses = $wpdb->prefix . 'inskill_eval_responses';

    // On boucle sur les apprenants inscrits
    $learners = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tbl_learners WHERE questionnaire_id = %d",
            $qid
        )
    );
    if ( empty($learners) ) {
        wp_die('Aucun participant inscrit pour ce questionnaire.');
    }

    // Préparation du ZIP
    $zip     = new ZipArchive();
    $tmp_zip = wp_tempnam( 'attestations_q' . $qid . '.zip' );
    if ( $zip->open( $tmp_zip, ZipArchive::CREATE ) !== TRUE ) {
        wp_die('Impossible de créer l’archive ZIP.');
    }

    foreach ( $learners as $L ) {
        // Recherche de la réponse (validation du questionnaire)
        $resp = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $tbl_responses WHERE questionnaire_id = %d AND LOWER(participant_email) = %s",
            $qid,
            strtolower( $L->participant_email )
        ) );

        // Construire les paramètres pour l'appel
        if ( $resp ) {
            // Participant ayant validé
            $param = 'response_id=' . $resp;
        } else {
            // Participant inscrit mais non validé
            $param = 'learner_id=' . $L->id;
        }

        // URL publique de génération de l'attestation (JPG)
        $url = INSKILL_EVAL_URL . 'generate-attestation-image.php?' . $param;

        // Appel HTTP pour récupérer le flux JPEG
        $result = wp_remote_get( $url, array( 'timeout' => 60 ) );
        if ( is_wp_error($result) || 200 !== wp_remote_retrieve_response_code($result) ) {
            continue;
        }
        $img = wp_remote_retrieve_body( $result );
        if ( empty($img) ) {
            continue;
        }

        // Nom de fichier
        $suffix = $resp ? '' : '_NO-EVAL';
        $filename = sanitize_file_name(
            "attestation_{$L->participant_nom}_{$L->participant_prenom}{$suffix}.jpg"
        );
        $zip->addFromString( $filename, $img );
    }

    // Ferme le ZIP
    $zip->close();

    // Envoi des headers et du contenu
    if ( headers_sent() ) {
        wp_die('Les en-têtes ont déjà été envoyés, impossible d’envoyer le ZIP.');
    }
    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="attestations_q' . $qid . '.zip"' );
    header( 'Content-Length: ' . filesize( $tmp_zip ) );
    readfile( $tmp_zip );
    @unlink( $tmp_zip );
    exit;
}

/**
 * 2) Chargement des pages d’admin
 */
if ( is_admin() ) {
    add_action( 'admin_menu', 'inskill_eval_admin_includes', 5 );
}
function inskill_eval_admin_includes() {
    include_once INSKILL_EVAL_DIR . 'admin/create-questionnaire.php';
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if ( $page === 'inskill-eval-manage' ) {
        include_once INSKILL_EVAL_DIR . 'admin/manage-questionnaires.php';
    }
    elseif ( $page === 'inskill-eval-results' ) {
        include_once INSKILL_EVAL_DIR . 'admin/view-results.php';
    }
}

// Activation / désactivation
register_activation_hook( __FILE__, 'inskill_eval_install' );
register_deactivation_hook( __FILE__, 'inskill_eval_uninstall' );
