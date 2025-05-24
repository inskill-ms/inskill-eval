<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function inskill_eval_view_results_page() {
    global $wpdb;
    $table_questionnaires = $wpdb->prefix . 'inskill_eval_questionnaires';
    $table_responses      = $wpdb->prefix . 'inskill_eval_responses';

    // Traitement de la suppression d'une réponse (effectué avant toute sortie)
    if ( isset($_GET['delete_response']) ) {
        $response_id = intval($_GET['delete_response']);
        $wpdb->delete( $table_responses, array( 'id' => $response_id ), array( '%d' ) );
        wp_redirect( admin_url('admin.php?page=inskill-eval-results') );
        exit;
    }

    // Récupération des valeurs distinctes pour les filtres
    $modules   = $wpdb->get_col("SELECT DISTINCT module_title FROM $table_questionnaires");
    $groups    = $wpdb->get_col("SELECT DISTINCT group_designation FROM $table_questionnaires");
    $trainers  = $wpdb->get_col("SELECT DISTINCT trainer_name FROM $table_questionnaires");
    $dates     = $wpdb->get_col("SELECT DISTINCT training_dates FROM $table_questionnaires");
    $locations = $wpdb->get_col("SELECT DISTINCT training_location FROM $table_questionnaires");

    // Récupération des filtres soumis (via POST)
    $filter_module   = isset($_POST['filter_module']) ? sanitize_text_field($_POST['filter_module']) : '';
    $filter_group    = isset($_POST['filter_group']) ? sanitize_text_field($_POST['filter_group']) : '';
    $filter_trainer  = isset($_POST['filter_trainer']) ? sanitize_text_field($_POST['filter_trainer']) : '';
    $filter_date     = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '';
    $filter_location = isset($_POST['filter_location']) ? sanitize_text_field($_POST['filter_location']) : '';

    // Construction de la clause WHERE pour filtrer les questionnaires
    $where = 'WHERE 1=1';
    if ( $filter_module && $filter_module != 'Tous' ) {
        $where .= $wpdb->prepare(" AND module_title = %s", $filter_module);
    }
    if ( $filter_group && $filter_group != 'Tous' ) {
        $where .= $wpdb->prepare(" AND group_designation = %s", $filter_group);
    }
    if ( $filter_trainer && $filter_trainer != 'Tous' ) {
        $where .= $wpdb->prepare(" AND trainer_name = %s", $filter_trainer);
    }
    if ( $filter_date && $filter_date != 'Tous' ) {
        $where .= $wpdb->prepare(" AND training_dates = %s", $filter_date);
    }
    if ( $filter_location && $filter_location != 'Tous' ) {
        $where .= $wpdb->prepare(" AND training_location = %s", $filter_location);
    }
    $questionnaire_ids = $wpdb->get_col("SELECT id FROM $table_questionnaires $where");
    ?>
    <div class="wrap">
        <h1>Consulter les résultats</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="filter_module">Intitulé du module</label></th>
                    <td>
                        <select name="filter_module" id="filter_module">
                            <option>Tous</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo esc_attr($module); ?>" <?php selected($filter_module, $module); ?>><?php echo esc_html($module); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_group">Désignation du Groupe</label></th>
                    <td>
                        <select name="filter_group" id="filter_group">
                            <option>Tous</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group); ?>" <?php selected($filter_group, $group); ?>><?php echo esc_html($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_trainer">Nom du Formateur</label></th>
                    <td>
                        <select name="filter_trainer" id="filter_trainer">
                            <option>Tous</option>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo esc_attr($trainer); ?>" <?php selected($filter_trainer, $trainer); ?>><?php echo esc_html($trainer); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_date">Date de formation J1</label></th>
                    <td>
                        <select name="filter_date" id="filter_date">
                            <option>Tous</option>
                            <?php foreach ($dates as $date): ?>
                                <option value="<?php echo esc_attr($date); ?>" <?php selected($filter_date, $date); ?>><?php echo esc_html($date); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="filter_location">Lieu de la formation J1</label></th>
                    <td>
                        <select name="filter_location" id="filter_location">
                            <option>Tous</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location); ?>" <?php selected($filter_location, $location); ?>><?php echo esc_html($location); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Afficher'); ?>
        </form>
        
        <?php
        if ( ! empty($questionnaire_ids) ) {
            $ids = implode(',', array_map('intval', $questionnaire_ids));
            $responses = $wpdb->get_results("SELECT * FROM $table_responses WHERE questionnaire_id IN ($ids)");
            
            if ( $responses ) {
                ?>
                <h2>Liste des participants</h2>
                <form method="post">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select_all"></th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Date de soumission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $responses as $response ): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_responses[]" value="<?php echo esc_attr($response->id); ?>"></td>
                                    <td><?php echo esc_html($response->participant_nom); ?></td>
                                    <td><?php echo esc_html($response->participant_prenom); ?></td>
                                    <td><?php echo esc_html($response->participant_email); ?></td>
                                    <td><?php echo esc_html($response->submitted_at); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=inskill-eval-results&delete_response=' . $response->id); ?>" class="button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php submit_button('Afficher les résultats'); ?>
                </form>
                <script>
                    document.getElementById('select_all').addEventListener('click', function(){
                        var checkboxes = document.querySelectorAll('input[name="selected_responses[]"]');
                        for (var checkbox of checkboxes) {
                            checkbox.checked = this.checked;
                        }
                    });
                    
                    // Fonction pour ouvrir la fenêtre de détails
                    function openDetails(questionLabel, scores, comments) {
                        var newWindow = window.open("", "Détails", "width=600,height=600,scrollbars=yes");
                        var html = '<html><head><title>Détails - ' + questionLabel + '</title>';
                        html += '<style>table {width:100%; border-collapse: collapse;} th, td {border:1px solid #ccc; padding:5px;} th {background:#f5f5f5;}</style>';
                        html += '</head><body>';
                        html += '<h2>' + questionLabel + '</h2>';
                        html += '<h3>Scores</h3>';
                        html += '<table><thead><tr><th>Participant</th><th>Score</th></tr></thead><tbody>';
                        scores.forEach(function(item) {
                            html += '<tr><td>' + item.participant + '</td><td>' + item.score + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        html += '<h3>Commentaires</h3>';
                        html += '<table><thead><tr><th>Participant</th><th>Commentaire</th></tr></thead><tbody>';
                        comments.forEach(function(item) {
                            html += '<tr><td>' + item.participant + '</td><td>' + item.comment + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        html += '</body></html>';
                        newWindow.document.write(html);
                        newWindow.document.close();
                    }
                </script>
                <?php
                // Si des réponses sont sélectionnées, afficher les résultats détaillés
                if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_responses']) ) {
                    $selected = array_map('intval', $_POST['selected_responses']);
                    $ids_sel = implode(',', $selected);
                    $selected_responses = $wpdb->get_results("SELECT * FROM $table_responses WHERE id IN ($ids_sel)");
                    
                    if ( $selected_responses ) {
                        // Affichage de la liste des participants concernés
                        echo '<h3>Participants concernés :</h3>';
                        echo '<ul>';
                        $participants = array();
                        foreach ($selected_responses as $resp) {
                            $name = esc_html($resp->participant_nom . ' ' . $resp->participant_prenom);
                            if (!in_array($name, $participants)) {
                                $participants[] = $name;
                                echo '<li>' . $name . '</li>';
                            }
                        }
                        echo '</ul>';
                        
                        // SECTION : La Formation (questions 1 à 6)
                        $laFormationQuestions = array(
                            1 => array('key' => 'formation_contenu', 'label' => 'Que pensez-vous du contenu de la formation ?'),
                            2 => array('key' => 'formation_dosage', 'label' => 'Que pensez-vous du dosage entre théorie et pratique ?'),
                            3 => array('key' => 'formation_duree', 'label' => 'Que pensez-vous de la durée globale de la formation ?'),
                            4 => array('key' => 'formation_presentations', 'label' => 'Que pensez-vous de la qualité des présentations ?'),
                            5 => array('key' => 'formation_documents', 'label' => 'Que pensez-vous de la qualité des documents distribués ?'),
                            6 => array('key' => 'formation_attentes', 'label' => 'Cette formation a-t-elle répondu à vos attentes ?')
                        );
                        $totalFormation = 0;
                        $nbQuestionsFormation = count($laFormationQuestions);
                        foreach ($laFormationQuestions as $num => $data) {
                            foreach ($selected_responses as $resp) {
                                $totalFormation += intval($resp->{$data['key']});
                            }
                        }
                        $avgFormationGlobal = $nbQuestionsFormation ? round($totalFormation / ($nbQuestionsFormation * count($selected_responses)), 2) : 0;
                        echo '<h2>La Formation : ' . $avgFormationGlobal . ' / 4</h2>';
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th style="width:50px;">N°</th><th>Intitulé de la question</th><th>Moyenne/4</th><th>Nb commentaires</th><th>Détails</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($laFormationQuestions as $num => $data) {
                            $total = 0;
                            $count = 0;
                            $nbComments = 0;
                            $scoresDetail = array();
                            $commentsDetail = array();
                            foreach ($selected_responses as $resp) {
                                $score = intval($resp->{$data['key']});
                                $total += $score;
                                $count++;
                                $scoresDetail[] = array(
                                    'participant' => $resp->participant_nom . ' ' . $resp->participant_prenom,
                                    'score' => $score
                                );
                                $commentField = 'commentaire_' . $data['key'];
                                $comment = trim($resp->{$commentField});
                                if (!empty($comment)) {
                                    $nbComments++;
                                    $commentsDetail[] = array(
                                        'participant' => $resp->participant_nom . ' ' . $resp->participant_prenom,
                                        'comment' => $comment
                                    );
                                }
                            }
                            $avg = $count ? round($total / $count, 2) : 0;
                            echo '<tr>';
                            echo '<td>' . $num . '</td>';
                            echo '<td>' . esc_html($data['label']) . '</td>';
                            echo '<td>' . $avg . ' / 4</td>';
                            echo '<td>' . $nbComments . '</td>';
                            $scoresJson = htmlspecialchars(json_encode($scoresDetail), ENT_QUOTES, 'UTF-8');
                            $commentsJson = htmlspecialchars(json_encode($commentsDetail), ENT_QUOTES, 'UTF-8');
                            echo '<td><button type="button" onclick="openDetails(\'' . esc_js($data['label']) . '\', ' . $scoresJson . ', ' . $commentsJson . ')">Détails</button></td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';

                        // SECTION : L'Animateur (questions 7 à 13)
                        $lAnimateurQuestions = array(
                            7 => array('key' => 'animateur_sympathie', 'label' => 'Sympathie, amabilité, accessibilité'),
                            8 => array('key' => 'animateur_dynamisme', 'label' => 'Dynamisme, enthousiasme'),
                            9 => array('key' => 'animateur_clarte_voix', 'label' => 'Clarté de voix (vitesse, volume, articulation)'),
                            10 => array('key' => 'animateur_ecoute', 'label' => 'Sens de l’écoute'),
                            11 => array('key' => 'animateur_maitrise', 'label' => 'Maîtrise du sujet de formation'),
                            12 => array('key' => 'animateur_explicatifs', 'label' => 'Clarté et pertinence des explications et réponses'),
                            13 => array('key' => 'animateur_interactivite', 'label' => 'Interactivité avec le groupe')
                        );
                        $totalAnimateur = 0;
                        $nbQuestionsAnimateur = count($lAnimateurQuestions);
                        foreach ($lAnimateurQuestions as $num => $data) {
                            foreach ($selected_responses as $resp) {
                                $totalAnimateur += intval($resp->{$data['key']});
                            }
                        }
                        $avgAnimateurGlobal = $nbQuestionsAnimateur ? round($totalAnimateur / ($nbQuestionsAnimateur * count($selected_responses)), 2) : 0;
                        echo '<h2>L\'Animateur : ' . $avgAnimateurGlobal . ' / 4</h2>';
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th style="width:50px;">N°</th><th>Intitulé de la question</th><th>Moyenne/4</th><th>Nb commentaires</th><th>Détails</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($lAnimateurQuestions as $num => $data) {
                            $total = 0;
                            $count = 0;
                            $nbComments = 0;
                            $scoresDetail = array();
                            $commentsDetail = array();
                            foreach ($selected_responses as $resp) {
                                $score = intval($resp->{$data['key']});
                                $total += $score;
                                $count++;
                                $scoresDetail[] = array(
                                    'participant' => $resp->participant_nom . ' ' . $resp->participant_prenom,
                                    'score' => $score
                                );
                                $commentField = 'commentaire_' . $data['key'];
                                $comment = trim($resp->{$commentField});
                                if (!empty($comment)) {
                                    $nbComments++;
                                    $commentsDetail[] = array(
                                        'participant' => $resp->participant_nom . ' ' . $resp->participant_prenom,
                                        'comment' => $comment
                                    );
                                }
                            }
                            $avg = $count ? round($total / $count, 2) : 0;
                            echo '<tr>';
                            echo '<td>' . $num . '</td>';
                            echo '<td>' . esc_html($data['label']) . '</td>';
                            echo '<td>' . $avg . ' / 4</td>';
                            echo '<td>' . $nbComments . '</td>';
                            $scoresJson = htmlspecialchars(json_encode($scoresDetail), ENT_QUOTES, 'UTF-8');
                            $commentsJson = htmlspecialchars(json_encode($commentsDetail), ENT_QUOTES, 'UTF-8');
                            echo '<td><button type="button" onclick="openDetails(\'' . esc_js($data['label']) . '\', ' . $scoresJson . ', ' . $commentsJson . ')">Détails</button></td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';

                        // SECTION : Remarques et suggestions générales
                        $remarks = array();
                        foreach ($selected_responses as $resp) {
                            if (!empty(trim($resp->remarques_suggestions))) {
                                $remarks[] = array(
                                    'participant' => $resp->participant_nom . ' ' . $resp->participant_prenom,
                                    'remark' => $resp->remarques_suggestions
                                );
                            }
                        }
                        $nbFeedbacks = count($remarks);
                        echo '<h2>Remarques et suggestions générales : ' . $nbFeedbacks . ' feedback(s)</h2>';
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Remarques et suggestions générales</th><th>NOM et Prénom du participant</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($remarks as $r) {
                            echo '<tr>';
                            echo '<td>' . esc_html($r['remark']) . '</td>';
                            echo '<td>' . esc_html($r['participant']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                    }
                }
            } else {
                echo "<p>Aucune réponse trouvée.</p>";
            }
        } else {
            echo "<p>Aucun questionnaire trouvé avec ces filtres.</p>";
        }
        ?>
    </div>
    <?php
}