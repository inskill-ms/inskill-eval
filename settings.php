<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enregistrement des réglages pour l'URL de la page du questionnaire et de la page d'inscription
 */
function inskill_eval_register_settings() {
    register_setting('inskill_eval_options_group', 'inskill_eval_survey_page_url', 'esc_url_raw');
    register_setting('inskill_eval_options_group', 'inskill_eval_subscription_page_url', 'esc_url_raw');
}
add_action('admin_init', 'inskill_eval_register_settings');

/**
 * Affichage de la page de réglages
 */
function inskill_eval_settings_page() {
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('Vous n’avez pas l’autorisation d’accéder à cette page.'));
    }
    ?>
    <div class="wrap">
        <h1>Réglages InSkill Eval</h1>
        <form method="post" action="options.php">
            <?php settings_fields('inskill_eval_options_group'); ?>
            <?php do_settings_sections('inskill_eval_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">URL de la page du questionnaire</th>
                    <td>
                        <input type="text" name="inskill_eval_survey_page_url" value="<?php echo esc_attr(get_option('inskill_eval_survey_page_url', site_url('/index.php/eval-nv1/'))); ?>" style="width: 400px;" />
                        <p class="description">
                            Saisissez l'URL complète de la page frontend utilisée pour afficher le questionnaire (cette page doit contenir le shortcode [inskill_eval_survey]).<br>
                            Par exemple : <code>https://digitaltools.inskill.net/eval-nv1/</code>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">URL de la page d'inscription</th>
                    <td>
                        <input type="text" name="inskill_eval_subscription_page_url" value="<?php echo esc_attr(get_option('inskill_eval_subscription_page_url', site_url('/index.php/eval-subscription/'))); ?>" style="width: 400px;" />
                        <p class="description">
                            Saisissez l'URL complète de la page d'inscription utilisée pour recenser les participants (cette page doit contenir le shortcode [inskill_eval_subscription]).<br>
                            Par exemple : <code>https://digitaltools.inskill.net/eval-subscription/</code>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Ajout de la page de réglages dans le menu InSkill Eval.
 * On utilise une priorité supérieure (20) pour s'assurer que le menu principal est déjà créé.
 */
function inskill_eval_add_settings_page() {
    add_submenu_page(
        'inskill-eval',             // Parent slug (menu principal du plugin)
        'Réglages InSkill Eval',    // Titre de la page
        'Réglages',                 // Titre du menu
        'manage_options',           // Capacité requise
        'inskill-eval-settings',    // Slug de la page
        'inskill_eval_settings_page'
    );
}
add_action('admin_menu', 'inskill_eval_add_settings_page', 20);