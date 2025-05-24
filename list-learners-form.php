<?php
/**
 * Template d'affichage du formulaire d'inscription (Préparation des attestations de formation).
 * La variable $subscription (objet) doit contenir :
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
<div class="min-h-screen p-4">
  <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Inscription à la formation</h1>
    <div class="mb-6">
      <p><strong>Intitulé du module :</strong> <?php echo esc_html( $subscription->module_title ); ?></p>
      <p><strong>Désignation du Groupe :</strong> <?php echo esc_html( $subscription->group_designation ); ?></p>
      <p><strong>Nom du Formateur :</strong> <?php echo esc_html( $subscription->trainer_name ); ?></p>
      <p><strong>Date de formation J1 :</strong> <?php echo esc_html( $subscription->training_dates ); ?></p>
      <p><strong>Lieu de la formation J1 :</strong> <?php echo esc_html( $subscription->training_location ); ?></p>
    </div>
    <form method="post" action="">
      <?php wp_nonce_field( 'inskill_eval_subscription', 'inskill_eval_subscription_nonce' ); ?>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
        <input type="text" name="participant_nom" class="w-full p-2 border rounded" placeholder="Votre nom" required>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
        <input type="text" name="participant_prenom" class="w-full p-2 border rounded" placeholder="Votre prénom" required>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Adresse e-mail</label>
        <input type="email" name="participant_email" class="w-full p-2 border rounded" placeholder="Votre e-mail" required>
      </div>
      <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">S'inscrire</button>
      </div>
    </form>
  </div>
</div>
<div id="bottom"></div>