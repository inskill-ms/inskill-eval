<?php
ob_start();
// Afficher les erreurs directement pour ce fichier (temporaire)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';

// Suppression d'un participant (learner) si demandé via GET
if ( isset($_GET['action']) && $_GET['action'] == 'delete_learner' && isset($_GET['learner_id']) && isset($_GET['questionnaire_id']) ) {
    $learner_id = intval($_GET['learner_id']);
    $wpdb->delete( $wpdb->prefix . 'inskill_eval_learners', array('id' => $learner_id) );
    if ( isset($_GET['in_popup']) && $_GET['in_popup'] == '1' ) {
         echo '<html><head><meta charset="UTF-8"><script>
         window.opener.location.reload();
         window.close();
         </script></head><body>Suppression effectuée.</body></html>';
         exit;
    } else {
         wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
         exit;
    }
}

// Ancien traitement d'ajout manuel d'un participant (non utilisé si on utilise le lien d'inscription)
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_learner']) && isset($_POST['questionnaire_id']) ) {
    $questionnaire_id = intval($_POST['questionnaire_id']);
    $participant_nom = sanitize_text_field($_POST['participant_nom']);
    $participant_prenom = sanitize_text_field($_POST['participant_prenom']);
    $participant_email = sanitize_email($_POST['participant_email']);

    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}inskill_eval_learners WHERE questionnaire_id = %d AND LOWER(participant_nom) = %s AND LOWER(participant_prenom) = %s AND LOWER(participant_email) = %s",
        $questionnaire_id,
        strtolower($participant_nom),
        strtolower($participant_prenom),
        strtolower($participant_email)
    ));

    if ( ! $existing ) {
        $wpdb->insert(
            $wpdb->prefix . 'inskill_eval_learners',
            array(
                'questionnaire_id' => $questionnaire_id,
                'participant_nom' => $participant_nom,
                'participant_prenom' => $participant_prenom,
                'participant_email' => $participant_email,
                'created_at' => current_time('mysql')
            )
        );
    }
    wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
    exit;
}

function inskill_eval_manage_questionnaires_page() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';

    // Traitement de la suppression d'un questionnaire
    if ( isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) ) {
        $id = intval($_GET['id']);
        $wpdb->delete( $table_questionnaires, array('id' => $id) );
        $table_responses = $wpdb->prefix . 'inskill_eval_responses';
        $table_learners = $wpdb->prefix . 'inskill_eval_learners';
        $wpdb->delete( $table_responses, array('questionnaire_id' => $id) );
        $wpdb->delete( $table_learners, array('questionnaire_id' => $id) );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }

    // Traitement de la mise à jour d'un questionnaire
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inskill_eval_update']) ) {
        check_admin_referer('inskill_eval_update_questionnaire');
        $id                = intval($_POST['questionnaire_id']);
        $client_company    = sanitize_text_field($_POST['client_company']);
        $module_title      = sanitize_text_field($_POST['module_title']);
        $group_designation = sanitize_text_field($_POST['group_designation']);
        $trainer_name      = sanitize_text_field($_POST['trainer_name']);
        $training_dates    = sanitize_text_field($_POST['training_dates']);
        $training_duration = sanitize_text_field($_POST['training_duration']);
        $training_location = sanitize_text_field($_POST['training_location']);
        $attestation_formation = isset($_POST['attestation_formation']) ? 1 : 0;
        $inscription_ouverte   = isset($_POST['inscription_ouverte']) ? 1 : 0;

        $wpdb->update(
            $table_questionnaires,
            array(
                'client_company'    => $client_company,
                'module_title'      => $module_title,
                'group_designation' => $group_designation,
                'trainer_name'      => $trainer_name,
                'training_dates'    => $training_dates,
                'training_duration' => $training_duration,
                'training_location' => $training_location,
                'attestation_formation' => $attestation_formation,
                'inscription_ouverte' => $inscription_ouverte
            ),
            array('id' => $id)
        );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }

    // Affichage du formulaire d'édition si demandé
    if ( isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id']) ) {
        $id = intval($_GET['id']);
        $questionnaire = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_questionnaires WHERE id = %d", $id ) );
        if ( ! $questionnaire ) {
            echo "<div class='notice notice-error'><p>Questionnaire introuvable.</p></div>";
            return;
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
                    <tr>
                        <th scope="row"><label for="inscription_ouverte">Inscription ouverte</label></th>
                        <td>
                            <input type="checkbox" name="inscription_ouverte" id="inscription_ouverte" value="1" <?php checked($questionnaire->inscription_ouverte, 1); ?>>
                            <label for="inscription_ouverte">Oui</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Mettre à jour', 'primary', 'inskill_eval_update'); ?>
                <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage'); ?>" class="button">Annuler</a>
            </form>
        </div>
        <?php
        return;
    }

    /* ---------- Début ajout des filtres ---------- */
    // Récupération des valeurs distinctes pour les filtres
    $modules   = $wpdb->get_col("SELECT DISTINCT module_title FROM $table_questionnaires");
    $groups    = $wpdb->get_col("SELECT DISTINCT group_designation FROM $table_questionnaires");
    $trainers  = $wpdb->get_col("SELECT DISTINCT trainer_name FROM $table_questionnaires");
    $dates     = $wpdb->get_col("SELECT DISTINCT training_dates FROM $table_questionnaires");
    $locations = $wpdb->get_col("SELECT DISTINCT training_location FROM $table_questionnaires");

    // Récupération des filtres soumis (ou "Tous" par défaut)
    $filter_module   = isset($_POST['filter_module']) ? sanitize_text_field($_POST['filter_module']) : 'Tous';
    $filter_group    = isset($_POST['filter_group']) ? sanitize_text_field($_POST['filter_group']) : 'Tous';
    $filter_trainer  = isset($_POST['filter_trainer']) ? sanitize_text_field($_POST['filter_trainer']) : 'Tous';
    $filter_date     = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : 'Tous';
    $filter_location = isset($_POST['filter_location']) ? sanitize_text_field($_POST['filter_location']) : 'Tous';

    // Construction de la clause WHERE
    $where = "WHERE 1=1";
    if($filter_module !== 'Tous') {
        $where .= $wpdb->prepare(" AND module_title = %s", $filter_module);
    }
    if($filter_group !== 'Tous') {
        $where .= $wpdb->prepare(" AND group_designation = %s", $filter_group);
    }
    if($filter_trainer !== 'Tous') {
        $where .= $wpdb->prepare(" AND trainer_name = %s", $filter_trainer);
    }
    if($filter_date !== 'Tous') {
        $where .= $wpdb->prepare(" AND training_dates = %s", $filter_date);
    }
    if($filter_location !== 'Tous') {
        $where .= $wpdb->prepare(" AND training_location = %s", $filter_location);
    }
    /* ---------- Fin ajout des filtres ---------- */

    // Récupération des URLs depuis les réglages
    $survey_page_url = get_option('inskill_eval_survey_page_url', site_url('/index.php/eval-nv1/'));
    $subscription_page_url = get_option('inskill_eval_subscription_page_url', site_url('/index.php/eval-subscription/'));

    // Récupération des questionnaires avec les filtres appliqués
    $questionnaires = $wpdb->get_results( "SELECT * FROM $table_questionnaires $where ORDER BY created_at DESC" );
    ?>
    <div class="wrap">
        <h1>Mes questionnaires</h1>
        <!-- Formulaire des filtres -->
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="filter_module">Module</label></th>
                    <td>
                        <select name="filter_module" id="filter_module">
                            <option>Tous</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo esc_attr($module); ?>" <?php selected($filter_module, $module); ?>>
                                    <?php echo esc_html($module); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_group">Groupe</label></th>
                    <td>
                        <select name="filter_group" id="filter_group">
                            <option>Tous</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group); ?>" <?php selected($filter_group, $group); ?>>
                                    <?php echo esc_html($group); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_trainer">Formateur</label></th>
                    <td>
                        <select name="filter_trainer" id="filter_trainer">
                            <option>Tous</option>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo esc_attr($trainer); ?>" <?php selected($filter_trainer, $trainer); ?>>
                                    <?php echo esc_html($trainer); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_date">Date</label></th>
                    <td>
                        <select name="filter_date" id="filter_date">
                            <option>Tous</option>
                            <?php foreach ($dates as $date): ?>
                                <option value="<?php echo esc_attr($date); ?>" <?php selected($filter_date, $date); ?>>
                                    <?php echo esc_html($date); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_location">Lieu</label></th>
                    <td>
                        <select name="filter_location" id="filter_location">
                            <option>Tous</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location); ?>" <?php selected($filter_location, $location); ?>>
                                    <?php echo esc_html($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Filtrer'); ?>
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Module</th>
                    <th>Groupe</th>
                    <th>Formateur</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Attestation</th>
                    <th>Inscription</th>
                    <th>URL du questionnaire</th>
                    <th>URL d'inscription</th>
                    <th>Auto-inscription</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $questionnaires as $q ) : 
                        $questionnaire_url = trailingslashit($survey_page_url) . '?inskill_eval_survey=' . $q->id;
                        $inscription_url   = trailingslashit($subscription_page_url) . '?inskill_eval_learners=' . $q->id;
                ?>
                    <tr>
                        <td><?php echo esc_html( $q->id ); ?></td>
                        <td><?php echo esc_html( $q->module_title ); ?></td>
                        <td><?php echo esc_html( $q->group_designation ); ?></td>
                        <td><?php echo esc_html( $q->trainer_name ); ?></td>
                        <td><?php echo esc_html( $q->training_dates ); ?></td>
                        <td><?php echo esc_html( $q->training_location ); ?></td>
                        <td><?php echo ($q->attestation_formation == 1) ? 'Oui' : 'Non'; ?></td>
                        <td><?php echo ($q->inscription_ouverte == 1) ? 'Ouvertes' : 'Fermées'; ?></td>
                        <td>
                            <a href="<?php echo esc_url($questionnaire_url); ?>" target="_blank"><?php echo esc_html($questionnaire_url); ?></a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($inscription_url); ?>" target="_blank"><?php echo esc_html($inscription_url); ?></a>
                        </td>
                        <td>
                            <button type="button" class="button" onclick="openLearnersPopup('learners_<?php echo $q->id; ?>')">Liste</button>
                            <div id="learners_<?php echo $q->id; ?>" style="display:none;">
                                <?php
                                $learners = $wpdb->get_results( $wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}inskill_eval_learners WHERE questionnaire_id = %d",
                                    $q->id
                                ) );
                                if ( $learners ) {
                                    ?>
                                    <table class="widefat">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Prénom</th>
                                                <th>Email</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $learners as $learner ) : ?>
                                                <tr>
                                                    <td><?php echo esc_html( $learner->participant_nom ); ?></td>
                                                    <td><?php echo esc_html( $learner->participant_prenom ); ?></td>
                                                    <td><?php echo esc_html( $learner->participant_email ); ?></td>
                                                    <td>
                                                        <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage&action=delete_learner&learner_id=' . $learner->id . '&questionnaire_id=' . $q->id . '&in_popup=1'); ?>" class="button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce participant ?');" target="_top">Supprimer</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php
                                } else {
                                    echo "<p>Aucun participant inscrit.</p>";
                                }
                                ?>
                                <h4><strong>Ajouter un participant</strong></h4>
                                <p>
                                    <a href="<?php echo esc_url($inscription_url . '#bottom'); ?>" class="button" onclick="window.open('<?php echo esc_url($inscription_url . '#bottom'); ?>', 'popup_inscription', 'width=600,height=600'); return false;">Cliquer ici pour accéder à la page d'inscription</a>
                                </p>
                            </div>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage&action=edit&id=' . $q->id); ?>" class="button">Editer</a>
                            <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage&action=delete&id=' . $q->id); ?>" class="button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce questionnaire ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    function openLearnersPopup(id) {
        var content = document.getElementById(id).innerHTML;
        var popup = window.open('', 'Liste des participants', 'width=800,height=600,scrollbars=yes');
        var html = '<html><head><title>Liste des participants</title>' +
                   '<link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">' +
                   '<style>' +
                   'body { margin: 0; padding: 20px; background: #f3f4f6; font-family: sans-serif; }' +
                   '.popup-container { max-width: 760px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }' +
                   '.popup-container h4 { margin-top: 20px; }' +
                   '.popup-container table { width: 100%; border-collapse: collapse; margin-top: 10px; }' +
                   '.popup-container table, .popup-container th, .popup-container td { border: 1px solid #e5e7eb; }' +
                   '.popup-container th, .popup-container td { padding: 8px; text-align: left; }' +
                   '</style>' +
                   '</head><body>' +
                   '<div class="popup-container">' + content + '</div>' +
                   '</body></html>';
        popup.document.write(html);
        popup.document.close();
    }
    </script>
    <?php
}
