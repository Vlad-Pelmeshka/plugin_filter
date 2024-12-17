<?php

// Create a helper function for easy SDK access.
function premmerce_pwpf_fs()
{
    global  $premmerce_pwpf_fs ;
    
    if ( !isset( $premmerce_pwpf_fs ) ) {
        // Include Freemius SDK.
        include_once dirname( __FILE__ ) . '/freemius/start.php';
        $premmerce_pwpf_fs = fs_dynamic_init( array(
            'id'              => '1519',
            'slug'            => 'premmerce-woocommerce-product-filter',
            'type'            => 'plugin',
            'public_key'      => 'pk_20f16471b14ab029cbbc55d432950',
            'is_premium'      => true,
            'has_addons'      => false,
            'has_paid_plans'  => true,
            'trial'           => array(
            'days'               => 7,
            'is_require_payment' => true,
        ),
            'has_affiliation' => 'all',
            'menu'            => array(
            'slug'    => 'premmerce-filter-admin',
            'support' => false,
            'pricing' => true,
            'contact' => false,
            'account' => false,
            'parent'  => array(
            'slug' => 'premmerce',
        ),
        ),
            'is_live'         => true,
        ) );
    }
    
    return $premmerce_pwpf_fs;
}

// Init Freemius.
premmerce_pwpf_fs();
// Signal that SDK was initiated.
do_action( 'premmerce_pwpf_fs_loaded' );