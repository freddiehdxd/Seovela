<?php
/**
 * Seovela AI Settings View
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle settings save
if ( isset( $_POST['seovela_save_ai_settings'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seovela_ai_nonce'] ) ), 'seovela_save_ai_settings' ) ) {
    // Save AI Provider (validate against allowlist)
    $ai_provider = isset( $_POST['seovela_ai_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_ai_provider'] ) ) : 'openai';
    $allowed_providers = array( 'openai', 'gemini', 'claude' );
    if ( ! in_array( $ai_provider, $allowed_providers, true ) ) {
        $ai_provider = 'openai';
    }
    update_option( 'seovela_ai_provider', $ai_provider );
    
    // Save OpenAI settings (encrypt API key, skip if placeholder mask submitted)
    $openai_key_input = isset( $_POST['seovela_openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_openai_api_key'] ) ) : '';
    if ( ! empty( $openai_key_input ) && strpos( $openai_key_input, '****' ) === false ) {
        update_option( 'seovela_openai_api_key', Seovela_Helpers::encrypt( $openai_key_input ) );
    }
    $openai_model = isset( $_POST['seovela_openai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_openai_model'] ) ) : 'gpt-5-mini';
    update_option( 'seovela_openai_model', $openai_model );
    
    // Save Gemini settings (encrypt API key)
    $gemini_key_input = isset( $_POST['seovela_gemini_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_gemini_api_key'] ) ) : '';
    if ( ! empty( $gemini_key_input ) && strpos( $gemini_key_input, '****' ) === false ) {
        update_option( 'seovela_gemini_api_key', Seovela_Helpers::encrypt( $gemini_key_input ) );
    }
    $gemini_model = isset( $_POST['seovela_gemini_model'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_gemini_model'] ) ) : 'gemini-3-flash-preview';
    update_option( 'seovela_gemini_model', $gemini_model );
    
    // Save Claude settings (encrypt API key)
    $claude_key_input = isset( $_POST['seovela_claude_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_claude_api_key'] ) ) : '';
    if ( ! empty( $claude_key_input ) && strpos( $claude_key_input, '****' ) === false ) {
        update_option( 'seovela_claude_api_key', Seovela_Helpers::encrypt( $claude_key_input ) );
    }
    $claude_model = isset( $_POST['seovela_claude_model'] ) ? sanitize_text_field( wp_unslash( $_POST['seovela_claude_model'] ) ) : 'claude-sonnet-4-6';
    update_option( 'seovela_claude_model', $claude_model );
    
    // Save AI generation settings
    $ai_temperature = isset( $_POST['seovela_ai_temperature'] ) ? floatval( wp_unslash( $_POST['seovela_ai_temperature'] ) ) : 0.7;
    $ai_post_types = isset( $_POST['seovela_ai_post_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['seovela_ai_post_types'] ) ) : array( 'post', 'page' );
    update_option( 'seovela_ai_temperature', $ai_temperature );
    update_option( 'seovela_ai_post_types', $ai_post_types );
    
    add_settings_error( 'seovela_ai_settings', 'ai_settings_saved', __( 'AI settings saved successfully!', 'seovela' ), 'success' );
}

// Get current values
$ai_provider = get_option( 'seovela_ai_provider', 'openai' );
$openai_key_raw = get_option( 'seovela_openai_api_key', '' );
$openai_key_masked = Seovela_Helpers::mask_api_key( $openai_key_raw );
$openai_model = get_option( 'seovela_openai_model', 'gpt-5-mini' );
$gemini_key_raw = get_option( 'seovela_gemini_api_key', '' );
$gemini_key_masked = Seovela_Helpers::mask_api_key( $gemini_key_raw );
$gemini_model = get_option( 'seovela_gemini_model', 'gemini-3-flash-preview' );
$claude_key_raw = get_option( 'seovela_claude_api_key', '' );
$claude_key_masked = Seovela_Helpers::mask_api_key( $claude_key_raw );
$claude_model = get_option( 'seovela_claude_model', 'claude-sonnet-4-6' );
$ai_temperature = get_option( 'seovela_ai_temperature', 0.7 );
$ai_post_types = get_option( 'seovela_ai_post_types', array( 'post', 'page' ) );

// Get all public post types
$post_types = get_post_types( array( 'public' => true ), 'objects' );
unset( $post_types['attachment'] );

// Check if API keys are configured (check raw value, not masked)
$openai_configured = ! empty( $openai_key_raw );
$gemini_configured = ! empty( $gemini_key_raw );
$claude_configured = ! empty( $claude_key_raw );
?>

<div class="seovela-premium-page">

    <!-- Premium Header -->
    <div class="seovela-page-header">
        <div class="seovela-page-header-bg"></div>
        <div class="seovela-page-header-content">
            <div class="seovela-page-header-top">
                <div class="seovela-page-header-text">
                    <div class="seovela-page-breadcrumb">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela' ) ); ?>">Seovela</a>
                        <span class="sep">/</span>
                        <span class="current"><?php esc_html_e( 'Settings', 'seovela' ); ?></span>
                    </div>
                    <h1><?php esc_html_e( 'SEO Settings', 'seovela' ); ?></h1>
                    <p><?php esc_html_e( 'Configure titles, meta tags, sitemaps, schema, and more to optimize your site for search engines.', 'seovela' ); ?></p>
                </div>
            </div>
            <div class="seovela-page-header-tabs">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta' ) ); ?>" class="seovela-header-tab">
                    <span class="dashicons dashicons-edit-page"></span>
                    <?php esc_html_e( 'Titles & Meta', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=sitemap' ) ); ?>" class="seovela-header-tab">
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e( 'Sitemap', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=schema' ) ); ?>" class="seovela-header-tab">
                    <span class="dashicons dashicons-code-standards"></span>
                    <?php esc_html_e( 'Schema', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=indexing' ) ); ?>" class="seovela-header-tab">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e( 'Indexing', 'seovela' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="seovela-header-tab active">
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e( 'AI Optimization', 'seovela' ); ?>
                </a>
            </div>
        </div>
    </div>

    <?php settings_errors(); ?>

    <div class="seovela-page-body">
            <form method="post" action="">
                <?php wp_nonce_field( 'seovela_save_ai_settings', 'seovela_ai_nonce' ); ?>
                
            <!-- AI Provider Selection -->
            <div class="seovela-card seovela-ai-providers">
                <h2>
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php esc_html_e( 'AI Provider', 'seovela' ); ?>
                </h2>
                    <p class="description">
                    <?php esc_html_e( 'Select your preferred AI provider for generating SEO titles and meta descriptions.', 'seovela' ); ?>
                </p>
                
                <div class="seovela-provider-cards">
                    <!-- OpenAI Card -->
                    <label class="seovela-provider-card <?php echo $ai_provider === 'openai' ? 'active' : ''; ?> <?php echo $openai_configured ? 'configured' : ''; ?>">
                        <input type="radio" name="seovela_ai_provider" value="openai" <?php checked( $ai_provider, 'openai' ); ?> />
                        <div class="seovela-provider-header">
                            <div class="seovela-provider-logo">
                                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                                    <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364l2.0201-1.1638a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6099-1.4997Z"/>
                                </svg>
                            </div>
                            <div class="seovela-provider-info">
                                <h3><?php esc_html_e( 'ChatGPT', 'seovela' ); ?></h3>
                                <span class="seovela-provider-tag">OpenAI</span>
                            </div>
                            <?php if ( $openai_configured ) : ?>
                                <span class="seovela-status-indicator configured">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="seovela-provider-desc"><?php esc_html_e( 'Powered by GPT-5 and GPT-4.1 models. Best for creative and nuanced SEO content.', 'seovela' ); ?></p>
                    </label>
                    
                    <!-- Gemini Card -->
                    <label class="seovela-provider-card <?php echo $ai_provider === 'gemini' ? 'active' : ''; ?> <?php echo $gemini_configured ? 'configured' : ''; ?>">
                        <input type="radio" name="seovela_ai_provider" value="gemini" <?php checked( $ai_provider, 'gemini' ); ?> />
                        <div class="seovela-provider-header">
                            <div class="seovela-provider-logo gemini">
                                <svg viewBox="0 0 24 24" width="32" height="32">
                                    <defs>
                                        <linearGradient id="gemini-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#4285f4"/>
                                            <stop offset="25%" style="stop-color:#9b72cb"/>
                                            <stop offset="50%" style="stop-color:#d96570"/>
                                            <stop offset="75%" style="stop-color:#9b72cb"/>
                                            <stop offset="100%" style="stop-color:#4285f4"/>
                                        </linearGradient>
                                    </defs>
                                    <path fill="url(#gemini-gradient)" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93s3.05-7.44 7-7.93v15.86zm2-15.86c3.95.49 7 3.85 7 7.93s-3.05 7.44-7 7.93V4.07z"/>
                                </svg>
                            </div>
                            <div class="seovela-provider-info">
                                <h3><?php esc_html_e( 'Gemini', 'seovela' ); ?></h3>
                                <span class="seovela-provider-tag google">Google</span>
                            </div>
                            <?php if ( $gemini_configured ) : ?>
                                <span class="seovela-status-indicator configured">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="seovela-provider-desc"><?php esc_html_e( 'Google\'s Gemini 3 series. Fast, efficient, and great for SEO optimization at scale.', 'seovela' ); ?></p>
                    </label>
                    
                    <!-- Claude Card -->
                    <label class="seovela-provider-card <?php echo $ai_provider === 'claude' ? 'active' : ''; ?> <?php echo $claude_configured ? 'configured' : ''; ?>">
                        <input type="radio" name="seovela_ai_provider" value="claude" <?php checked( $ai_provider, 'claude' ); ?> />
                        <div class="seovela-provider-header">
                            <div class="seovela-provider-logo claude">
                                <svg viewBox="0 0 24 24" width="32" height="32" fill="none">
                                    <path d="M16.98 7.41L12.58 3 8.18 7.41 12.58 11.82l4.4-4.41zM3 12.59l4.41 4.41 4.41-4.41L7.41 8.18 3 12.59zM12.58 13.36l-4.41 4.41 4.41 4.41 4.41-4.41-4.41-4.41zM17.76 8.18l-4.41 4.41 4.41 4.41L22.17 12.59 17.76 8.18z" fill="#D97757"/>
                                </svg>
                            </div>
                            <div class="seovela-provider-info">
                                <h3><?php esc_html_e( 'Claude', 'seovela' ); ?></h3>
                                <span class="seovela-provider-tag anthropic">Anthropic</span>
                            </div>
                            <?php if ( $claude_configured ) : ?>
                                <span class="seovela-status-indicator configured">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="seovela-provider-desc"><?php esc_html_e( 'Anthropic\'s Claude 4.6 models. Excellent at nuanced writing and following complex instructions.', 'seovela' ); ?></p>
                    </label>
                </div>
            </div>

            <!-- OpenAI Configuration -->
            <div class="seovela-card seovela-ai-config" id="openai-config" <?php echo $ai_provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
                <h2>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" style="vertical-align: middle; margin-right: 8px;">
                        <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729z"/>
                    </svg>
                    <?php esc_html_e( 'OpenAI Configuration', 'seovela' ); ?>
                </h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                            <label for="seovela_openai_api_key"><?php esc_html_e( 'API Key', 'seovela' ); ?></label>
                            </th>
                            <td>
                            <div class="seovela-api-key-field">
                                <input 
                                    type="password" 
                                    id="seovela_openai_api_key" 
                                    name="seovela_openai_api_key" 
                                    value="" 
                                    class="regular-text"
                                    placeholder="<?php echo $openai_configured ? esc_attr( $openai_key_masked ) : 'sk-...'; ?>"
                                    autocomplete="off"
                                />
                                <button type="button" class="button seovela-test-key" data-provider="openai">
                                    <?php esc_html_e( 'Test Connection', 'seovela' ); ?>
                                </button>
                            </div>
                                <?php if ( $openai_configured ) : ?>
                                <p class="description" style="color: #10b981; font-weight: 500;">
                                    <span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
                                    <?php esc_html_e( 'API key is saved and encrypted. Enter a new key to replace it.', 'seovela' ); ?>
                                </p>
                                <?php endif; ?>
                                <p class="description">
                                <?php 
                                printf( 
                                    esc_html__( 'Get your API key from %s', 'seovela' ),
                                    '<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI Platform</a>'
                                ); 
                                ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                            <label for="seovela_openai_model"><?php esc_html_e( 'Model', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <select id="seovela_openai_model" name="seovela_openai_model" class="regular-text">
                                <optgroup label="<?php esc_attr_e( 'GPT-5 Series (Latest)', 'seovela' ); ?>">
                                    <option value="gpt-5.2" <?php selected( $openai_model, 'gpt-5.2' ); ?>>
                                        GPT-5.2 (<?php esc_html_e( 'Flagship - Most Capable', 'seovela' ); ?>)
                                    </option>
                                    <option value="gpt-5-mini" <?php selected( $openai_model, 'gpt-5-mini' ); ?>>
                                        GPT-5 Mini (<?php esc_html_e( 'Fast & Smart', 'seovela' ); ?>)
                                    </option>
                                    <option value="gpt-5-nano" <?php selected( $openai_model, 'gpt-5-nano' ); ?>>
                                        GPT-5 Nano (<?php esc_html_e( 'Ultra Fast & Cheap', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e( 'GPT-4.1 Series', 'seovela' ); ?>">
                                    <option value="gpt-4.1" <?php selected( $openai_model, 'gpt-4.1' ); ?>>
                                        GPT-4.1 (<?php esc_html_e( 'Previous Flagship', 'seovela' ); ?>)
                                    </option>
                                    <option value="gpt-4.1-mini" <?php selected( $openai_model, 'gpt-4.1-mini' ); ?>>
                                        GPT-4.1 Mini (<?php esc_html_e( 'Balanced', 'seovela' ); ?>)
                                    </option>
                                    <option value="gpt-4.1-nano" <?php selected( $openai_model, 'gpt-4.1-nano' ); ?>>
                                        GPT-4.1 Nano (<?php esc_html_e( 'Budget', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e( 'GPT-4o Series (Legacy)', 'seovela' ); ?>">
                                    <option value="gpt-4o" <?php selected( $openai_model, 'gpt-4o' ); ?>>
                                        GPT-4o (<?php esc_html_e( 'Legacy Multimodal', 'seovela' ); ?>)
                                    </option>
                                    <option value="gpt-4o-mini" <?php selected( $openai_model, 'gpt-4o-mini' ); ?>>
                                        GPT-4o Mini (<?php esc_html_e( 'Legacy Fast', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'GPT-5 Mini is recommended for the best balance of speed, quality, and cost.', 'seovela' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Gemini Configuration -->
            <div class="seovela-card seovela-ai-config" id="gemini-config" <?php echo $ai_provider !== 'gemini' ? 'style="display:none;"' : ''; ?>>
                <h2>
                    <svg viewBox="0 0 24 24" width="24" height="24" style="vertical-align: middle; margin-right: 8px;">
                        <defs>
                            <linearGradient id="gemini-gradient-2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#4285f4"/>
                                <stop offset="50%" style="stop-color:#d96570"/>
                                <stop offset="100%" style="stop-color:#4285f4"/>
                            </linearGradient>
                        </defs>
                        <path fill="url(#gemini-gradient-2)" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93s3.05-7.44 7-7.93v15.86zm2-15.86c3.95.49 7 3.85 7 7.93s-3.05 7.44-7 7.93V4.07z"/>
                    </svg>
                    <?php esc_html_e( 'Google Gemini Configuration', 'seovela' ); ?>
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="seovela_gemini_api_key"><?php esc_html_e( 'API Key', 'seovela' ); ?></label>
                            </th>
                            <td>
                            <div class="seovela-api-key-field">
                                <input 
                                    type="password" 
                                    id="seovela_gemini_api_key" 
                                    name="seovela_gemini_api_key" 
                                    value="" 
                                    class="regular-text"
                                    placeholder="<?php echo $gemini_configured ? esc_attr( $gemini_key_masked ) : 'AIza...'; ?>"
                                    autocomplete="off"
                                />
                                <button type="button" class="button seovela-test-key" data-provider="gemini">
                                    <?php esc_html_e( 'Test Connection', 'seovela' ); ?>
                                </button>
                            </div>
                            <?php if ( $gemini_configured ) : ?>
                            <p class="description" style="color: #10b981; font-weight: 500;">
                                <span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
                                <?php esc_html_e( 'API key is saved and encrypted. Enter a new key to replace it.', 'seovela' ); ?>
                            </p>
                            <?php endif; ?>
                            <p class="description">
                                <?php 
                                printf( 
                                    esc_html__( 'Get your API key from %s', 'seovela' ),
                                    '<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>'
                                ); 
                                ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="seovela_gemini_model"><?php esc_html_e( 'Model', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <select id="seovela_gemini_model" name="seovela_gemini_model" class="regular-text">
                                <optgroup label="<?php esc_attr_e( 'Gemini 3 Series (Latest)', 'seovela' ); ?>">
                                    <option value="gemini-3.1-pro-preview" <?php selected( $gemini_model, 'gemini-3.1-pro-preview' ); ?>>
                                        Gemini 3.1 Pro (<?php esc_html_e( 'Flagship - Deep Reasoning', 'seovela' ); ?>)
                                    </option>
                                    <option value="gemini-3-flash-preview" <?php selected( $gemini_model, 'gemini-3-flash-preview' ); ?>>
                                        Gemini 3 Flash (<?php esc_html_e( 'Balanced - Fast & Smart', 'seovela' ); ?>)
                                    </option>
                                    <option value="gemini-3.1-flash-lite-preview" <?php selected( $gemini_model, 'gemini-3.1-flash-lite-preview' ); ?>>
                                        Gemini 3.1 Flash Lite (<?php esc_html_e( 'Ultra Fast & Cheap', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e( 'Embeddings', 'seovela' ); ?>">
                                    <option value="text-embedding-004" <?php selected( $gemini_model, 'text-embedding-004' ); ?>>
                                        Text Embedding 004 (<?php esc_html_e( 'Semantic Search - Free', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Gemini 3 Flash is recommended for the best balance of speed, intelligence, and cost.', 'seovela' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Claude Configuration -->
            <div class="seovela-card seovela-ai-config" id="claude-config" <?php echo $ai_provider !== 'claude' ? 'style="display:none;"' : ''; ?>>
                <h2>
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" style="vertical-align: middle; margin-right: 8px;">
                        <path d="M16.98 7.41L12.58 3 8.18 7.41 12.58 11.82l4.4-4.41zM3 12.59l4.41 4.41 4.41-4.41L7.41 8.18 3 12.59zM12.58 13.36l-4.41 4.41 4.41 4.41 4.41-4.41-4.41-4.41zM17.76 8.18l-4.41 4.41 4.41 4.41L22.17 12.59 17.76 8.18z" fill="#D97757"/>
                    </svg>
                    <?php esc_html_e( 'Anthropic Claude Configuration', 'seovela' ); ?>
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="seovela_claude_api_key"><?php esc_html_e( 'API Key', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <div class="seovela-api-key-field">
                                <input 
                                    type="password" 
                                    id="seovela_claude_api_key" 
                                    name="seovela_claude_api_key" 
                                    value="" 
                                    class="regular-text"
                                    placeholder="<?php echo $claude_configured ? esc_attr( $claude_key_masked ) : 'sk-ant-...'; ?>"
                                    autocomplete="off"
                                />
                                <button type="button" class="button seovela-test-key" data-provider="claude">
                                    <?php esc_html_e( 'Test Connection', 'seovela' ); ?>
                                </button>
                            </div>
                            <?php if ( $claude_configured ) : ?>
                            <p class="description" style="color: #10b981; font-weight: 500;">
                                <span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
                                <?php esc_html_e( 'API key is saved and encrypted. Enter a new key to replace it.', 'seovela' ); ?>
                            </p>
                            <?php endif; ?>
                            <p class="description">
                                <?php 
                                printf( 
                                    esc_html__( 'Get your API key from %s', 'seovela' ),
                                    '<a href="https://console.anthropic.com/" target="_blank" rel="noopener">Anthropic Console</a>'
                                ); 
                                ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="seovela_claude_model"><?php esc_html_e( 'Model', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <select id="seovela_claude_model" name="seovela_claude_model" class="regular-text">
                                <optgroup label="<?php esc_attr_e( 'Claude 4.6 Series (Latest)', 'seovela' ); ?>">
                                    <option value="claude-opus-4-6" <?php selected( $claude_model, 'claude-opus-4-6' ); ?>>
                                        Claude Opus 4.6 (<?php esc_html_e( 'Most Intelligent - Complex Reasoning', 'seovela' ); ?>)
                                    </option>
                                    <option value="claude-sonnet-4-6" <?php selected( $claude_model, 'claude-sonnet-4-6' ); ?>>
                                        Claude Sonnet 4.6 (<?php esc_html_e( 'Balanced - Fast & Smart', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e( 'Claude Haiku', 'seovela' ); ?>">
                                    <option value="claude-haiku-4-5-20251001" <?php selected( $claude_model, 'claude-haiku-4-5-20251001' ); ?>>
                                        Claude Haiku 4.5 (<?php esc_html_e( 'Fastest & Cheapest', 'seovela' ); ?>)
                                    </option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Claude Sonnet 4.6 is recommended for the best balance of quality, speed, and cost for SEO tasks.', 'seovela' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Generation Settings -->
            <div class="seovela-card">
                <h2>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Generation Settings', 'seovela' ); ?>
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="seovela_ai_temperature"><?php esc_html_e( 'Creativity Level', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <div class="seovela-range-field">
                                <input 
                                    type="range" 
                                    id="seovela_ai_temperature" 
                                    name="seovela_ai_temperature" 
                                    value="<?php echo esc_attr( $ai_temperature ); ?>" 
                                    min="0" 
                                    max="1" 
                                    step="0.1"
                                />
                                <span class="seovela-range-value"><?php echo esc_html( $ai_temperature ); ?></span>
                            </div>
                            <div class="seovela-range-labels">
                                <span><?php esc_html_e( 'Conservative', 'seovela' ); ?></span>
                                <span><?php esc_html_e( 'Balanced', 'seovela' ); ?></span>
                                <span><?php esc_html_e( 'Creative', 'seovela' ); ?></span>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Lower values produce more predictable content, higher values are more creative.', 'seovela' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Enable for Post Types', 'seovela' ); ?></label>
                        </th>
                        <td>
                            <div class="seovela-checkbox-grid">
                                <?php foreach ( $post_types as $post_type ) : ?>
                                    <label class="seovela-checkbox-item">
                                        <input 
                                            type="checkbox" 
                                            name="seovela_ai_post_types[]" 
                                            value="<?php echo esc_attr( $post_type->name ); ?>"
                                            <?php checked( in_array( $post_type->name, $ai_post_types, true ) ); ?>
                                        />
                                        <span><?php echo esc_html( $post_type->labels->singular_name ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                                <p class="description">
                                <?php esc_html_e( 'Select which post types should have AI optimization available in the editor.', 'seovela' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( __( 'Save AI Settings', 'seovela' ), 'primary', 'seovela_save_ai_settings' ); ?>
                </div>
            </form>

        <!-- How It Works -->
        <div class="seovela-card seovela-info-box">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e( 'How It Works', 'seovela' ); ?>
            </h2>
            <div class="seovela-steps">
                <div class="seovela-step">
                    <div class="seovela-step-number">1</div>
                    <div class="seovela-step-content">
                        <h4><?php esc_html_e( 'Configure Your API Key', 'seovela' ); ?></h4>
                        <p><?php esc_html_e( 'Add your OpenAI, Google Gemini, or Anthropic Claude API key above and select your preferred model.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-step">
                    <div class="seovela-step-number">2</div>
                    <div class="seovela-step-content">
                        <h4><?php esc_html_e( 'Edit Your Content', 'seovela' ); ?></h4>
                        <p><?php esc_html_e( 'Open any post or page in the editor. The Seovela SEO metabox will appear.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-step">
                    <div class="seovela-step-number">3</div>
                    <div class="seovela-step-content">
                        <h4><?php esc_html_e( 'Generate with AI', 'seovela' ); ?></h4>
                        <p><?php esc_html_e( 'Click "Generate with AI" next to the meta title or description fields to get AI-powered suggestions.', 'seovela' ); ?></p>
                    </div>
                </div>
                <div class="seovela-step">
                    <div class="seovela-step-number">4</div>
                    <div class="seovela-step-content">
                        <h4><?php esc_html_e( 'Review & Apply', 'seovela' ); ?></h4>
                        <p><?php esc_html_e( 'Review the generated content and apply it with one click. Edit as needed.', 'seovela' ); ?></p>
                    </div>
                </div>
            </div>
            </div>

        <!-- Pricing Info -->
            <div class="seovela-card">
            <h2>
                <span class="dashicons dashicons-info-outline"></span>
                <?php esc_html_e( 'API Pricing', 'seovela' ); ?>
            </h2>
            <div class="seovela-pricing-grid">
                <div class="seovela-pricing-item">
                    <h4><?php esc_html_e( 'OpenAI', 'seovela' ); ?></h4>
                    <ul>
                        <li><strong>GPT-5.2:</strong> $1.75 / 1M input, $14.00 / 1M output</li>
                        <li><strong>GPT-5 Mini:</strong> $0.25 / 1M input, $2.00 / 1M output</li>
                        <li><strong>GPT-5 Nano:</strong> $0.05 / 1M input, $0.40 / 1M output</li>
                        <li><strong>GPT-4.1:</strong> $2.00 / 1M input, $8.00 / 1M output</li>
                        <li><strong>GPT-4.1 Mini:</strong> $0.40 / 1M input, $1.60 / 1M output</li>
                        <li><strong>GPT-4.1 Nano:</strong> $0.10 / 1M input, $0.40 / 1M output</li>
                    </ul>
                    <p class="description"><?php esc_html_e( '~100 meta generations = ~$0.01-0.05 with GPT-5 Mini', 'seovela' ); ?></p>
                </div>
                <div class="seovela-pricing-item">
                    <h4><?php esc_html_e( 'Google Gemini', 'seovela' ); ?></h4>
                    <ul>
                        <li><strong>Gemini 3.1 Pro:</strong> $2.00 / 1M input, $12.00 / 1M output</li>
                        <li><strong>Gemini 3 Flash:</strong> $0.50 / 1M input, $3.00 / 1M output</li>
                        <li><strong>Gemini 3.1 Flash Lite:</strong> $0.25 / 1M input, $1.50 / 1M output</li>
                        <li><strong>Text Embedding 004:</strong> Free (within limits)</li>
                    </ul>
                    <p class="description"><?php esc_html_e( 'Excellent for bulk SEO tasks. Embedding model free for semantic linking.', 'seovela' ); ?></p>
                </div>
                <div class="seovela-pricing-item">
                    <h4><?php esc_html_e( 'Anthropic Claude', 'seovela' ); ?></h4>
                    <ul>
                        <li><strong>Claude Opus 4.6:</strong> $5.00 / 1M input, $25.00 / 1M output</li>
                        <li><strong>Claude Sonnet 4.6:</strong> $3.00 / 1M input, $15.00 / 1M output</li>
                        <li><strong>Claude Haiku 4.5:</strong> $1.00 / 1M input, $5.00 / 1M output</li>
                    </ul>
                    <p class="description"><?php esc_html_e( 'Excellent for nuanced, instruction-following SEO content', 'seovela' ); ?></p>
                </div>
            </div>
        </div>
    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->

<style>
/* AI Settings Page Styles */
.seovela-provider-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.seovela-provider-card {
    position: relative;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.seovela-provider-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.seovela-provider-card.active {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.seovela-provider-card.configured .seovela-status-indicator {
    color: #10b981;
}

.seovela-provider-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.seovela-provider-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

.seovela-provider-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
}

.seovela-provider-logo.gemini {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.seovela-provider-info h3 {
    margin: 0 0 4px;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.seovela-provider-tag {
    display: inline-block;
    background: #e2e8f0;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.seovela-provider-tag.google {
    background: linear-gradient(135deg, #4285f4, #34a853, #fbbc04, #ea4335);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.seovela-status-indicator {
    margin-left: auto;
}

.seovela-status-indicator .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.seovela-provider-desc {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

/* API Key Field */
.seovela-api-key-field {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.seovela-api-key-field input {
    flex: 1;
    min-width: 200px;
}

.seovela-test-key {
    white-space: nowrap;
}

/* Range Field */
.seovela-range-field {
    display: flex;
    align-items: center;
    gap: 16px;
    max-width: 400px;
}

.seovela-range-field input[type="range"] {
    flex: 1;
    height: 8px;
    border-radius: 4px;
    background: #e2e8f0;
    outline: none;
    -webkit-appearance: none;
}

.seovela-range-field input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
}

.seovela-range-value {
    font-weight: 700;
    font-size: 18px;
    color: #3b82f6;
    min-width: 40px;
    text-align: center;
}

.seovela-range-labels {
    display: flex;
    justify-content: space-between;
    max-width: 400px;
    margin-top: 8px;
}

.seovela-range-labels span {
    font-size: 12px;
    color: #94a3b8;
}

/* Checkbox Grid */
.seovela-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.seovela-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.seovela-checkbox-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.seovela-checkbox-item input:checked + span {
    color: #3b82f6;
    font-weight: 600;
}

/* Steps */
.seovela-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.seovela-step {
    display: flex;
    gap: 16px;
}

.seovela-step-number {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.seovela-step-content h4 {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.seovela-step-content p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
}

/* Pricing Grid */
.seovela-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.seovela-pricing-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
}

.seovela-pricing-item h4 {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.seovela-pricing-item ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.seovela-pricing-item li {
    padding: 6px 0;
    color: #475569;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.seovela-pricing-item li:last-child {
    border-bottom: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Provider selection
    $("input[name='seovela_ai_provider']").on("change", function() {
        var provider = $(this).val();

        // Update card states
        $(".seovela-provider-card").removeClass("active");
        $(this).closest(".seovela-provider-card").addClass("active");

        // Toggle config panels
        $("#openai-config, #gemini-config, #claude-config").slideUp(200);
        $("#" + provider + "-config").slideDown(200);
    });

    // Test API connection
    $(".seovela-test-key").on("click", function() {
        var $button = $(this);
        var provider = $button.data("provider");
        var originalText = $button.text();

        $button.text("<?php echo esc_js( __( 'Testing...', 'seovela' ) ); ?>").prop("disabled", true);

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "seovela_test_ai_connection",
                provider: provider,
                api_key: provider === "openai" ? $("#seovela_openai_api_key").val() : (provider === "claude" ? $("#seovela_claude_api_key").val() : $("#seovela_gemini_api_key").val()),
                nonce: "<?php echo wp_create_nonce( 'seovela_test_ai' ); ?>"
            },
            success: function(response) {
                if (response.success) {
                    alert("<?php echo esc_js( __( 'Connection successful! API key is valid.', 'seovela' ) ); ?>");
                } else {
                    alert("<?php echo esc_js( __( 'Connection failed: ', 'seovela' ) ); ?>" + response.data.message);
                }
            },
            error: function() {
                alert("<?php echo esc_js( __( 'Connection test failed. Please try again.', 'seovela' ) ); ?>");
            },
            complete: function() {
                $button.text(originalText).prop("disabled", false);
            }
        });
    });

    // Temperature slider
    $("#seovela_ai_temperature").on("input", function() {
        $(".seovela-range-value").text($(this).val());
    });
});
</script>
