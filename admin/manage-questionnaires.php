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
if (
    isset($_GET['action']) && $_GET['action'] === 'delete_learner' &&
    isset($_GET['learner_id']) && isset($_GET['questionnaire_id'])
) {
    $learner_id = intval($_GET['learner_id']);
    $wpdb->delete( "{$wpdb->prefix}inskill_eval_learners", [ 'id' => $learner_id ] );
    if ( isset($_GET['in_popup']) && $_GET['in_popup'] === '1' ) {
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
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_learner']) &&
    isset($_POST['questionnaire_id'])
) {
    $qid = intval($_POST['questionnaire_id']);
    $nom = sanitize_text_field($_POST['participant_nom']);
    $prenom = sanitize_text_field($_POST['participant_prenom']);
    $email = sanitize_email($_POST['participant_email']);

    $existing = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}inskill_eval_learners
         WHERE questionnaire_id = %d
           AND LOWER(participant_nom) = %s
           AND LOWER(participant_prenom) = %s
           AND LOWER(participant_email) = %s",
        $qid,
        strtolower($nom),
        strtolower($prenom),
        strtolower($email)
    ) );

    if ( ! $existing ) {
        $wpdb->insert(
            "{$wpdb->prefix}inskill_eval_learners",
            [
                'questionnaire_id' => $qid,
                'participant_nom'  => $nom,
                'participant_prenom'=> $prenom,
                'participant_email'=> $email,
                'created_at'       => current_time('mysql'),
            ]
        );
    }
    wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
    exit;
}

function inskill_eval_manage_questionnaires_page() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';

    // SUPPRESSION D’UN QUESTIONNAIRE
    if (
        isset($_GET['action']) && $_GET['action'] === 'delete' &&
        isset($_GET['id'])
    ) {
        $id = intval($_GET['id']);
        $wpdb->delete( $table_questionnaires, [ 'id' => $id ] );
        $wpdb->delete( "{$wpdb->prefix}inskill_eval_responses",   [ 'questionnaire_id' => $id ] );
        $wpdb->delete( "{$wpdb->prefix}inskill_eval_learners",    [ 'questionnaire_id' => $id ] );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }

    // MISE À JOUR D’UN QUESTIONNAIRE
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['inskill_eval_update'])
    ) {
        check_admin_referer('inskill_eval_update_questionnaire');
        $id                   = intval($_POST['questionnaire_id']);
        $client_company       = sanitize_text_field($_POST['client_company']);
        $module_title         = sanitize_text_field($_POST['module_title']);
        $group_designation    = sanitize_text_field($_POST['group_designation']);
        $trainer_name         = sanitize_text_field($_POST['trainer_name']);
        $training_dates       = sanitize_text_field($_POST['training_dates']);
        $training_duration    = sanitize_text_field($_POST['training_duration']);
        $training_location    = sanitize_text_field($_POST['training_location']);
        $attestation_formation= isset($_POST['attestation_formation']) ? 1 : 0;
        $inscription_ouverte  = isset($_POST['inscription_ouverte'])   ? 1 : 0;

        $wpdb->update(
            $table_questionnaires,
            [
                'client_company'        => $client_company,
                'module_title'          => $module_title,
                'group_designation'     => $group_designation,
                'trainer_name'          => $trainer_name,
                'training_dates'        => $training_dates,
                'training_duration'     => $training_duration,
                'training_location'     => $training_location,
                'attestation_formation' => $attestation_formation,
                'inscription_ouverte'   => $inscription_ouverte
            ],
            [ 'id' => $id ]
        );
        wp_redirect( admin_url('admin.php?page=inskill-eval-manage') );
        exit;
    }

    // FORMULAIRE D’ÉDITION D’UN QUESTIONNAIRE
    if (
        isset($_GET['action']) && $_GET['action'] === 'edit' &&
        isset($_GET['id'])
    ) {
        $id = intval($_GET['id']);
        $q  = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_questionnaires WHERE id = %d",
            $id
        ) );
        if ( ! $q ) {
            echo "<div class='notice notice-error'><p>Questionnaire introuvable.</p></div>";
            return;
        }
        ?>
        <div class="wrap">
          <h1>Editer le questionnaire</h1>
          <form method="post">
            <?php wp_nonce_field('inskill_eval_update_questionnaire'); ?>
            <input type="hidden" name="questionnaire_id" value="<?php echo esc_attr($q->id); ?>">
            <table class="form-table">
              <tr>
                <th><label for="client_company">Entreprise du client</label></th>
                <td><input name="client_company" type="text" id="client_company" class="regular-text" value="<?php echo esc_attr($q->client_company); ?>" required></td>
              </tr>
              <tr>
                <th><label for="module_title">Intitulé du module</label></th>
                <td><input name="module_title" type="text" id="module_title" class="regular-text" value="<?php echo esc_attr($q->module_title); ?>" required></td>
              </tr>
              <tr>
                <th><label for="group_designation">Désignation du Groupe</label></th>
                <td><input name="group_designation" type="text" id="group_designation" class="regular-text" value="<?php echo esc_attr($q->group_designation); ?>" required></td>
              </tr>
              <tr>
                <th><label for="trainer_name">Nom du Formateur</label></th>
                <td><input name="trainer_name" type="text" id="trainer_name" class="regular-text" value="<?php echo esc_attr($q->trainer_name); ?>" required></td>
              </tr>
              <tr>
                <th><label for="training_dates">Date J1 (JJ/MM/AAAA)</label></th>
                <td><input name="training_dates" type="text" id="training_dates" class="regular-text" value="<?php echo esc_attr($q->training_dates); ?>" required></td>
              </tr>
              <tr>
                <th><label for="training_duration">Durée (en lettres)</label></th>
                <td><input name="training_duration" type="text" id="training_duration" class="regular-text" value="<?php echo esc_attr($q->training_duration); ?>" required></td>
              </tr>
              <tr>
                <th><label for="training_location">Lieu J1</label></th>
                <td><input name="training_location" type="text" id="training_location" class="regular-text" value="<?php echo esc_attr($q->training_location); ?>" required></td>
              </tr>
              <tr>
                <th><label for="attestation_formation">Attestation de formation</label></th>
                <td>
                  <input type="checkbox" name="attestation_formation" id="attestation_formation" value="1" <?php checked( $q->attestation_formation, 1 ); ?>>
                  <label for="attestation_formation">Oui</label>
                </td>
              </tr>
              <tr>
                <th><label for="inscription_ouverte">Inscription ouverte</label></th>
                <td>
                  <input type="checkbox" name="inscription_ouverte" id="inscription_ouverte" value="1" <?php checked( $q->inscription_ouverte, 1 ); ?>>
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

    /* ---------- Filtrage ---------- */
    $modules   = $wpdb->get_col("SELECT DISTINCT module_title       FROM $table_questionnaires");
    $groups    = $wpdb->get_col("SELECT DISTINCT group_designation  FROM $table_questionnaires");
    $trainers  = $wpdb->get_col("SELECT DISTINCT trainer_name       FROM $table_questionnaires");
    $dates     = $wpdb->get_col("SELECT DISTINCT training_dates     FROM $table_questionnaires");
    $locations = $wpdb->get_col("SELECT DISTINCT training_location  FROM $table_questionnaires");

    $filter_module   = $_POST['filter_module']   ?? 'Tous';
    $filter_group    = $_POST['filter_group']    ?? 'Tous';
    $filter_trainer  = $_POST['filter_trainer']  ?? 'Tous';
    $filter_date     = $_POST['filter_date']     ?? 'Tous';
    $filter_location = $_POST['filter_location'] ?? 'Tous';

    $where = "WHERE 1=1";
    if ( $filter_module   !== 'Tous' ) $where .= $wpdb->prepare(" AND module_title      = %s", $filter_module);
    if ( $filter_group    !== 'Tous' ) $where .= $wpdb->prepare(" AND group_designation = %s", $filter_group);
    if ( $filter_trainer  !== 'Tous' ) $where .= $wpdb->prepare(" AND trainer_name      = %s", $filter_trainer);
    if ( $filter_date     !== 'Tous' ) $where .= $wpdb->prepare(" AND training_dates    = %s", $filter_date);
    if ( $filter_location !== 'Tous' ) $where .= $wpdb->prepare(" AND training_location = %s", $filter_location);

    // URLs configurées
    $survey_url       = get_option('inskill_eval_survey_page_url',       site_url('/eval-nv1/'));
    $subscription_url = get_option('inskill_eval_subscription_page_url', site_url('/eval-subscription/'));

    // Requête finale
    $questionnaires = $wpdb->get_results(
        "SELECT * FROM $table_questionnaires $where ORDER BY created_at DESC"
    );
    ?>
    <div class="wrap">
      <h1>Mes questionnaires</h1>

      <!-- Formulaire de filtres -->
      <form method="post">
        <table class="form-table">
          <tr>
            <th><label for="filter_module">Module</label></th>
            <td>
              <select name="filter_module" id="filter_module">
                <option>Tous</option>
                <?php foreach ( $modules as $m ) : ?>
                  <option value="<?php echo esc_attr($m); ?>" <?php selected($filter_module, $m); ?>>
                    <?php echo esc_html($m); ?>
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
                <?php foreach ( $groups as $g ) : ?>
                  <option value="<?php echo esc_attr($g); ?>" <?php selected($filter_group, $g); ?>>
                    <?php echo esc_html($g); ?>
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
                <?php foreach ( $trainers as $t ) : ?>
                  <option value="<?php echo esc_attr($t); ?>" <?php selected($filter_trainer, $t); ?>>
                    <?php echo esc_html($t); ?>
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
                <?php foreach ( $dates as $d ) : ?>
                  <option value="<?php echo esc_attr($d); ?>" <?php selected($filter_date, $d); ?>>
                    <?php echo esc_html($d); ?>
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
                <?php foreach ( $locations as $l ) : ?>
                  <option value="<?php echo esc_attr($l); ?>" <?php selected($filter_location, $l); ?>>
                    <?php echo esc_html($l); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        </table>
        <?php submit_button('Filtrer'); ?>
      </form>

      <!-- Tableau principal -->
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th>ID</th><th>Module</th><th>Groupe</th><th>Formateur</th>
            <th>Date</th><th>Lieu</th><th>Attestation</th><th>Inscription</th>
            <th>URL questionnaire</th><th>URL inscription</th>
            <th>Auto-inscription</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ( $questionnaires as $q ) :
            $q_url  = trailingslashit($survey_url)       . '?inskill_eval_survey='   . $q->id;
            $s_url  = trailingslashit($subscription_url) . '?inskill_eval_learners=' . $q->id;
        ?>
          <tr>
            <td><?php echo esc_html($q->id); ?></td>
            <td><?php echo esc_html($q->module_title); ?></td>
            <td><?php echo esc_html($q->group_designation); ?></td>
            <td><?php echo esc_html($q->trainer_name); ?></td>
            <td><?php echo esc_html($q->training_dates); ?></td>
            <td><?php echo esc_html($q->training_location); ?></td>
            <td><?php echo $q->attestation_formation ? 'Oui' : 'Non'; ?></td>
            <td><?php echo $q->inscription_ouverte   ? 'Ouvertes' : 'Fermées'; ?></td>
            <td><a href="<?php echo esc_url($q_url); ?>" target="_blank"><?php echo esc_html($q_url); ?></a></td>
            <td><a href="<?php echo esc_url($s_url); ?>" target="_blank"><?php echo esc_html($s_url); ?></a></td>
            <td>
              <button class="button" onclick="openLearnersPopup('learners_<?php echo $q->id; ?>')">Liste</button>
              <div id="learners_<?php echo $q->id; ?>" style="display:none;">
                <?php
                  $learners = $wpdb->get_results( $wpdb->prepare(
                      "SELECT * FROM {$wpdb->prefix}inskill_eval_learners WHERE questionnaire_id = %d",
                      $q->id
                  ) );
                  if ( $learners ) :
                ?>
                  <table class="widefat">
                    <thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ( $learners as $L ) : ?>
                      <tr>
                        <td><?php echo esc_html($L->participant_nom); ?></td>
                        <td><?php echo esc_html($L->participant_prenom); ?></td>
                        <td><?php echo esc_html($L->participant_email); ?></td>
                        <td>
                          <a href="<?php echo admin_url(
                            'admin.php?page=inskill-eval-manage&action=delete_learner'
                            .'&learner_id='.$L->id.'&questionnaire_id='.$q->id.'&in_popup=1'
                          ); ?>"
                          class="button" onclick="return confirm('Supprimer ce participant ?');" target="_top">
                            Supprimer
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  <p>Aucun participant inscrit.</p>
                <?php endif; ?>
                <h4><strong>Ajouter un participant</strong></h4>
                <a href="<?php echo esc_url($s_url . '#bottom'); ?>"
                   class="button"
                   onclick="window.open('<?php echo esc_url($s_url . '#bottom'); ?>','popup','width=600,height=600');return false;">
                  Ouvrir la page d'inscription
                </a>
              </div>
            </td>
            <td>
              <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage&action=edit&id='.$q->id); ?>"
                 class="button">Editer</a>
              <a href="<?php echo admin_url('admin.php?page=inskill-eval-manage&action=delete&id='.$q->id); ?>"
                 class="button" onclick="return confirm('Vraiment supprimer ce questionnaire ?');">
                Supprimer
              </a>
              <?php if ( $q->attestation_formation ) : ?>
                <a href="<?php echo admin_url(
                  'admin-post.php?action=download_attestations&questionnaire_id=' . $q->id
                ); ?>" class="button">Attestations</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <script>
      function openLearnersPopup(id) {
        var content = document.getElementById(id).innerHTML;
        var popup   = window.open('', 'Liste des participants', 'width=800,height=600,scrollbars=yes');
        var html    = '<html><head><title>Participants</title>'
                    + '<style>body{margin:0;padding:20px;font-family:sans-serif;}'
                    + 'table{width:100%;border-collapse:collapse;}'
                    + 'th,td{border:1px solid #ccc;padding:8px;text-align:left;}'
                    + '</style></head><body><h2>Participants</h2>'
                    + content + '</body></html>';
        popup.document.write(html);
        popup.document.close();
      }
    </script>
<?php
}
