<?php
/*
Plugin Name: InSkill Eval
Description: Plugin pour évaluer la satisfaction des participants en formation.
Version: 1.0
Author: Michel SOMAS (InSkill)
*/

// Sécurité
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Définir des constantes utiles
define( 'INSKILL_EVAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'INSKILL_EVAL_URL', plugin_dir_url( __FILE__ ) );

// Inclusion des fichiers d'installation/désinstallation et du shortcode
include_once( INSKILL_EVAL_DIR . 'inskill-eval-install.php' );
include_once( INSKILL_EVAL_DIR . 'shortcodes.php' );

// Inclusion du fichier de réglages
include_once( INSKILL_EVAL_DIR . 'settings.php' );

// Inclusion des fichiers d'administration uniquement via le hook admin_menu
if ( is_admin() ) {
    // Le hook admin_menu est attaché avec une priorité de 5
    add_action('admin_menu', 'inskill_eval_admin_includes', 5);
}

function inskill_eval_admin_includes(){
    // Inclusion inconditionnelle du fichier qui enregistre le menu
    include_once( INSKILL_EVAL_DIR . 'admin/create-questionnaire.php' );
    
    // Inclusion conditionnelle du contenu des autres pages selon le paramètre 'page'
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    if ( $current_page == 'inskill-eval-manage' ) {
        include_once( INSKILL_EVAL_DIR . 'admin/manage-questionnaires.php' );
    } elseif ( $current_page == 'inskill-eval-results' ) {
        include_once( INSKILL_EVAL_DIR . 'admin/view-results.php' );
    }
}

// Hook d'activation et de désactivation
register_activation_hook( __FILE__, 'inskill_eval_install' );
register_deactivation_hook( __FILE__, 'inskill_eval_uninstall' );
