<?php
/**
 * Seovela Meta Settings View
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle settings save
if ( isset( $_POST['seovela_save_meta_settings'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seovela_meta_nonce'] ) ), 'seovela_save_meta_settings' ) ) {
    // Save all settings
    $settings_to_save = array(
        // Robots Meta
        'seovela_robots_index',
        'seovela_robots_noindex',
        'seovela_robots_nofollow',
        'seovela_robots_noarchive',
        'seovela_robots_noimageindex',
        'seovela_robots_nosnippet',
        // Advanced Robots
        'seovela_robots_max_snippet',
        'seovela_robots_max_video_preview',
        'seovela_robots_max_image_preview',
        // Archive Settings
        'seovela_noindex_empty_archives',
        // Title Settings
        'seovela_separator_character',
        'seovela_capitalize_titles',
        // OpenGraph
        'seovela_default_og_image',
        // Twitter
        'seovela_twitter_card_type',
        // Local SEO
        'seovela_knowledge_graph_type',
        'seovela_knowledge_graph_name',
        'seovela_knowledge_graph_logo',
        // Homepage
        'seovela_home_title',
        'seovela_home_description',
        // Post Types (Posts)
        'seovela_titles_post_title',
        'seovela_titles_post_description',
        'seovela_titles_post_type',
        'seovela_titles_post_robots',
        // Post Types (Pages)
        'seovela_titles_page_title',
        'seovela_titles_page_description',
        'seovela_titles_page_type',
        'seovela_titles_page_robots',
        // Taxonomies (Categories)
        'seovela_titles_category_title',
        'seovela_titles_category_description',
        'seovela_titles_category_robots',
        // Taxonomies (Tags)
        'seovela_titles_post_tag_title',
        'seovela_titles_post_tag_description',
        'seovela_titles_post_tag_robots',
    );

    foreach ( $settings_to_save as $setting ) {
        if ( isset( $_POST[ $setting ] ) ) {
            if ( in_array( $setting, array( 'seovela_default_og_image', 'seovela_knowledge_graph_logo' ) ) ) {
                update_option( $setting, esc_url_raw( wp_unslash( $_POST[ $setting ] ) ) );
            } elseif ( in_array( $setting, array( 'seovela_robots_max_snippet', 'seovela_robots_max_video_preview' ) ) ) {
                update_option( $setting, intval( wp_unslash( $_POST[ $setting ] ) ) );
            } else {
                update_option( $setting, sanitize_text_field( wp_unslash( $_POST[ $setting ] ) ) );
            }
        } else {
            update_option( $setting, '' );
        }
    }

    add_settings_error( 'seovela_settings', 'settings_saved', __( 'Settings saved successfully!', 'seovela' ), 'success' );
}

// Get current values with proper fallback for empty values
$robots_index = get_option( 'seovela_robots_index', 'index' );
$robots_index = ! empty( $robots_index ) ? $robots_index : 'index';

$robots_nofollow = get_option( 'seovela_robots_nofollow', '' );
$robots_noarchive = get_option( 'seovela_robots_noarchive', '' );
$robots_noimageindex = get_option( 'seovela_robots_noimageindex', '' );
$robots_nosnippet = get_option( 'seovela_robots_nosnippet', '' );

$max_snippet = get_option( 'seovela_robots_max_snippet', '-1' );
$max_snippet = ( $max_snippet !== '' && $max_snippet !== false ) ? $max_snippet : '-1';

$max_video_preview = get_option( 'seovela_robots_max_video_preview', '-1' );
$max_video_preview = ( $max_video_preview !== '' && $max_video_preview !== false ) ? $max_video_preview : '-1';

$max_image_preview = get_option( 'seovela_robots_max_image_preview', 'standard' );
$max_image_preview = ! empty( $max_image_preview ) ? $max_image_preview : 'standard';

$noindex_empty_archives = get_option( 'seovela_noindex_empty_archives', '1' );
$noindex_empty_archives = ( $noindex_empty_archives !== '' && $noindex_empty_archives !== false ) ? $noindex_empty_archives : '1';

$separator = get_option( 'seovela_separator_character', '-' );
$separator = ! empty( $separator ) ? $separator : '-';

$capitalize_titles = get_option( 'seovela_capitalize_titles', '' );
$default_og_image = get_option( 'seovela_default_og_image', '' );

$twitter_card_type = get_option( 'seovela_twitter_card_type', 'summary_large_image' );
$twitter_card_type = ! empty( $twitter_card_type ) ? $twitter_card_type : 'summary_large_image';

// Local SEO values
$knowledge_graph_type = get_option( 'seovela_knowledge_graph_type', 'person' );
$knowledge_graph_type = ! empty( $knowledge_graph_type ) ? $knowledge_graph_type : 'person';

$knowledge_graph_name = get_option( 'seovela_knowledge_graph_name', get_bloginfo( 'name' ) );
$knowledge_graph_name = ! empty( $knowledge_graph_name ) ? $knowledge_graph_name : get_bloginfo( 'name' );

$knowledge_graph_logo = get_option( 'seovela_knowledge_graph_logo', '' );

// Homepage values
$home_title = get_option( 'seovela_home_title', '' );
$home_description = get_option( 'seovela_home_description', '' );

// Post Types values
$titles_post_title = get_option( 'seovela_titles_post_title', '%title% %sep% %sitename%' );
$titles_post_title = ! empty( $titles_post_title ) ? $titles_post_title : '%title% %sep% %sitename%';

$titles_post_description = get_option( 'seovela_titles_post_description', '%excerpt%' );
$titles_post_description = ! empty( $titles_post_description ) ? $titles_post_description : '%excerpt%';

$titles_post_type = get_option( 'seovela_titles_post_type', 'Article' );
$titles_post_type = ! empty( $titles_post_type ) ? $titles_post_type : 'Article';

$titles_post_robots = get_option( 'seovela_titles_post_robots', 'index' );
$titles_post_robots = ! empty( $titles_post_robots ) ? $titles_post_robots : 'index';

$titles_page_title = get_option( 'seovela_titles_page_title', '%title% %sep% %sitename%' );
$titles_page_title = ! empty( $titles_page_title ) ? $titles_page_title : '%title% %sep% %sitename%';

$titles_page_description = get_option( 'seovela_titles_page_description', '' );

$titles_page_type = get_option( 'seovela_titles_page_type', 'WebPage' );
$titles_page_type = ! empty( $titles_page_type ) ? $titles_page_type : 'WebPage';

$titles_page_robots = get_option( 'seovela_titles_page_robots', 'index' );
$titles_page_robots = ! empty( $titles_page_robots ) ? $titles_page_robots : 'index';

// Taxonomies values
$titles_category_title = get_option( 'seovela_titles_category_title', '%term_title% Archives %sep% %sitename%' );
$titles_category_title = ! empty( $titles_category_title ) ? $titles_category_title : '%term_title% Archives %sep% %sitename%';

$titles_category_description = get_option( 'seovela_titles_category_description', '' );

$titles_category_robots = get_option( 'seovela_titles_category_robots', 'index' );
$titles_category_robots = ! empty( $titles_category_robots ) ? $titles_category_robots : 'index';

$titles_post_tag_title = get_option( 'seovela_titles_post_tag_title', '%term_title% Archives %sep% %sitename%' );
$titles_post_tag_title = ! empty( $titles_post_tag_title ) ? $titles_post_tag_title : '%term_title% Archives %sep% %sitename%';

$titles_post_tag_description = get_option( 'seovela_titles_post_tag_description', '' );

$titles_post_tag_robots = get_option( 'seovela_titles_post_tag_robots', 'index' );
$titles_post_tag_robots = ! empty( $titles_post_tag_robots ) ? $titles_post_tag_robots : 'index';

// Get active section
$active_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'global';
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta' ) ); ?>" class="seovela-header-tab active">
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=ai' ) ); ?>" class="seovela-header-tab">
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e( 'AI Optimization', 'seovela' ); ?>
                </a>
            </div>
        </div>
    </div>

    <?php settings_errors(); ?>

    <div class="seovela-page-body">
    <div class="seovela-settings-layout">
        <!-- Sidebar Navigation -->
        <div class="seovela-settings-sidebar">
            <ul class="seovela-settings-menu">
                <li class="seovela-menu-header"><?php esc_html_e( 'Global Meta', 'seovela' ); ?></li>
                <li class="<?php echo $active_section === 'global' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=global' ) ); ?>">
                        <span class="dashicons dashicons-admin-site"></span>
                        <?php esc_html_e( 'Global Meta', 'seovela' ); ?>
                    </a>
                </li>
                <li class="<?php echo $active_section === 'local-seo' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=local-seo' ) ); ?>">
                        <span class="dashicons dashicons-location"></span>
                        <?php esc_html_e( 'Local SEO', 'seovela' ); ?>
                    </a>
                </li>
                <li class="<?php echo $active_section === 'social' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=social' ) ); ?>">
                        <span class="dashicons dashicons-share"></span>
                        <?php esc_html_e( 'Social Meta', 'seovela' ); ?>
                    </a>
                </li>
                <li class="<?php echo $active_section === 'homepage' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=homepage' ) ); ?>">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php esc_html_e( 'Homepage', 'seovela' ); ?>
                    </a>
                </li>
                
                <li class="seovela-menu-header"><?php esc_html_e( 'Post Types', 'seovela' ); ?></li>
                <li class="<?php echo $active_section === 'posts' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=posts' ) ); ?>">
                        <span class="dashicons dashicons-admin-post"></span>
                        <?php esc_html_e( 'Posts', 'seovela' ); ?>
                    </a>
                </li>
                <li class="<?php echo $active_section === 'pages' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=pages' ) ); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e( 'Pages', 'seovela' ); ?>
                    </a>
                </li>
                
                <li class="seovela-menu-header"><?php esc_html_e( 'Taxonomies', 'seovela' ); ?></li>
                <li class="<?php echo $active_section === 'categories' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=categories' ) ); ?>">
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e( 'Categories', 'seovela' ); ?>
                    </a>
                </li>
                <li class="<?php echo $active_section === 'tags' ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seovela-settings&tab=meta&section=tags' ) ); ?>">
                        <span class="dashicons dashicons-tag"></span>
                        <?php esc_html_e( 'Tags', 'seovela' ); ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="seovela-settings-content-area">
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'seovela_save_meta_settings', 'seovela_meta_nonce' ); ?>

                <?php if ( $active_section === 'global' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Default values for robots meta tag. These can be changed for individual posts, taxonomies, etc.', 'seovela' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default Robots Meta', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-robots-checkbox-group">
                                        <label class="seovela-robots-checkbox">
                                            <input type="radio" name="seovela_robots_index" value="index" <?php checked( $robots_index, 'index' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'Index', 'seovela' ); ?></span>
                                            </span>
                                        </label>

                                        <label class="seovela-robots-checkbox">
                                            <input type="radio" name="seovela_robots_index" value="noindex" <?php checked( $robots_index, 'noindex' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M3 3L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'No Index', 'seovela' ); ?></span>
                                            </span>
                                        </label>

                                        <label class="seovela-robots-checkbox">
                                            <input type="checkbox" name="seovela_robots_nofollow" value="1" <?php checked( $robots_nofollow, '1' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <span class="dashicons dashicons-admin-links"></span>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'No Follow', 'seovela' ); ?></span>
                                            </span>
                                        </label>

                                        <label class="seovela-robots-checkbox">
                                            <input type="checkbox" name="seovela_robots_noarchive" value="1" <?php checked( $robots_noarchive, '1' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <span class="dashicons dashicons-archive"></span>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'No Archive', 'seovela' ); ?></span>
                                            </span>
                                        </label>

                                        <label class="seovela-robots-checkbox">
                                            <input type="checkbox" name="seovela_robots_noimageindex" value="1" <?php checked( $robots_noimageindex, '1' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <span class="dashicons dashicons-format-image"></span>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'No Image Index', 'seovela' ); ?></span>
                                            </span>
                                        </label>

                                        <label class="seovela-robots-checkbox">
                                            <input type="checkbox" name="seovela_robots_nosnippet" value="1" <?php checked( $robots_nosnippet, '1' ); ?> />
                                            <span class="seovela-robots-checkbox-content">
                                                <span class="seovela-robots-icon">
                                                    <span class="dashicons dashicons-text"></span>
                                                </span>
                                                <span class="seovela-robots-label"><?php esc_html_e( 'No Snippet', 'seovela' ); ?></span>
                                            </span>
                                        </label>
                                    </div>
                                    <p class="description" style="margin-top: 16px;"><?php esc_html_e( 'Default values for robots meta tag. These can be changed for individual posts, taxonomies, etc.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Advanced Robots Meta', 'seovela' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_robots_max_snippet">
                                        <?php esc_html_e( 'Snippet', 'seovela' ); ?>
                                        <span class="seovela-help-tip" title="<?php esc_attr_e( 'Specify maximum text length, in characters, of a snippet for your page. Use -1 for no limit.', 'seovela' ); ?>">?</span>
                                    </label>
                                </th>
                                <td>
                                    <div class="seovela-advanced-meta-row">
                                        <input type="number" id="seovela_robots_max_snippet" name="seovela_robots_max_snippet" value="<?php echo esc_attr( $max_snippet ); ?>" class="small-text" />
                                        <span class="seovela-value-label"><?php echo esc_html( $max_snippet == '-1' ? __( '(No limit)', 'seovela' ) : sprintf( __( '%d characters', 'seovela' ), intval( $max_snippet ) ) ); ?></span>
                                        <p class="description" style="margin: 5px 0 0 0;"><?php esc_html_e( 'Max snippet length. Use -1 for no limit, or set a specific character count.', 'seovela' ); ?></p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_robots_max_video_preview">
                                        <?php esc_html_e( 'Video Preview', 'seovela' ); ?>
                                        <span class="seovela-help-tip" title="<?php esc_attr_e( 'Specify maximum duration in seconds of an animated video preview. Use -1 for no limit.', 'seovela' ); ?>">?</span>
                                    </label>
                                </th>
                                <td>
                                    <div class="seovela-advanced-meta-row">
                                        <input type="number" id="seovela_robots_max_video_preview" name="seovela_robots_max_video_preview" value="<?php echo esc_attr( $max_video_preview ); ?>" class="small-text" />
                                        <span class="seovela-value-label"><?php echo esc_html( $max_video_preview == '-1' ? __( '(No limit)', 'seovela' ) : sprintf( __( '%d seconds', 'seovela' ), intval( $max_video_preview ) ) ); ?></span>
                                        <p class="description" style="margin: 5px 0 0 0;"><?php esc_html_e( 'Max video preview duration. Use -1 for no limit, or set seconds.', 'seovela' ); ?></p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_robots_max_image_preview">
                                        <?php esc_html_e( 'Image Preview', 'seovela' ); ?>
                                        <span class="seovela-help-tip" title="<?php esc_attr_e( 'Specify maximum size of image preview shown for images on this page.', 'seovela' ); ?>">?</span>
                                    </label>
                                </th>
                                <td>
                                    <div class="seovela-advanced-meta-row">
                                        <select id="seovela_robots_max_image_preview" name="seovela_robots_max_image_preview">
                                            <option value="none" <?php selected( $max_image_preview, 'none' ); ?>><?php esc_html_e( 'None - No image preview', 'seovela' ); ?></option>
                                            <option value="standard" <?php selected( $max_image_preview, 'standard' ); ?>><?php esc_html_e( 'Standard - Default size', 'seovela' ); ?></option>
                                            <option value="large" <?php selected( $max_image_preview, 'large' ); ?>><?php esc_html_e( 'Large - Maximum size', 'seovela' ); ?></option>
                                        </select>
                                        <p class="description" style="margin: 5px 0 0 0;"><?php esc_html_e( 'Maximum size of image preview shown in search results.', 'seovela' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Archive Settings', 'seovela' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Empty Archives', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-toggle-item">
                                        <label class="seovela-toggle-modern">
                                            <input type="checkbox" name="seovela_noindex_empty_archives" value="1" <?php checked( $noindex_empty_archives, '1' ); ?> />
                                            <span class="seovela-toggle-slider-modern"></span>
                                        </label>
                                        <span class="seovela-toggle-label"><?php esc_html_e( 'Noindex Empty Category and Tag Archives', 'seovela' ); ?></span>
                                        <p class="description"><?php esc_html_e( 'Setting empty archives to noindex is useful for avoiding indexation of thin content pages and dilution of page rank. As soon as a post is added, the page is updated to index.', 'seovela' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Title Settings', 'seovela' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_separator_character"><?php esc_html_e( 'Separator Character', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <div class="seovela-separator-selector">
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value="-" <?php checked( $separator, '-' ); ?> />
                                            <span class="seovela-separator-box">-</span>
                                        </label>
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value="|" <?php checked( $separator, '|' ); ?> />
                                            <span class="seovela-separator-box">|</span>
                                        </label>
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value="~" <?php checked( $separator, '~' ); ?> />
                                            <span class="seovela-separator-box">~</span>
                                        </label>
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value="•" <?php checked( $separator, '•' ); ?> />
                                            <span class="seovela-separator-box">•</span>
                                        </label>
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value=">" <?php checked( $separator, '>' ); ?> />
                                            <span class="seovela-separator-box">&gt;</span>
                                        </label>
                                        <label class="seovela-separator-option">
                                            <input type="radio" name="seovela_separator_character" value="<" <?php checked( $separator, '<' ); ?> />
                                            <span class="seovela-separator-box">&lt;</span>
                                        </label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'You can use the separator character in titles by inserting %separator% or %sep% in the title fields.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Capitalize Titles', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-toggle-item">
                                        <label class="seovela-toggle-modern">
                                            <input type="checkbox" name="seovela_capitalize_titles" value="1" <?php checked( $capitalize_titles, '1' ); ?> />
                                            <span class="seovela-toggle-slider-modern"></span>
                                        </label>
                                        <span class="seovela-toggle-label"><?php esc_html_e( 'Capitalize Titles', 'seovela' ); ?></span>
                                        <p class="description"><?php esc_html_e( 'Automatically capitalize the first character of each word in the titles.', 'seovela' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'local-seo' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Local SEO Settings', 'seovela' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Configure your website for Local SEO to help Google understand your business.', 'seovela' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_knowledge_graph_type"><?php esc_html_e( 'Person or Company', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <select id="seovela_knowledge_graph_type" name="seovela_knowledge_graph_type">
                                        <option value="person" <?php selected( $knowledge_graph_type, 'person' ); ?>><?php esc_html_e( 'Person', 'seovela' ); ?></option>
                                        <option value="company" <?php selected( $knowledge_graph_type, 'company' ); ?>><?php esc_html_e( 'Organization', 'seovela' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose whether the site represents a person or an organization.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_knowledge_graph_name"><?php esc_html_e( 'Name', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_knowledge_graph_name" name="seovela_knowledge_graph_name" value="<?php echo esc_attr( $knowledge_graph_name ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Your name or company name', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_knowledge_graph_logo"><?php esc_html_e( 'Logo', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <div class="seovela-media-upload-box">
                                        <?php if ( $knowledge_graph_logo ) : ?>
                                            <div class="seovela-media-preview seovela-media-preview-small">
                                                <img src="<?php echo esc_url( $knowledge_graph_logo ); ?>" alt="Logo" />
                                                <div class="seovela-media-overlay">
                                                    <button type="button" class="button seovela-upload-image-button" data-target="seovela_knowledge_graph_logo">
                                                        <span class="dashicons dashicons-edit"></span>
                                                        <?php esc_html_e( 'Change', 'seovela' ); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <div class="seovela-media-placeholder seovela-media-placeholder-small">
                                                <span class="dashicons dashicons-format-image"></span>
                                                <p><?php esc_html_e( 'No logo', 'seovela' ); ?></p>
                                                <button type="button" class="button button-secondary seovela-upload-image-button" data-target="seovela_knowledge_graph_logo">
                                                    <?php esc_html_e( 'Upload Logo', 'seovela' ); ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <input type="hidden" id="seovela_knowledge_graph_logo" name="seovela_knowledge_graph_logo" value="<?php echo esc_url( $knowledge_graph_logo ); ?>" />
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Upload a logo for your business. Minimum size: 112x112px.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'homepage' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Homepage Settings', 'seovela' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Configure the title and meta description for your homepage.', 'seovela' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_home_title"><?php esc_html_e( 'Homepage Title', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_home_title" name="seovela_home_title" value="<?php echo esc_attr( $home_title ); ?>" class="large-text seovela-seo-input" data-min="30" data-optimal="50" data-max="60" />
                                    <div class="seovela-seo-meter">
                                        <div class="seovela-seo-meter-bar">
                                            <div class="seovela-seo-meter-fill" id="seovela_home_title_meter"></div>
                                        </div>
                                        <div class="seovela-seo-meter-info">
                                            <span class="seovela-seo-meter-count"><span id="seovela_home_title_count">0</span> / 60 <?php esc_html_e( 'characters', 'seovela' ); ?></span>
                                            <span class="seovela-seo-meter-status" id="seovela_home_title_status"></span>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'The title tag for your homepage. Google typically displays 50-60 characters.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_home_description"><?php esc_html_e( 'Homepage Description', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="seovela_home_description" name="seovela_home_description" rows="3" class="large-text seovela-seo-input" data-min="70" data-optimal="120" data-max="160"><?php echo esc_textarea( $home_description ); ?></textarea>
                                    <div class="seovela-seo-meter">
                                        <div class="seovela-seo-meter-bar">
                                            <div class="seovela-seo-meter-fill" id="seovela_home_description_meter"></div>
                                        </div>
                                        <div class="seovela-seo-meter-info">
                                            <span class="seovela-seo-meter-count"><span id="seovela_home_description_count">0</span> / 160 <?php esc_html_e( 'characters', 'seovela' ); ?></span>
                                            <span class="seovela-seo-meter-status" id="seovela_home_description_status"></span>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'The meta description for your homepage. Google typically displays 150-160 characters.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'social' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'OpenGraph Settings', 'seovela' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_default_og_image"><?php esc_html_e( 'Default OpenGraph Thumbnail', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <div class="seovela-media-upload-box">
                                        <?php if ( $default_og_image ) : ?>
                                            <div class="seovela-media-preview">
                                                <img src="<?php echo esc_url( $default_og_image ); ?>" alt="OpenGraph Image" />
                                                <div class="seovela-media-overlay">
                                                    <button type="button" class="button seovela-upload-image-button">
                                                        <span class="dashicons dashicons-edit"></span>
                                                        <?php esc_html_e( 'Change', 'seovela' ); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <div class="seovela-media-placeholder">
                                                <span class="dashicons dashicons-format-image"></span>
                                                <p><?php esc_html_e( 'No image selected', 'seovela' ); ?></p>
                                                <button type="button" class="button button-secondary seovela-upload-image-button">
                                                    <?php esc_html_e( 'Add or Upload File', 'seovela' ); ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <input type="hidden" id="seovela_default_og_image" name="seovela_default_og_image" value="<?php echo esc_url( $default_og_image ); ?>" />
                                    </div>
                                    <p class="description"><?php esc_html_e( 'When a featured image or an OpenGraph Image is not set for individual posts/pages/CPTs, this image will be used as a fallback thumbnail when your post is shared on Facebook. The recommended image size is 1200 x 630 pixels.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Twitter Card Settings', 'seovela' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_twitter_card_type"><?php esc_html_e( 'Default Twitter Card Type', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <select id="seovela_twitter_card_type" name="seovela_twitter_card_type">
                                        <option value="summary" <?php selected( $twitter_card_type, 'summary' ); ?>><?php esc_html_e( 'Summary Card', 'seovela' ); ?></option>
                                        <option value="summary_large_image" <?php selected( $twitter_card_type, 'summary_large_image' ); ?>><?php esc_html_e( 'Summary Card with Large Image', 'seovela' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Card type selected when creating a new post. This will also be applied for posts without a card type selected.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'posts' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Posts Settings', 'seovela' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Configure default SEO settings for your single posts.', 'seovela' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_post_title"><?php esc_html_e( 'Single Post Title', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_titles_post_title" name="seovela_titles_post_title" value="<?php echo esc_attr( $titles_post_title ); ?>" class="large-text" />
                                    <p class="description"><?php esc_html_e( 'Use %title%, %sep%, %sitename% variables.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_post_description"><?php esc_html_e( 'Single Post Description', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="seovela_titles_post_description" name="seovela_titles_post_description" rows="3" class="large-text"><?php echo esc_textarea( $titles_post_description ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Use %excerpt% variable to auto-populate from excerpt.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_post_type"><?php esc_html_e( 'Schema Type', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <select id="seovela_titles_post_type" name="seovela_titles_post_type">
                                        <option value="Article" <?php selected( $titles_post_type, 'Article' ); ?>><?php esc_html_e( 'Article', 'seovela' ); ?></option>
                                        <option value="BlogPosting" <?php selected( $titles_post_type, 'BlogPosting' ); ?>><?php esc_html_e( 'Blog Post', 'seovela' ); ?></option>
                                        <option value="NewsArticle" <?php selected( $titles_post_type, 'NewsArticle' ); ?>><?php esc_html_e( 'News Article', 'seovela' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Select the default Schema type for your posts.', 'seovela' ); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-radio-group">
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_post_robots" value="index" <?php checked( $titles_post_robots, 'index' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-yes"></span>
                                                    <?php esc_html_e( 'Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_post_robots" value="noindex" <?php checked( $titles_post_robots, 'noindex' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-no"></span>
                                                    <?php esc_html_e( 'No Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'pages' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Pages Settings', 'seovela' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Configure default SEO settings for your pages.', 'seovela' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_page_title"><?php esc_html_e( 'Single Page Title', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_titles_page_title" name="seovela_titles_page_title" value="<?php echo esc_attr( $titles_page_title ); ?>" class="large-text" />
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_page_description"><?php esc_html_e( 'Single Page Description', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="seovela_titles_page_description" name="seovela_titles_page_description" rows="3" class="large-text"><?php echo esc_textarea( $titles_page_description ); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_page_type"><?php esc_html_e( 'Schema Type', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <select id="seovela_titles_page_type" name="seovela_titles_page_type">
                                        <option value="WebPage" <?php selected( $titles_page_type, 'WebPage' ); ?>><?php esc_html_e( 'Web Page', 'seovela' ); ?></option>
                                        <option value="Article" <?php selected( $titles_page_type, 'Article' ); ?>><?php esc_html_e( 'Article', 'seovela' ); ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-radio-group">
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_page_robots" value="index" <?php checked( $titles_page_robots, 'index' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-yes"></span>
                                                    <?php esc_html_e( 'Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_page_robots" value="noindex" <?php checked( $titles_page_robots, 'noindex' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-no"></span>
                                                    <?php esc_html_e( 'No Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'categories' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Categories Settings', 'seovela' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_category_title"><?php esc_html_e( 'Category Title', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_titles_category_title" name="seovela_titles_category_title" value="<?php echo esc_attr( $titles_category_title ); ?>" class="large-text" />
                                    <p class="description"><?php esc_html_e( 'Use %term_title%, %sep%, %sitename% variables.', 'seovela' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_category_description"><?php esc_html_e( 'Category Description', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="seovela_titles_category_description" name="seovela_titles_category_description" rows="3" class="large-text"><?php echo esc_textarea( $titles_category_description ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-radio-group">
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_category_robots" value="index" <?php checked( $titles_category_robots, 'index' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-yes"></span>
                                                    <?php esc_html_e( 'Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_category_robots" value="noindex" <?php checked( $titles_category_robots, 'noindex' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-no"></span>
                                                    <?php esc_html_e( 'No Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php elseif ( $active_section === 'tags' ) : ?>
                    <div class="seovela-settings-section">
                        <h2><?php esc_html_e( 'Tags Settings', 'seovela' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_post_tag_title"><?php esc_html_e( 'Tag Title', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="seovela_titles_post_tag_title" name="seovela_titles_post_tag_title" value="<?php echo esc_attr( $titles_post_tag_title ); ?>" class="large-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="seovela_titles_post_tag_description"><?php esc_html_e( 'Tag Description', 'seovela' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="seovela_titles_post_tag_description" name="seovela_titles_post_tag_description" rows="3" class="large-text"><?php echo esc_textarea( $titles_post_tag_description ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Robots Meta', 'seovela' ); ?></th>
                                <td>
                                    <div class="seovela-radio-group">
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_post_tag_robots" value="index" <?php checked( $titles_post_tag_robots, 'index' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-yes"></span>
                                                    <?php esc_html_e( 'Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                        <label class="seovela-radio-option">
                                            <input type="radio" name="seovela_titles_post_tag_robots" value="noindex" <?php checked( $titles_post_tag_robots, 'noindex' ); ?> />
                                            <div class="seovela-radio-content">
                                                <span class="seovela-radio-title">
                                                    <span class="dashicons dashicons-no"></span>
                                                    <?php esc_html_e( 'No Index', 'seovela' ); ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php else : ?>
                    <div class="seovela-settings-section">
                        <h2><?php echo esc_html( ucfirst( $active_section ) ); ?> <?php esc_html_e( 'Settings', 'seovela' ); ?></h2>
                        <p class="seovela-coming-soon">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Settings for this section are coming soon. You can still customize meta tags for individual items using the Seovela SEO meta box in the editor.', 'seovela' ); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <p class="submit">
                    <button type="submit" name="seovela_save_meta_settings" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'Save Changes', 'seovela' ); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
    </div><!-- .seovela-page-body -->
</div><!-- .seovela-premium-page -->

<script>
jQuery(document).ready(function($) {
    // Media Uploader
    var mediaUploader;
    var currentTarget;

    $(".seovela-upload-image-button").on("click", function(e) {
        e.preventDefault();

        var $button = $(this);
        var $box = $button.closest(".seovela-media-upload-box");
        currentTarget = $box.find("input[type='hidden']").attr("id");

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: "<?php echo esc_js( __( 'Choose Image', 'seovela' ) ); ?>",
            button: {
                text: "<?php echo esc_js( __( 'Use this image', 'seovela' ) ); ?>"
            },
            multiple: false
        });

        mediaUploader.on("select", function() {
            var attachment = mediaUploader.state().get("selection").first().toJSON();
            var $box = $("#" + currentTarget).closest(".seovela-media-upload-box");

            // Update hidden input
            $("#" + currentTarget).val(attachment.url);

            // Replace placeholder with preview
            var isSmall = $box.find(".seovela-media-placeholder-small").length > 0;
            var sizeClass = isSmall ? "seovela-media-preview-small" : "";

            $box.html(
                '<div class="seovela-media-preview ' + sizeClass + '">' +
                    '<img src="' + attachment.url + '" alt="Image" />' +
                    '<div class="seovela-media-overlay">' +
                        '<button type="button" class="button seovela-upload-image-button">' +
                            '<span class="dashicons dashicons-edit"></span> Change' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<input type="hidden" id="' + currentTarget + '" name="' + currentTarget + '" value="' + attachment.url + '" />'
            );
        });

        mediaUploader.open();
    });

    // Re-bind for dynamically created buttons
    $(document).on("click", ".seovela-upload-image-button", function(e) {
        if (!$(this).hasClass("seovela-upload-image-button")) return;
        e.preventDefault();
        $(this).trigger("click");
    });

    // SEO Meter functionality
    function updateSeoMeter(input) {
        var $input = $(input);
        var id = $input.attr("id");
        var text = $input.val();
        var length = text.length;

        var minLength = parseInt($input.data("min")) || 30;
        var optimalLength = parseInt($input.data("optimal")) || 50;
        var maxLength = parseInt($input.data("max")) || 60;

        var $meter = $("#" + id + "_meter");
        var $count = $("#" + id + "_count");
        var $status = $("#" + id + "_status");

        // Update character count
        $count.text(length);

        // Calculate percentage (capped at 100%)
        var percentage = Math.min((length / maxLength) * 100, 100);
        $meter.css("width", percentage + "%");

        // Remove all status classes
        $meter.removeClass("seovela-meter-empty seovela-meter-short seovela-meter-good seovela-meter-warning seovela-meter-danger");
        $status.removeClass("seovela-status-empty seovela-status-short seovela-status-good seovela-status-warning seovela-status-danger");

        // Determine status and apply classes
        if (length === 0) {
            $meter.addClass("seovela-meter-empty");
            $status.addClass("seovela-status-empty").text("<?php echo esc_js( __( 'Empty', 'seovela' ) ); ?>");
        } else if (length < minLength) {
            $meter.addClass("seovela-meter-short");
            $status.addClass("seovela-status-short").text("<?php echo esc_js( __( 'Too short', 'seovela' ) ); ?>");
        } else if (length >= minLength && length <= optimalLength) {
            $meter.addClass("seovela-meter-good");
            $status.addClass("seovela-status-good").text("<?php echo esc_js( __( 'Good', 'seovela' ) ); ?>");
        } else if (length > optimalLength && length <= maxLength) {
            $meter.addClass("seovela-meter-warning");
            $status.addClass("seovela-status-warning").text("<?php echo esc_js( __( 'Acceptable', 'seovela' ) ); ?>");
        } else {
            $meter.addClass("seovela-meter-danger");
            $status.addClass("seovela-status-danger").text("<?php echo esc_js( __( 'Too long', 'seovela' ) ); ?>");
        }
    }

    // Initialize meters on page load
    $(".seovela-seo-input").each(function() {
        updateSeoMeter(this);
    });

    // Update meters on input
    $(".seovela-seo-input").on("input keyup", function() {
        updateSeoMeter(this);
    });
});
</script>
