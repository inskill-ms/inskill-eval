<?php
/**
 * Template d'affichage du questionnaire.
 * La variable $survey (objet) doit contenir :
 *   - module_title
 *   - group_designation
 *   - trainer_name
 *   - training_dates
 *   - training_location
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!-- Inclusion de Tailwind CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
<style>
  /* Vos styles personnalisés, identiques à ceux fournis */
  .rating-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
  }
  @media (max-width: 640px) {
    .rating-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.25rem;
    }
    .rating-label { font-size: 0.7rem; }
  }
  .radio-button {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border: 2px solid;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
  }
  .radio-button input[type="radio"] { position: absolute; opacity: 0; }
  /* Styles par volet */
  .volet-formation-header { background-color: #376092 !important; }
  .volet-formation .radio-button { border-color: #376092 !important; }
  .volet-formation .radio-button:hover { background-color: #dce6f2 !important; }
  .volet-formation .radio-button:has(input[type="radio"]:checked) { background-color: #376092 !important; color: white !important; }
  .volet-animateur-header { background-color: #77933c !important; }
  .volet-animateur .radio-button { border-color: #77933c !important; }
  .volet-animateur .radio-button:hover { background-color: #ebf1de !important; }
  .volet-animateur .radio-button:has(input[type="radio"]:checked) { background-color: #77933c !important; color: white !important; }
  .volet-remarques-header { background-color: #c0504d !important; }
  .textarea-remarques { border: 2px solid #c0504d !important; }
  .input-contact { border: 2px solid #444444; }
  .rating-value { font-weight: bold; margin-bottom: 0.25rem; }
  .rating-label { font-size: 0.75rem; line-height: 1; }
  .add-comment { color: #0070C0; cursor: pointer; font-size: 0.9rem; margin-top: 0.5rem; text-decoration: underline; }
  .comment-formation { border: 2px solid #376092 !important; }
  .comment-animateur { border: 2px solid #77933c !important; }
</style>

<div class="min-h-screen p-4">
  <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-6">
    <!-- Introduction -->
    <div class="text-center mb-8">
      <p class="text-sm text-gray-600 mb-2">
        Ce questionnaire a pour objet d'aider les organisateurs à améliorer cette formation et toute autre activité de nature similaire.
      </p>
      <p class="text-sm text-gray-600 mb-2">
        Nous vous remercions par avance de bien vouloir prendre le temps de répondre aussi franchement et ouvertement que possible. Vos réponses seront traitées en toute confidentialité.
      </p>
      <p class="text-sm text-gray-600 mb-2">
        Le cabinet InSkill vous remercie par avance pour votre contribution !!!
      </p>
      <p class="text-sm text-gray-600 mb-2">
        <strong>Ce questionnaire devrait vous prendre 3 à 5 minutes seulement.</strong>
      </p>
    </div>
    
    <!-- Formulaire -->
    <form method="post" action="">
      <?php wp_nonce_field( 'inskill_eval_survey', 'inskill_eval_survey_nonce' ); ?>
      
      <!-- Informations du questionnaire (non modifiables) -->
      <div class="mb-8">
        <p><strong>Intitulé du module :</strong> <?php echo esc_html( $survey->module_title ); ?></p>
        <p><strong>Désignation du Groupe :</strong> <?php echo esc_html( $survey->group_designation ); ?></p>
        <p><strong>Nom du Formateur :</strong> <?php echo esc_html( $survey->trainer_name ); ?></p>
        <p><strong>Date de formation J1 :</strong> <?php echo esc_html( $survey->training_dates ); ?></p>
        <p><strong>Lieu de la formation J1 :</strong> <?php echo esc_html( $survey->training_location ); ?></p>
      </div>
      
      <!-- Informations personnelles -->
      <div class="mb-8">
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionner votre adresse e-mail</label>
          <select id="participant_email" name="participant_email" class="input-contact w-full rounded p-2" required>
            <option value="">-- Sélectionnez votre adresse e-mail --</option>
            <?php 
            // Récupérer la liste des emails depuis les inscriptions (table inskill_eval_learners)
            global $wpdb;
            $questionnaire_id = isset($_GET['inskill_eval_survey']) ? intval($_GET['inskill_eval_survey']) : 0;
            $table_learners = $wpdb->prefix . 'inskill_eval_learners';
            $learners = $wpdb->get_results( $wpdb->prepare( "SELECT participant_email, participant_nom, participant_prenom FROM $table_learners WHERE questionnaire_id = %d", $questionnaire_id ) );
            if($learners) {
              foreach($learners as $learner) {
                echo '<option value="'.esc_attr($learner->participant_email).'" data-nom="'.esc_attr($learner->participant_nom).'" data-prenom="'.esc_attr($learner->participant_prenom).'">'.esc_html($learner->participant_email).'</option>';
              }
            }
            ?>
          </select>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
            <input type="text" name="participant_nom" id="participant_nom" class="input-contact w-full rounded p-2" placeholder="Votre nom" readonly required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
            <input type="text" name="participant_prenom" id="participant_prenom" class="input-contact w-full rounded p-2" placeholder="Votre prénom" readonly required>
          </div>
        </div>
      </div>
      
      <!-- Volet : La Formation (Questions 1 à 6) -->
      <div class="mb-8 volet-formation">
        <div class="bg-blue-600 volet-formation-header text-white p-3 rounded-t-lg font-semibold mb-4">
          La Formation
        </div>
        <div class="space-y-6">
          <!-- Question 1 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>1.</strong> Que pensez-vous du contenu de la formation ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_contenu" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_contenu" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_contenu" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_contenu" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_contenu" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 2 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>2.</strong> Que pensez-vous du dosage entre théorie et pratique ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_dosage" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_dosage" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_dosage" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_dosage" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_dosage" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 3 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>3.</strong> Que pensez-vous de la durée globale de la formation ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_duree" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">Totalement inadapté</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_duree" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">Trop long/court</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_duree" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">Mérite quelques ajustements</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_duree" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">Parfaitement adapté</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_duree" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 4 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>4.</strong> Que pensez-vous de la qualité des présentations ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_presentations" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvaise</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_presentations" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyenne</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_presentations" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bonne</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_presentations" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellente</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_presentations" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 5 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>5.</strong> Que pensez-vous de la qualité des documents distribués ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_documents" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvaise</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_documents" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyenne</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_documents" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bonne</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_documents" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellente</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_documents" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 6 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>6.</strong> Cette formation a-t-elle répondu à vos attentes ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="formation_attentes" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">Pas du tout</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_attentes" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">Moyennement</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_attentes" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">En grande partie</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="formation_attentes" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">Parfaitement</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_formation_attentes" class="w-full comment-formation rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Volet : L'Animateur (Questions 7 à 13) -->
      <div class="mb-8 volet-animateur">
        <div class="bg-blue-600 volet-animateur-header text-white p-3 rounded-t-lg font-semibold mb-4">
          L'Animateur
        </div>
        <div class="space-y-6">
          <!-- Question 7 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>7.</strong> Comment avez-vous trouvé l'animateur en termes de sympathie, amabilité, accessibilité ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_sympathie" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_sympathie" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_sympathie" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_sympathie" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_sympathie" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 8 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>8.</strong> Comment avez-vous trouvé l'animateur en termes de dynamisme, d'enthousiasme ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_dynamisme" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_dynamisme" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_dynamisme" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_dynamisme" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_dynamisme" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 9 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>9.</strong> Comment avez-vous trouvé l'animateur en termes de clarté de voix (vitesse, volume, articulation) ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_clarte_voix" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_clarte_voix" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_clarte_voix" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_clarte_voix" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_clarte_voix" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 10 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>10.</strong> Comment avez-vous trouvé l'animateur en termes de sens de l’écoute ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_ecoute" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_ecoute" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_ecoute" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_ecoute" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_ecoute" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 11 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>11.</strong> Comment avez-vous trouvé l'animateur en termes de maîtrise du sujet de formation ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_maitrise" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_maitrise" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_maitrise" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_maitrise" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_maitrise" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 12 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>12.</strong> Comment avez-vous trouvé l'animateur en termes de clarté et pertinence des explications et réponses ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_explicatifs" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_explicatifs" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_explicatifs" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_explicatifs" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_explicatifs" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
          
          <!-- Question 13 -->
          <div class="question-template">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <strong>13.</strong> Comment avez-vous trouvé l'animateur en termes d’interactivité avec le groupe ?
            </label>
            <div class="rating-grid mb-2">
              <label class="radio-button">
                <input type="radio" name="animateur_interactivite" value="1" required>
                <span class="rating-value">1</span>
                <span class="rating-label">mauvais</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_interactivite" value="2">
                <span class="rating-value">2</span>
                <span class="rating-label">moyen</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_interactivite" value="3">
                <span class="rating-value">3</span>
                <span class="rating-label">bon</span>
              </label>
              <label class="radio-button">
                <input type="radio" name="animateur_interactivite" value="4">
                <span class="rating-value">4</span>
                <span class="rating-label">excellent</span>
              </label>
            </div>
            <p class="add-comment">Cliquez pour ajouter un commentaire</p>
            <div class="comment-field hidden">
              <textarea name="commentaire_animateur_interactivite" class="w-full comment-animateur rounded p-2 mt-2" placeholder="Vos commentaires..." rows="2"></textarea>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Volet : Remarques et suggestions générales -->
      <div class="mb-8 volet-remarques">
        <div class="bg-blue-600 volet-remarques-header text-white p-3 rounded-t-lg font-semibold mb-4">
          Remarques et suggestions générales
        </div>
        <div>
          <textarea name="remarques_suggestions" class="w-full textarea-remarques border-2 rounded p-2" placeholder="Si vous avez des commentaires ou recommandations à apporter, nous serons ravis d'en prendre connaissance dans cet encadré ☺" rows="4"></textarea>
        </div>
      </div>
      
      <!-- Bouton de soumission -->
      <div class="text-center">
        <button type="submit" class="bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors">
          Valider et envoyer vos réponses
        </button>
      </div>
    </form>
    <!-- Message de fin -->
    <div class="text-center mt-6 text-black font-medium">
      <p>Nous vous remercions chaleureusement d'avoir pris le temps de répondre à ce questionnaire.<br>
      Vos informations seront traitées en toute confidentialité.</p>
    </div>
  </div>
</div>

<!-- Script pour afficher/cacher les zones de commentaire et auto-remplir Nom et Prénom -->
<script>
  document.querySelectorAll('.add-comment').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var commentField = this.nextElementSibling;
      commentField.classList.toggle('hidden');
    });
  });

  // Auto-remplissage des champs Nom et Prénom en fonction de l'adresse e-mail sélectionnée
  document.getElementById('participant_email').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var nom = selectedOption.getAttribute('data-nom') || '';
    var prenom = selectedOption.getAttribute('data-prenom') || '';
    document.getElementById('participant_nom').value = nom;
    document.getElementById('participant_prenom').value = prenom;
  });
</script>
