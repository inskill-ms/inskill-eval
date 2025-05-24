<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function inskill_eval_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $table_responses = $wpdb->prefix . 'inskill_eval_responses';
    $table_learners = $wpdb->prefix . 'inskill_eval_learners';

    $sql1 = "CREATE TABLE $table_questionnaires (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        client_company varchar(255) NOT NULL,
        module_title varchar(255) NOT NULL,
        group_designation varchar(255) NOT NULL,
        trainer_name varchar(255) NOT NULL,
        training_dates varchar(50) NOT NULL,
        training_duration varchar(255) NOT NULL,
        training_location varchar(255) NOT NULL,
        attestation_formation tinyint(1) NOT NULL DEFAULT 0,
        inscription_ouverte tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql2 = "CREATE TABLE $table_responses (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        questionnaire_id mediumint(9) NOT NULL,
        participant_nom varchar(255) NOT NULL,
        participant_prenom varchar(255) NOT NULL,
        participant_email varchar(255) NOT NULL,
        formation_contenu tinyint(1) NOT NULL,
        commentaire_formation_contenu text NULL,
        formation_dosage tinyint(1) NOT NULL,
        commentaire_formation_dosage text NULL,
        formation_duree tinyint(1) NOT NULL,
        commentaire_formation_duree text NULL,
        formation_presentations tinyint(1) NOT NULL,
        commentaire_formation_presentations text NULL,
        formation_documents tinyint(1) NOT NULL,
        commentaire_formation_documents text NULL,
        formation_attentes tinyint(1) NOT NULL,
        commentaire_formation_attentes text NULL,
        animateur_sympathie tinyint(1) NOT NULL,
        commentaire_animateur_sympathie text NULL,
        animateur_dynamisme tinyint(1) NOT NULL,
        commentaire_animateur_dynamisme text NULL,
        animateur_clarte_voix tinyint(1) NOT NULL,
        commentaire_animateur_clarte_voix text NULL,
        animateur_ecoute tinyint(1) NOT NULL,
        commentaire_animateur_ecoute text NULL,
        animateur_maitrise tinyint(1) NOT NULL,
        commentaire_animateur_maitrise text NULL,
        animateur_explicatifs tinyint(1) NOT NULL,
        commentaire_animateur_explicatifs text NULL,
        animateur_interactivite tinyint(1) NOT NULL,
        commentaire_animateur_interactivite text NULL,
        remarques_suggestions text NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY questionnaire_id (questionnaire_id)
    ) $charset_collate;";

    $sql3 = "CREATE TABLE $table_learners (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        questionnaire_id mediumint(9) NOT NULL,
        participant_nom varchar(255) NOT NULL,
        participant_prenom varchar(255) NOT NULL,
        participant_email varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_learner (questionnaire_id, participant_nom, participant_prenom, participant_email)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql1 );
    dbDelta( $sql2 );
    dbDelta( $sql3 );
}

function inskill_eval_uninstall() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $table_responses = $wpdb->prefix . 'inskill_eval_responses';
    $table_learners = $wpdb->prefix . 'inskill_eval_learners';

    $wpdb->query( "DROP TABLE IF EXISTS $table_questionnaires" );
    $wpdb->query( "DROP TABLE IF EXISTS $table_responses" );
    $wpdb->query( "DROP TABLE IF EXISTS $table_learners" );
}
