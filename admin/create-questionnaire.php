<?php
ob_start();
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistre le menu et les sous-menus pour InSkill Eval.
 */
function inskill_eval_create_questionnaire_menu() {
    add_menu_page(
        'InSkill Eval',          // Titre de la page
        'InSkill Eval',           // Titre du menu
        'manage_options',         // Capacité requise
        'inskill-eval',           // Slug du menu
        'inskill_eval_manage_questionnaires_page', // Callback pour la page par défaut (gestion)
        'dashicons-welcome-write-blog',
        6
    );

    add_submenu_page(
        'inskill-eval',
        'Créer un questionnaire',
        'Créer un questionnaire',
        'manage_options',
        'inskill-eval-create',
        'inskill_eval_create_questionnaire_page'
    );

    add_submenu_page(
        'inskill-eval',
        'Mes questionnaires',
        'Mes questionnaires',
        'manage_options',
        'inskill-eval-manage',
        'inskill_eval_manage_questionnaires_page'
    );

    add_submenu_page(
        'inskill-eval',
        'Consulter les résultats',
        'Consulter les résultats',
        'manage_options',
        'inskill-eval-results',
        'inskill_eval_view_results_page'
    );
}
add_action( 'admin_menu', 'inskill_eval_create_questionnaire_menu' );

/**
 * Affiche la page "Créer un questionnaire" et traite le formulaire d'insertion.
 */
function inskill_eval_create_questionnaire_page() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';

    // Traitement du formulaire (POST)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inskill_eval_create']) ) {
        check_admin_referer('inskill_eval_create_questionnaire');
        $client_company       = sanitize_text_field($_POST['client_company']);
        $module_title         = sanitize_text_field($_POST['module_title']);
        $group_designation    = sanitize_text_field($_POST['group_designation']);
        $trainer_name         = sanitize_text_field($_POST['trainer_name']);
        $training_dates       = sanitize_text_field($_POST['training_dates']);
        $training_duration    = sanitize_text_field($_POST['training_duration']);
        $training_location    = sanitize_text_field($_POST['training_location']);
        $attestation_formation = isset($_POST['attestation_formation']) ? 1 : 0;
        $inscription_ouverte   = isset($_POST['inscription_ouverte']) ? 1 : 0;

        $wpdb->insert(
            $table_questionnaires,
            array(
                'client_company'       => $client_company,
                'module_title'         => $module_title,
                'group_designation'    => $group_designation,
                'trainer_name'         => $trainer_name,
                'training_dates'       => $training_dates,
                'training_duration'    => $training_duration,
                'training_location'    => $training_location,
                'attestation_formation'=> $attestation_formation,
                'inscription_ouverte'  => $inscription_ouverte,
                'created_at'           => current_time('mysql')
            )
        );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }
    ?>
    <div class="wrap">
        <h1>Créer un questionnaire</h1>
        <form method="post">
            <?php wp_nonce_field('inskill_eval_create_questionnaire'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="client_company">Entreprise du client</label></th>
                    <td><input name="client_company" type="text" id="client_company" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="module_title">Intitulé du module</label></th>
                    <td><input name="module_title" type="text" id="module_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="group_designation">Désignation du Groupe</label></th>
                    <td><input name="group_designation" type="text" id="group_designation" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="trainer_name">Formateur (prénom NOM) </label></th>
                    <td><input name="trainer_name" type="text" id="trainer_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_dates">Date de formation J1 (JJ/MM/AAAA)</label></th>
                    <td><input name="training_dates" type="text" id="training_dates" class="regular-text" placeholder="JJ/MM/AAAA" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_duration">Durée de formation (Nb journées en lettres)</label></th>
                    <td><input name="training_duration" type="text" id="training_duration" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_location">Lieu de la formation J1</label></th>
                    <td><input name="training_location" type="text" id="training_location" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="attestation_formation">Attestation de formation</label></th>
                    <td>
                        <input type="checkbox" name="attestation_formation" id="attestation_formation" value="1">
                        <label for="attestation_formation">Oui</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="inscription_ouverte">Inscription ouverte</label></th>
                    <td>
                        <input type="checkbox" name="inscription_ouverte" id="inscription_ouverte" value="1">
                        <label for="inscription_ouverte">Oui</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Créer', 'primary', 'inskill_eval_create'); ?>
        </form>
    </div>
    <?php
}

/**
 * Affiche la page "Editer le questionnaire" et traite le formulaire de mise à jour.
 */
function inskill_eval_edit_questionnaire_page() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ( ! $id ) {
        echo "<div class='notice notice-error'><p>Identifiant non fourni.</p></div>";
        return;
    }
    $questionnaire = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_questionnaires WHERE id = %d", $id ) );
    if ( ! $questionnaire ) {
        echo "<div class='notice notice-error'><p>Questionnaire introuvable.</p></div>";
        return;
    }
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inskill_eval_update']) ) {
        check_admin_referer('inskill_eval_update_questionnaire');
        $client_company       = sanitize_text_field($_POST['client_company']);
        $module_title         = sanitize_text_field($_POST['module_title']);
        $group_designation    = sanitize_text_field($_POST['group_designation']);
        $trainer_name         = sanitize_text_field($_POST['trainer_name']);
        $training_dates       = sanitize_text_field($_POST['training_dates']);
        $training_duration    = sanitize_text_field($_POST['training_duration']);
        $training_location    = sanitize_text_field($_POST['training_location']);
        $attestation_formation = isset($_POST['attestation_formation']) ? 1 : 0;

        $wpdb->update(
            $table_questionnaires,
            array(
                'client_company'       => $client_company,
                'module_title'         => $module_title,
                'group_designation'    => $group_designation,
                'trainer_name'         => $trainer_name,
                'training_dates'       => $training_dates,
                'training_duration'    => $training_duration,
                'training_location'    => $training_location,
                'attestation_formation'=> $attestation_formation
            ),
            array('id' => $id)
        );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }
    ?>
    <div class="wrap">
        <h1>Editer le questionnaire</h1>
        <form method="post">
            <?php wp_nonce_field('inskill_eval_update_questionnaire'); ?>
            <input type="hidden" name="questionnaire_id" value="<?php echo esc_attr($questionnaire->id); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="client_company">Entreprise du client</label></th>
                    <td><input name="client_company" type="text" id="client_company" class="regular-text" value="<?php echo esc_attr($questionnaire->client_company); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="module_title">Intitulé du module</label></th>
                    <td><input name="module_title" type="text" id="module_title" class="regular-text" value="<?php echo esc_attr($questionnaire->module_title); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="group_designation">Désignation du Groupe</label></th>
                    <td><input name="group_designation" type="text" id="group_designation" class="regular-text" value="<?php echo esc_attr($questionnaire->group_designation); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="trainer_name">Nom du Formateur</label></th>
                    <td><input name="trainer_name" type="text" id="trainer_name" class="regular-text" value="<?php echo esc_attr($questionnaire->trainer_name); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_dates">Date de formation J1 (JJ/MM/AAAA)</label></th>
                    <td><input name="training_dates" type="text" id="training_dates" class="regular-text" value="<?php echo esc_attr($questionnaire->training_dates); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_duration">Durée de formation (Nb journées en lettres)</label></th>
                    <td><input name="training_duration" type="text" id="training_duration" class="regular-text" value="<?php echo esc_attr($questionnaire->training_duration); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="training_location">Lieu de la formation J1</label></th>
                    <td><input name="training_location" type="text" id="training_location" class="regular-text" value="<?php echo esc_attr($questionnaire->training_location); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="attestation_formation">Attestation de formation</label></th>
                    <td>
                        <input type="checkbox" name="attestation_formation" id="attestation_formation" value="1" <?php checked($questionnaire->attestation_formation, 1); ?>>
                        <label for="attestation_formation">Oui</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Mettre à jour', 'primary', 'inskill_eval_update'); ?>
            <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage'); ?>" class="button">Annuler</a>
        </form>
    </div>
    <?php
}
