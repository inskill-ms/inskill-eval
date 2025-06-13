<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Affiche le questionnaire.
 */
function inskill_eval_display_survey() {
    if ( ! isset($_GET['inskill_eval_survey']) || empty($_GET['inskill_eval_survey']) ) {
        return "Aucun questionnaire spécifié.";
    }

    $questionnaire_id = intval($_GET['inskill_eval_survey']);
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $questionnaire = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_questionnaires WHERE id = %d", $questionnaire_id ) );

    if ( ! $questionnaire ) {
        return "Questionnaire introuvable.";
    }

    // Traitement du formulaire lors de la soumission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inskill_eval_survey_nonce']) && wp_verify_nonce($_POST['inskill_eval_survey_nonce'], 'inskill_eval_survey')) {
        $submitted_nom = sanitize_text_field($_POST['participant_nom']);
        $submitted_prenom = sanitize_text_field($_POST['participant_prenom']);
        $submitted_email = sanitize_email($_POST['participant_email']);

        $table_learners = $wpdb->prefix . 'inskill_eval_learners';
        $learner = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_learners WHERE questionnaire_id = %d AND LOWER(participant_nom) = %s AND LOWER(participant_prenom) = %s AND LOWER(participant_email) = %s",
            $questionnaire_id,
            strtolower($submitted_nom),
            strtolower($submitted_prenom),
            strtolower($submitted_email)
        ));

        if ( ! $learner ) {
            return '<div class="error-message" style="text-align:center; margin-top:20px; color:red;">
                Les informations saisies ne correspondent pas à votre inscription. Veuillez vérifier vos données.<br>
                <a href="javascript:history.back()" class="button" style="padding:10px 20px; background:#000; color:#fff; text-decoration:none; margin-top:10px; display:inline-block;">Revenir au questionnaire</a>
            </div>';
        }

        $data = array(
            'questionnaire_id' => $questionnaire_id,
            'participant_nom' => $submitted_nom,
            'participant_prenom' => $submitted_prenom,
            'participant_email' => $submitted_email,
            'formation_contenu' => intval($_POST['formation_contenu']),
            'commentaire_formation_contenu' => sanitize_textarea_field($_POST['commentaire_formation_contenu']),
            'formation_dosage' => intval($_POST['formation_dosage']),
            'commentaire_formation_dosage' => sanitize_textarea_field($_POST['commentaire_formation_dosage']),
            'formation_duree' => intval($_POST['formation_duree']),
            'commentaire_formation_duree' => sanitize_textarea_field($_POST['commentaire_formation_duree']),
            'formation_presentations' => intval($_POST['formation_presentations']),
            'commentaire_formation_presentations' => sanitize_textarea_field($_POST['commentaire_formation_presentations']),
            'formation_documents' => intval($_POST['formation_documents']),
            'commentaire_formation_documents' => sanitize_textarea_field($_POST['commentaire_formation_documents']),
            'formation_attentes' => intval($_POST['formation_attentes']),
            'commentaire_formation_attentes' => sanitize_textarea_field($_POST['commentaire_formation_attentes']),
            'animateur_sympathie' => intval($_POST['animateur_sympathie']),
            'commentaire_animateur_sympathie' => sanitize_textarea_field($_POST['commentaire_animateur_sympathie']),
            'animateur_dynamisme' => intval($_POST['animateur_dynamisme']),
            'commentaire_animateur_dynamisme' => sanitize_textarea_field($_POST['commentaire_animateur_dynamisme']),
            'animateur_clarte_voix' => intval($_POST['animateur_clarte_voix']),
            'commentaire_animateur_clarte_voix' => sanitize_textarea_field($_POST['commentaire_animateur_clarte_voix']),
            'animateur_ecoute' => intval($_POST['animateur_ecoute']),
            'commentaire_animateur_ecoute' => sanitize_textarea_field($_POST['commentaire_animateur_ecoute']),
            'animateur_maitrise' => intval($_POST['animateur_maitrise']),
            'commentaire_animateur_maitrise' => sanitize_textarea_field($_POST['commentaire_animateur_maitrise']),
            'animateur_explicatifs' => intval($_POST['animateur_explicatifs']),
            'commentaire_animateur_explicatifs' => sanitize_textarea_field($_POST['commentaire_animateur_explicatifs']),
            'animateur_interactivite' => intval($_POST['animateur_interactivite']),
            'commentaire_animateur_interactivite' => sanitize_textarea_field($_POST['commentaire_animateur_interactivite']),
            'remarques_suggestions' => sanitize_textarea_field($_POST['remarques_suggestions']),
            'submitted_at' => current_time('mysql')
        );

        $table_responses = $wpdb->prefix . 'inskill_eval_responses';
        $wpdb->insert( $table_responses, $data );
        $response_id = $wpdb->insert_id;

        if ($questionnaire->attestation_formation == 1) {
            $download_url = site_url('/wp-content/plugins/inskill-eval/generate-attestation-image.php?response_id=' . $response_id);
            $download_button = '<a href="javascript:void(0)" onclick="downloadAttestation(\'' . $download_url . '\')" class="button" style="padding:10px 20px; background:#000; color:#fff; text-decoration:none;">Télécharger l\'attestation de formation professionnelle</a>';
            $quit_button = '<a href="https://inskill.net" class="button" id="quitButton" style="padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; display:none; margin-top:10px;">Quitter vers l\'accueil</a>';
            $script = '<script>
                function downloadAttestation(url) {
                    var iframe = document.createElement("iframe");
                    iframe.style.display = "none";
                    iframe.src = url;
                    document.body.appendChild(iframe);
                    document.getElementById("quitButton").style.display = "inline-block";
                }
            </script>';
            
            return '<div class="thank-you-message" style="text-align:center; margin-top:20px;">
                <p>Nous vous remercions chaleureusement d\'avoir pris le temps de répondre à ce questionnaire.<br>
                Vos informations seront traitées en toute confidentialité.</p>'
                . $download_button . '<br>' . $quit_button . $script .
                '</div>';
        } else {
            return '<div class="thank-you-message" style="text-align:center; margin-top:20px;">
                <p>Nous vous remercions chaleureusement d\'avoir pris le temps de répondre à ce questionnaire.<br>
                Vos informations seront traitées en toute confidentialité.</p>
                <a href="https://inskill.net" class="button" style="padding:10px 20px; background:#000; color:#fff; text-decoration:none;">Accueil</a>
            </div>';
        }
    }

    $survey = new stdClass();
    $survey->module_title = $questionnaire->module_title;
    $survey->group_designation = $questionnaire->group_designation;
    $survey->trainer_name = $questionnaire->trainer_name;
    $survey->training_dates = $questionnaire->training_dates;
    $survey->training_location = $questionnaire->training_location;

    ob_start();
    include INSKILL_EVAL_DIR . 'survey-form.php';
    return ob_get_clean();
}
add_shortcode( 'inskill_eval_survey', 'inskill_eval_display_survey' );

/**
 * Affiche le formulaire d'inscription.
 */
function inskill_eval_display_subscription() {
    if ( ! isset($_GET['inskill_eval_learners']) || empty($_GET['inskill_eval_learners']) ) {
        return "Aucun questionnaire spécifié pour l'inscription.";
    }

    $questionnaire_id = intval($_GET['inskill_eval_learners']);
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $questionnaire = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_questionnaires WHERE id = %d", $questionnaire_id ) );

    if ( ! $questionnaire ) {
        return "Questionnaire introuvable.";
    }
    
    // Vérifier si les inscriptions sont ouvertes
    if ( $questionnaire->inscription_ouverte != 1 ) {
        return '<div class="error-message" style="text-align:center; margin-top:20px; color:red;">Les inscriptions pour cette formation sont fermées.</div>';
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inskill_eval_subscription_nonce']) && wp_verify_nonce($_POST['inskill_eval_subscription_nonce'], 'inskill_eval_subscription')) {
        $participant_nom = sanitize_text_field($_POST['participant_nom']);
        $participant_prenom = sanitize_text_field($_POST['participant_prenom']);
        $participant_email = sanitize_email($_POST['participant_email']);

        $table_learners = $wpdb->prefix . 'inskill_eval_learners';
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_learners WHERE questionnaire_id = %d AND LOWER(participant_nom) = %s AND LOWER(participant_prenom) = %s AND LOWER(participant_email) = %s",
            $questionnaire_id,
            strtolower($participant_nom),
            strtolower($participant_prenom),
            strtolower($participant_email)
        ));

        if ( $existing ) {
            return '<div class="error-message" style="text-align:center; margin-top:20px; color:red;">
                Vous êtes déjà inscrit avec ces informations.
            </div>';
        } else {
            $wpdb->insert(
                $table_learners,
                array(
                    'questionnaire_id' => $questionnaire_id,
                    'participant_nom' => $participant_nom,
                    'participant_prenom' => $participant_prenom,
                    'participant_email' => $participant_email,
                    'created_at' => current_time('mysql')
                )
            );
            return '<div class="success-message" style="text-align:center; margin-top:20px; color:green;">
                Inscription réussie. Vous pouvez fermer cette page. Bonne formation !.
            </div>';
        }
    }

    $subscription = new stdClass();
    $subscription->module_title = $questionnaire->module_title;
    $subscription->group_designation = $questionnaire->group_designation;
    $subscription->trainer_name = $questionnaire->trainer_name;
    $subscription->training_dates = $questionnaire->training_dates;
    $subscription->training_location = $questionnaire->training_location;

    ob_start();
    include INSKILL_EVAL_DIR . 'list-learners-form.php';
    return ob_get_clean();
}
add_shortcode( 'inskill_eval_subscription', 'inskill_eval_display_subscription' );
