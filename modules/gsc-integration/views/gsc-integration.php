<?php
/**
 * Google Search Console Admin Page - Centralized OAuth
 *
 * No credentials required - uses Seovela's central OAuth app
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gsc = Seovela_Gsc_Integration::get_instance();

// Get connection status and property info
$user_id = get_current_user_id();
$is_connected = $gsc->is_connected( $user_id );
$has_property = $gsc->has_property( $user_id );
$connected_email = $gsc->get_connected_email( $user_id );
$property = $gsc->get_property( $user_id );

// Check for success message
$just_connected = isset( $_GET['connected'] ) && $_GET['connected'] === '1';

// Get sites if connected but no property selected
$sites = array();
$sync_error = null;
if ( $is_connected && ! $has_property ) {
	$sites = $gsc->get_sites( $user_id );
	if ( is_wp_error( $sites ) ) {
		$sync_error = $sites->get_error_message();
		$sites = array();
	}
}

// Get stats if property is selected
$stats = array();
$top_queries = array();
if ( $is_connected && $has_property ) {
	$stats = $gsc->get_site_stats( 30, $user_id );
	$top_queries = $gsc->get_top_queries( 10, 'clicks', $user_id );
}
?>

<div class="wrap seovela-gsc-wrap">
	
	<!-- Animated Background -->
	<div class="seovela-gsc-bg">
		<div class="seovela-gsc-bg-gradient"></div>
		<div class="seovela-gsc-bg-orbs">
			<div class="orb orb-1"></div>
			<div class="orb orb-2"></div>
			<div class="orb orb-3"></div>
		</div>
	</div>

	<?php settings_errors( 'seovela_gsc' ); ?>

	<?php if ( ! $is_connected ) : ?>
		<!-- Connect to Google - Single Click! -->
		<div class="gsc-connect-container">
			<div class="gsc-hero">
				<div class="gsc-google-logo">
					<svg viewBox="0 0 24 24" width="72" height="72">
						<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
						<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
						<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
						<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
					</svg>
				</div>
				
				<h1 class="gsc-hero-title"><?php esc_html_e( 'Connect Google Search Console', 'seovela' ); ?></h1>
				<p class="gsc-hero-subtitle"><?php esc_html_e( 'Get insights into how Google sees your site. Track clicks, impressions, rankings, and discover top-performing keywords.', 'seovela' ); ?></p>
			</div>

			<div class="gsc-connect-section">
				<a href="<?php echo esc_url( $gsc->get_auth_url() ); ?>" class="gsc-connect-btn">
					<svg viewBox="0 0 24 24" width="24" height="24" class="google-icon">
						<path fill="#fff" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
						<path fill="#fff" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
						<path fill="#fff" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
						<path fill="#fff" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
					</svg>
					<span><?php esc_html_e( 'Sign in with Google', 'seovela' ); ?></span>
				</a>
				
				<p class="gsc-connect-note">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
					</svg>
					<?php esc_html_e( 'Secure one-click connection. We only request read-only access to your Search Console data.', 'seovela' ); ?>
				</p>
			</div>

			<!-- Features Grid -->
			<div class="gsc-features-grid">
				<div class="gsc-feature-card">
					<div class="feature-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
							<polyline points="10 17 15 12 10 7"/>
							<line x1="15" y1="12" x2="3" y2="12"/>
						</svg>
					</div>
					<h3><?php esc_html_e( 'Track Clicks', 'seovela' ); ?></h3>
					<p><?php esc_html_e( 'See how many visitors come from Google search results.', 'seovela' ); ?></p>
				</div>
				
				<div class="gsc-feature-card">
					<div class="feature-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
							<circle cx="12" cy="12" r="3"/>
						</svg>
					</div>
					<h3><?php esc_html_e( 'Monitor Impressions', 'seovela' ); ?></h3>
					<p><?php esc_html_e( 'Know how often your pages appear in search results.', 'seovela' ); ?></p>
				</div>
				
				<div class="gsc-feature-card">
					<div class="feature-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
							<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
						</svg>
					</div>
					<h3><?php esc_html_e( 'Check Rankings', 'seovela' ); ?></h3>
					<p><?php esc_html_e( 'Track your average position in search results.', 'seovela' ); ?></p>
				</div>
				
				<div class="gsc-feature-card">
					<div class="feature-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<line x1="21" y1="21" x2="16.65" y2="16.65"/>
						</svg>
					</div>
					<h3><?php esc_html_e( 'Discover Keywords', 'seovela' ); ?></h3>
					<p><?php esc_html_e( 'Find the search queries bringing traffic to your site.', 'seovela' ); ?></p>
				</div>
			</div>
		</div>

	<?php elseif ( ! $has_property ) : ?>
		<!-- Select Property -->
		<div class="gsc-connect-container">
			<div class="gsc-hero">
				<div class="gsc-google-logo gsc-success-logo">
					<svg viewBox="0 0 24 24" width="72" height="72" fill="none" stroke="#22c55e" stroke-width="2">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
						<polyline points="22 4 12 14.01 9 11.01"/>
					</svg>
				</div>
				
				<h1 class="gsc-hero-title"><?php esc_html_e( 'Select Your Property', 'seovela' ); ?></h1>
				<p class="gsc-hero-subtitle">
					<?php if ( $connected_email ) : ?>
						<?php printf( esc_html__( 'Connected as %s. Choose which website you want to track.', 'seovela' ), '<strong>' . esc_html( $connected_email ) . '</strong>' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Choose which website you want to track from your Google Search Console account.', 'seovela' ); ?>
					<?php endif; ?>
				</p>
			</div>

			<?php if ( $sync_error ) : ?>
				<div class="gsc-error-notice">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"/>
						<line x1="12" y1="8" x2="12" y2="12"/>
						<line x1="12" y1="16" x2="12.01" y2="16"/>
					</svg>
					<span><?php echo esc_html( $sync_error ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $sites ) ) : ?>
				<div class="gsc-property-selector">
					<h2><?php esc_html_e( 'Your Properties', 'seovela' ); ?></h2>
					
					<div class="gsc-properties-grid">
						<?php foreach ( $sites as $site ) : 
							$site_url = $site['siteUrl'];
							$permission = $site['permissionLevel'];
							$is_verified = in_array( $permission, array( 'siteOwner', 'siteFullUser', 'siteRestrictedUser' ), true );
						?>
							<div class="gsc-property-card <?php echo $is_verified ? '' : 'unverified'; ?>" data-url="<?php echo esc_attr( $site_url ); ?>">
								<div class="property-icon">
									<?php if ( strpos( $site_url, 'sc-domain:' ) === 0 ) : ?>
										<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
											<circle cx="12" cy="12" r="10"/>
											<line x1="2" y1="12" x2="22" y2="12"/>
											<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
										</svg>
									<?php else : ?>
										<svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2">
											<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
											<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
										</svg>
									<?php endif; ?>
								</div>
								<div class="property-info">
									<span class="property-url"><?php echo esc_html( str_replace( 'sc-domain:', '', $site_url ) ); ?></span>
									<span class="property-type">
										<?php if ( strpos( $site_url, 'sc-domain:' ) === 0 ) : ?>
											<?php esc_html_e( 'Domain Property', 'seovela' ); ?>
										<?php else : ?>
											<?php esc_html_e( 'URL Prefix', 'seovela' ); ?>
										<?php endif; ?>
									</span>
								</div>
								<?php if ( $is_verified ) : ?>
									<button type="button" class="gsc-btn gsc-btn-select select-property" data-url="<?php echo esc_attr( $site_url ); ?>">
										<?php esc_html_e( 'Select', 'seovela' ); ?>
									</button>
								<?php else : ?>
									<span class="property-unverified"><?php esc_html_e( 'Not Verified', 'seovela' ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php else : ?>
				<div class="gsc-no-properties">
					<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="12" cy="12" r="10"/>
						<line x1="12" y1="8" x2="12" y2="12"/>
						<line x1="12" y1="16" x2="12.01" y2="16"/>
					</svg>
					<h3><?php esc_html_e( 'No Properties Found', 'seovela' ); ?></h3>
					<p><?php esc_html_e( 'You don\'t have any properties in your Google Search Console account, or there was an error fetching them.', 'seovela' ); ?></p>
					<a href="https://search.google.com/search-console" target="_blank" class="gsc-btn gsc-btn-secondary">
						<?php esc_html_e( 'Add Property in Search Console', 'seovela' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<!-- Disconnect Link -->
			<div class="gsc-disconnect-link">
				<button type="button" class="gsc-link-btn" id="disconnect-gsc">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
						<polyline points="16 17 21 12 16 7"/>
						<line x1="21" y1="12" x2="9" y2="12"/>
					</svg>
					<?php esc_html_e( 'Disconnect Google Account', 'seovela' ); ?>
				</button>
			</div>
		</div>

	<?php else : ?>
		<!-- Connected State - Dashboard -->
		<div class="gsc-dashboard">
			
			<?php if ( $just_connected ) : ?>
				<div class="gsc-success-banner">
					<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
						<polyline points="22 4 12 14.01 9 11.01"/>
					</svg>
					<span><?php esc_html_e( 'Successfully connected! Click "Sync Now" to fetch your Search Console data.', 'seovela' ); ?></span>
				</div>
			<?php endif; ?>

			<!-- Header -->
			<header class="gsc-dashboard-header">
				<div class="gsc-header-left">
					<div class="gsc-google-badge">
						<svg viewBox="0 0 24 24" width="28" height="28">
							<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
							<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
							<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
							<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
						</svg>
					</div>
					<div class="gsc-header-text">
						<h1><?php esc_html_e( 'Google Search Console', 'seovela' ); ?></h1>
						<div class="gsc-connection-info">
							<span class="gsc-status-badge connected">
								<span class="status-dot"></span>
								<?php esc_html_e( 'Connected', 'seovela' ); ?>
							</span>
							<span class="gsc-property"><?php echo esc_html( str_replace( 'sc-domain:', '', $property ) ); ?></span>
							<?php if ( $connected_email ) : ?>
								<span class="gsc-email">(<?php echo esc_html( $connected_email ); ?>)</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="gsc-header-actions">
					<select id="date-range-selector" class="gsc-select">
						<option value="7"><?php esc_html_e( 'Last 7 days', 'seovela' ); ?></option>
						<option value="30" selected><?php esc_html_e( 'Last 30 days', 'seovela' ); ?></option>
						<option value="90"><?php esc_html_e( 'Last 90 days', 'seovela' ); ?></option>
					</select>
					<button type="button" class="gsc-btn gsc-btn-secondary" id="sync-gsc-data">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
							<path d="M3 3v5h5"/>
							<path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
							<path d="M16 21h5v-5"/>
						</svg>
						<span><?php esc_html_e( 'Sync Now', 'seovela' ); ?></span>
					</button>
					<button type="button" class="gsc-btn gsc-btn-ghost" id="disconnect-gsc">
						<?php esc_html_e( 'Disconnect', 'seovela' ); ?>
					</button>
				</div>
			</header>

			<!-- Stats Overview -->
			<section class="gsc-stats-section">
				<div class="gsc-stats-grid">
					<div class="gsc-stat-card stat-clicks">
						<div class="gsc-stat-icon">
							<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
								<polyline points="10 17 15 12 10 7"/>
								<line x1="15" y1="12" x2="3" y2="12"/>
							</svg>
						</div>
						<div class="gsc-stat-content">
							<div class="gsc-stat-value" id="stat-clicks"><?php echo esc_html( number_format( $stats['total_clicks'] ) ); ?></div>
							<div class="gsc-stat-label"><?php esc_html_e( 'Total Clicks', 'seovela' ); ?></div>
							<div class="gsc-stat-change <?php echo $stats['clicks_change'] >= 0 ? 'positive' : 'negative'; ?>">
								<span class="change-arrow"><?php echo $stats['clicks_change'] >= 0 ? '↑' : '↓'; ?></span>
								<span class="change-value"><?php echo esc_html( abs( $stats['clicks_change'] ) ); ?>%</span>
								<span class="change-period"><?php esc_html_e( 'vs previous', 'seovela' ); ?></span>
							</div>
						</div>
					</div>

					<div class="gsc-stat-card stat-impressions">
						<div class="gsc-stat-icon">
							<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
								<circle cx="12" cy="12" r="3"/>
							</svg>
						</div>
						<div class="gsc-stat-content">
							<div class="gsc-stat-value" id="stat-impressions"><?php echo esc_html( number_format( $stats['total_impressions'] ) ); ?></div>
							<div class="gsc-stat-label"><?php esc_html_e( 'Impressions', 'seovela' ); ?></div>
							<div class="gsc-stat-change <?php echo $stats['impressions_change'] >= 0 ? 'positive' : 'negative'; ?>">
								<span class="change-arrow"><?php echo $stats['impressions_change'] >= 0 ? '↑' : '↓'; ?></span>
								<span class="change-value"><?php echo esc_html( abs( $stats['impressions_change'] ) ); ?>%</span>
								<span class="change-period"><?php esc_html_e( 'vs previous', 'seovela' ); ?></span>
							</div>
						</div>
					</div>

					<div class="gsc-stat-card stat-ctr">
						<div class="gsc-stat-icon">
							<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
							</svg>
						</div>
						<div class="gsc-stat-content">
							<div class="gsc-stat-value" id="stat-ctr"><?php echo esc_html( number_format( $stats['avg_ctr'], 2 ) ); ?>%</div>
							<div class="gsc-stat-label"><?php esc_html_e( 'Average CTR', 'seovela' ); ?></div>
							<div class="gsc-stat-hint"><?php esc_html_e( 'Click-through rate', 'seovela' ); ?></div>
						</div>
					</div>

					<div class="gsc-stat-card stat-position">
						<div class="gsc-stat-icon">
							<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
								<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
							</svg>
						</div>
						<div class="gsc-stat-content">
							<div class="gsc-stat-value" id="stat-position"><?php echo esc_html( number_format( $stats['avg_position'], 1 ) ); ?></div>
							<div class="gsc-stat-label"><?php esc_html_e( 'Avg Position', 'seovela' ); ?></div>
							<div class="gsc-stat-hint"><?php esc_html_e( 'Lower is better', 'seovela' ); ?></div>
						</div>
					</div>
				</div>
			</section>

			<!-- Chart Section -->
			<section class="gsc-chart-section">
				<div class="gsc-section-header">
					<h2>
						<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="20" x2="18" y2="10"/>
							<line x1="12" y1="20" x2="12" y2="4"/>
							<line x1="6" y1="20" x2="6" y2="14"/>
						</svg>
						<?php esc_html_e( 'Performance Over Time', 'seovela' ); ?>
					</h2>
				</div>
				<div class="gsc-chart-container">
					<canvas id="gsc-performance-chart"></canvas>
				</div>
			</section>

			<!-- Data Tables Section -->
			<div class="gsc-tables-grid">
				<!-- Top Queries Table -->
				<section class="gsc-queries-section">
					<div class="gsc-section-header">
						<h2>
							<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
								<circle cx="11" cy="11" r="8"/>
								<line x1="21" y1="21" x2="16.65" y2="16.65"/>
							</svg>
							<?php esc_html_e( 'Top Keywords', 'seovela' ); ?>
						</h2>
					</div>
					
					<?php if ( empty( $top_queries ) ) : ?>
						<div class="gsc-empty-state">
							<p><?php esc_html_e( 'No keyword data yet. Click "Sync Now" to fetch data.', 'seovela' ); ?></p>
						</div>
					<?php else : ?>
						<div class="gsc-table-wrapper">
							<table class="gsc-table gsc-queries-table">
								<thead>
									<tr>
										<th class="col-query"><?php esc_html_e( 'Query', 'seovela' ); ?></th>
										<th class="col-clicks"><?php esc_html_e( 'Clicks', 'seovela' ); ?></th>
										<th class="col-impressions"><?php esc_html_e( 'Impr.', 'seovela' ); ?></th>
										<th class="col-ctr"><?php esc_html_e( 'CTR', 'seovela' ); ?></th>
										<th class="col-position"><?php esc_html_e( 'Pos.', 'seovela' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $top_queries as $query ) : 
										$ctr_class = $query->ctr >= 0.05 ? 'good' : ( $query->ctr >= 0.02 ? 'medium' : 'low' );
										$pos_class = $query->position <= 10 ? 'good' : ( $query->position <= 30 ? 'medium' : 'low' );
									?>
										<tr>
											<td class="col-query">
												<span class="query-text"><?php echo esc_html( $query->query ); ?></span>
											</td>
											<td class="col-clicks">
												<span class="clicks-value"><?php echo esc_html( number_format( $query->clicks ) ); ?></span>
											</td>
											<td class="col-impressions">
												<?php echo esc_html( number_format( $query->impressions ) ); ?>
											</td>
											<td class="col-ctr">
												<span class="gsc-badge badge-<?php echo esc_attr( $ctr_class ); ?>">
													<?php echo esc_html( number_format( $query->ctr * 100, 2 ) ); ?>%
												</span>
											</td>
											<td class="col-position">
												<span class="gsc-badge badge-<?php echo esc_attr( $pos_class ); ?>">
													<?php echo esc_html( number_format( $query->position, 1 ) ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</section>

				<!-- Top Pages Table -->
				<section class="gsc-pages-section">
					<div class="gsc-section-header">
						<h2>
							<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
								<polyline points="14,2 14,8 20,8"/>
							</svg>
							<?php esc_html_e( 'Top Pages', 'seovela' ); ?>
						</h2>
						<?php 
						$last_sync = get_user_meta( $user_id, 'seovela_gsc_last_sync', true );
						if ( $last_sync ) : 
						?>
							<span class="last-sync"><?php printf( esc_html__( 'Last synced: %s ago', 'seovela' ), esc_html( human_time_diff( strtotime( $last_sync ) ) ) ); ?></span>
						<?php endif; ?>
					</div>
					
					<?php
					$top_pages = $gsc->get_top_pages( 10, 'clicks', $user_id );
					?>

					<?php if ( empty( $top_pages ) ) : ?>
						<div class="gsc-empty-state">
							<div class="empty-icon">
								<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
									<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
									<polyline points="14,2 14,8 20,8"/>
								</svg>
							</div>
							<h3><?php esc_html_e( 'No Data Yet', 'seovela' ); ?></h3>
							<p><?php esc_html_e( 'Click "Sync Now" to fetch your Search Console data.', 'seovela' ); ?></p>
							<button type="button" class="gsc-btn gsc-btn-primary" id="sync-gsc-empty">
								<?php esc_html_e( 'Sync Data Now', 'seovela' ); ?>
							</button>
						</div>
					<?php else : ?>
						<div class="gsc-table-wrapper">
							<table class="gsc-table">
								<thead>
									<tr>
										<th class="col-page"><?php esc_html_e( 'Page', 'seovela' ); ?></th>
										<th class="col-clicks"><?php esc_html_e( 'Clicks', 'seovela' ); ?></th>
										<th class="col-impressions"><?php esc_html_e( 'Impr.', 'seovela' ); ?></th>
										<th class="col-ctr"><?php esc_html_e( 'CTR', 'seovela' ); ?></th>
										<th class="col-position"><?php esc_html_e( 'Pos.', 'seovela' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $top_pages as $page ) : 
										$post_id = $page->post_id;
										$post = $post_id ? get_post( $post_id ) : null;
										$ctr_class = $page->ctr >= 0.05 ? 'good' : ( $page->ctr >= 0.02 ? 'medium' : 'low' );
										$pos_class = $page->position <= 10 ? 'good' : ( $page->position <= 30 ? 'medium' : 'low' );
									?>
										<tr>
											<td class="col-page">
												<?php if ( $post ) : ?>
													<div class="page-info">
														<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="page-title">
															<?php echo esc_html( $post->post_title ); ?>
														</a>
														<div class="page-actions">
															<a href="<?php echo esc_url( $page->page_url ); ?>" target="_blank" class="action-link">
																<?php esc_html_e( 'View', 'seovela' ); ?>
																<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
																	<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
																	<polyline points="15 3 21 3 21 9"/>
																	<line x1="10" y1="14" x2="21" y2="3"/>
																</svg>
															</a>
														</div>
													</div>
												<?php else : ?>
													<div class="page-info">
														<a href="<?php echo esc_url( $page->page_url ); ?>" target="_blank" class="page-title external">
															<?php 
															$display_url = str_replace( array( 'https://', 'http://' ), '', $page->page_url );
															$display_url = strlen( $display_url ) > 50 ? substr( $display_url, 0, 47 ) . '...' : $display_url;
															echo esc_html( $display_url ); 
															?>
														</a>
													</div>
												<?php endif; ?>
											</td>
											<td class="col-clicks">
												<span class="clicks-value"><?php echo esc_html( number_format( $page->clicks ) ); ?></span>
											</td>
											<td class="col-impressions">
												<?php echo esc_html( number_format( $page->impressions ) ); ?>
											</td>
											<td class="col-ctr">
												<span class="gsc-badge badge-<?php echo esc_attr( $ctr_class ); ?>">
													<?php echo esc_html( number_format( $page->ctr * 100, 2 ) ); ?>%
												</span>
											</td>
											<td class="col-position">
												<span class="gsc-badge badge-<?php echo esc_attr( $pos_class ); ?>">
													<?php echo esc_html( number_format( $page->position, 1 ) ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</section>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
/* GSC Page Styles - Centralized OAuth Version */
:root {
	--gsc-primary: #4285F4;
	--gsc-primary-dark: #3367D6;
	--gsc-green: #34A853;
	--gsc-yellow: #FBBC05;
	--gsc-red: #EA4335;
	--gsc-bg: #f8fafc;
	--gsc-card: #ffffff;
	--gsc-border: #e2e8f0;
	--gsc-text: #1e293b;
	--gsc-text-muted: #64748b;
	--gsc-gradient-start: #667eea;
	--gsc-gradient-end: #764ba2;
}

.seovela-gsc-wrap {
	margin: -8px -20px 0 -2px;
	min-height: 100vh;
	position: relative;
	overflow: hidden;
}

/* Animated Background */
.seovela-gsc-bg {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 0;
	pointer-events: none;
}

.seovela-gsc-bg-gradient {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 400px;
	background: linear-gradient(135deg, var(--gsc-gradient-start) 0%, var(--gsc-gradient-end) 100%);
	opacity: 0.05;
}

.seovela-gsc-bg-orbs .orb {
	position: absolute;
	border-radius: 50%;
	filter: blur(60px);
	opacity: 0.3;
	animation: float 20s ease-in-out infinite;
}

.orb-1 {
	width: 300px;
	height: 300px;
	background: var(--gsc-primary);
	top: 10%;
	right: 10%;
}

.orb-2 {
	width: 200px;
	height: 200px;
	background: var(--gsc-green);
	top: 50%;
	left: 5%;
	animation-delay: -7s;
}

.orb-3 {
	width: 250px;
	height: 250px;
	background: var(--gsc-yellow);
	bottom: 10%;
	right: 20%;
	animation-delay: -14s;
}

@keyframes float {
	0%, 100% { transform: translate(0, 0); }
	25% { transform: translate(30px, -30px); }
	50% { transform: translate(-20px, 20px); }
	75% { transform: translate(20px, 30px); }
}

/* Connect Container */
.gsc-connect-container {
	position: relative;
	z-index: 1;
	max-width: 900px;
	margin: 0 auto;
	padding: 40px;
}

/* Hero Section */
.gsc-hero {
	text-align: center;
	margin-bottom: 40px;
}

.gsc-google-logo {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 120px;
	height: 120px;
	background: white;
	border-radius: 30px;
	box-shadow: 0 20px 60px rgba(66, 133, 244, 0.2);
	margin-bottom: 24px;
}

.gsc-success-logo {
	background: #dcfce7;
	box-shadow: 0 20px 60px rgba(34, 197, 94, 0.2);
}

.gsc-hero-title {
	font-size: 32px;
	font-weight: 800;
	color: var(--gsc-text);
	margin: 0 0 16px 0;
	line-height: 1.2;
}

.gsc-hero-subtitle {
	font-size: 18px;
	color: var(--gsc-text-muted);
	max-width: 600px;
	margin: 0 auto;
	line-height: 1.6;
}

/* Features Grid */
.gsc-features-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-top: 40px;
}

.gsc-feature-card {
	background: white;
	border-radius: 16px;
	padding: 24px;
	text-align: center;
	border: 1px solid var(--gsc-border);
	transition: all 0.3s ease;
}

.gsc-feature-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}

.gsc-feature-card .feature-icon {
	width: 56px;
	height: 56px;
	background: linear-gradient(135deg, var(--gsc-primary) 0%, var(--gsc-primary-dark) 100%);
	border-radius: 14px;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 16px;
	color: white;
}

.gsc-feature-card h3 {
	font-size: 16px;
	font-weight: 700;
	color: var(--gsc-text);
	margin: 0 0 8px 0;
}

.gsc-feature-card p {
	font-size: 14px;
	color: var(--gsc-text-muted);
	margin: 0;
	line-height: 1.5;
}

/* Connect Section */
.gsc-connect-section {
	text-align: center;
	margin: 40px 0;
}

.gsc-connect-btn {
	display: inline-flex;
	align-items: center;
	gap: 12px;
	background: linear-gradient(135deg, var(--gsc-primary) 0%, var(--gsc-primary-dark) 100%);
	color: white;
	border: none;
	padding: 18px 40px;
	font-size: 18px;
	font-weight: 700;
	border-radius: 14px;
	cursor: pointer;
	box-shadow: 0 8px 30px rgba(66, 133, 244, 0.4);
	transition: all 0.3s ease;
	text-decoration: none;
}

.gsc-connect-btn:hover {
	transform: translateY(-3px);
	box-shadow: 0 12px 40px rgba(66, 133, 244, 0.5);
	color: white;
}

.gsc-connect-note {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	margin-top: 20px;
	font-size: 14px;
	color: var(--gsc-text-muted);
	background: #f1f5f9;
	padding: 10px 20px;
	border-radius: 10px;
}

/* Buttons */
.gsc-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	padding: 14px 28px;
	font-size: 15px;
	font-weight: 600;
	border-radius: 12px;
	cursor: pointer;
	transition: all 0.2s ease;
	border: none;
	text-decoration: none;
}

.gsc-btn-primary {
	background: linear-gradient(135deg, var(--gsc-primary) 0%, var(--gsc-primary-dark) 100%);
	color: white;
	box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
}

.gsc-btn-primary:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
	color: white;
}

.gsc-btn-secondary {
	background: #f1f5f9;
	color: var(--gsc-text);
}

.gsc-btn-secondary:hover {
	background: #e2e8f0;
}

.gsc-btn-ghost {
	background: transparent;
	color: var(--gsc-text-muted);
	border: 2px solid var(--gsc-border);
}

.gsc-btn-ghost:hover {
	border-color: var(--gsc-red);
	color: var(--gsc-red);
}

.gsc-btn-select {
	padding: 10px 20px;
	font-size: 14px;
}

.gsc-link-btn {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	background: none;
	border: none;
	color: var(--gsc-text-muted);
	font-size: 14px;
	cursor: pointer;
	padding: 0;
	transition: color 0.2s ease;
}

.gsc-link-btn:hover {
	color: var(--gsc-primary);
}

/* Property Selector */
.gsc-property-selector {
	background: white;
	border-radius: 20px;
	padding: 30px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
	border: 1px solid var(--gsc-border);
}

.gsc-property-selector h2 {
	font-size: 18px;
	font-weight: 700;
	color: var(--gsc-text);
	margin: 0 0 24px 0;
}

.gsc-properties-grid {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.gsc-property-card {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 20px;
	background: #f8fafc;
	border-radius: 14px;
	border: 2px solid var(--gsc-border);
	transition: all 0.2s ease;
}

.gsc-property-card:hover {
	border-color: var(--gsc-primary);
	background: white;
}

.gsc-property-card.unverified {
	opacity: 0.6;
}

.property-icon {
	width: 48px;
	height: 48px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: white;
	border-radius: 12px;
	color: var(--gsc-primary);
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.property-info {
	flex: 1;
}

.property-url {
	display: block;
	font-weight: 600;
	color: var(--gsc-text);
	font-size: 15px;
	margin-bottom: 4px;
}

.property-type {
	font-size: 13px;
	color: var(--gsc-text-muted);
}

.property-unverified {
	font-size: 12px;
	color: var(--gsc-yellow);
	font-weight: 600;
}

/* Error/Success Notices */
.gsc-error-notice,
.gsc-success-banner {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px 20px;
	border-radius: 12px;
	margin-bottom: 24px;
	font-size: 14px;
	font-weight: 500;
}

.gsc-error-notice {
	background: #fef2f2;
	color: #991b1b;
	border: 1px solid #fecaca;
}

.gsc-success-banner {
	background: #dcfce7;
	color: #166534;
	border: 1px solid #bbf7d0;
}

/* No Properties */
.gsc-no-properties {
	text-align: center;
	padding: 40px;
}

.gsc-no-properties svg {
	color: var(--gsc-text-muted);
	margin-bottom: 16px;
}

.gsc-no-properties h3 {
	font-size: 18px;
	font-weight: 700;
	color: var(--gsc-text);
	margin: 0 0 8px 0;
}

.gsc-no-properties p {
	color: var(--gsc-text-muted);
	margin: 0 0 20px 0;
}

.gsc-disconnect-link {
	text-align: center;
	margin-top: 30px;
}

/* Dashboard Styles */
.gsc-dashboard {
	position: relative;
	z-index: 1;
	padding: 30px 40px;
	max-width: 1400px;
	margin: 0 auto;
}

.gsc-dashboard-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: white;
	border-radius: 20px;
	padding: 24px 30px;
	margin-bottom: 24px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
	border: 1px solid var(--gsc-border);
}

.gsc-header-left {
	display: flex;
	align-items: center;
	gap: 20px;
}

.gsc-google-badge {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 56px;
	height: 56px;
	background: white;
	border-radius: 14px;
	box-shadow: 0 4px 15px rgba(66, 133, 244, 0.2);
}

.gsc-header-text h1 {
	font-size: 24px;
	font-weight: 700;
	color: var(--gsc-text);
	margin: 0 0 6px 0;
}

.gsc-connection-info {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

.gsc-status-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	padding: 6px 12px;
	border-radius: 20px;
}

.gsc-status-badge.connected {
	background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
	color: #166534;
}

.status-dot {
	width: 8px;
	height: 8px;
	background: var(--gsc-green);
	border-radius: 50%;
	animation: pulse 2s infinite;
}

@keyframes pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}

.gsc-property {
	font-size: 14px;
	color: var(--gsc-text-muted);
	font-weight: 600;
}

.gsc-email {
	font-size: 13px;
	color: var(--gsc-text-muted);
}

.gsc-header-actions {
	display: flex;
	align-items: center;
	gap: 12px;
}

.gsc-select {
	padding: 10px 16px;
	border: 2px solid var(--gsc-border);
	border-radius: 10px;
	font-size: 14px;
	font-weight: 600;
	color: var(--gsc-text);
	background: white;
	cursor: pointer;
	transition: all 0.2s ease;
}

.gsc-select:hover,
.gsc-select:focus {
	border-color: var(--gsc-primary);
	outline: none;
}

/* Stats Section */
.gsc-stats-section {
	margin-bottom: 24px;
}

.gsc-stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
	gap: 20px;
}

.gsc-stat-card {
	background: white;
	border-radius: 16px;
	padding: 24px;
	display: flex;
	gap: 16px;
	align-items: flex-start;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
	border: 1px solid var(--gsc-border);
	transition: all 0.3s ease;
	position: relative;
	overflow: hidden;
}

.gsc-stat-card::before {
	content: "";
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
}

.gsc-stat-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}

.gsc-stat-card.stat-clicks::before { background: linear-gradient(90deg, var(--gsc-primary), #60a5fa); }
.gsc-stat-card.stat-impressions::before { background: linear-gradient(90deg, var(--gsc-green), #4ade80); }
.gsc-stat-card.stat-ctr::before { background: linear-gradient(90deg, var(--gsc-yellow), #fcd34d); }
.gsc-stat-card.stat-position::before { background: linear-gradient(90deg, var(--gsc-red), #f87171); }

.gsc-stat-icon {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 52px;
	height: 52px;
	border-radius: 12px;
	flex-shrink: 0;
}

.stat-clicks .gsc-stat-icon { background: #dbeafe; color: var(--gsc-primary); }
.stat-impressions .gsc-stat-icon { background: #dcfce7; color: var(--gsc-green); }
.stat-ctr .gsc-stat-icon { background: #fef3c7; color: #d97706; }
.stat-position .gsc-stat-icon { background: #fee2e2; color: var(--gsc-red); }

.gsc-stat-content { flex: 1; }

.gsc-stat-value {
	font-size: 32px;
	font-weight: 800;
	color: var(--gsc-text);
	line-height: 1;
	margin-bottom: 4px;
}

.gsc-stat-label {
	font-size: 13px;
	font-weight: 600;
	color: var(--gsc-text-muted);
	text-transform: uppercase;
	letter-spacing: 0.5px;
	margin-bottom: 8px;
}

.gsc-stat-change {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	font-weight: 600;
	padding: 4px 8px;
	border-radius: 6px;
}

.gsc-stat-change.positive { background: #dcfce7; color: #166534; }
.gsc-stat-change.negative { background: #fee2e2; color: #991b1b; }

.change-period { color: var(--gsc-text-muted); font-weight: 500; }

.gsc-stat-hint {
	font-size: 12px;
	color: var(--gsc-text-muted);
	font-style: italic;
}

/* Chart Section */
.gsc-chart-section {
	background: white;
	border-radius: 20px;
	padding: 24px;
	margin-bottom: 24px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
	border: 1px solid var(--gsc-border);
}

.gsc-chart-container {
	height: 300px;
	position: relative;
}

/* Tables Grid */
.gsc-tables-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 24px;
}

/* Pages/Queries Section */
.gsc-pages-section,
.gsc-queries-section {
	background: white;
	border-radius: 20px;
	padding: 24px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
	border: 1px solid var(--gsc-border);
}

.gsc-section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.gsc-section-header h2 {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 18px;
	font-weight: 700;
	color: var(--gsc-text);
	margin: 0;
}

.gsc-section-header h2 svg { color: var(--gsc-primary); }

.last-sync {
	font-size: 13px;
	color: var(--gsc-text-muted);
}

/* Table */
.gsc-table-wrapper {
	overflow-x: auto;
	border-radius: 12px;
	border: 1px solid var(--gsc-border);
}

.gsc-table {
	width: 100%;
	border-collapse: collapse;
}

.gsc-table thead {
	background: linear-gradient(135deg, var(--gsc-gradient-start) 0%, var(--gsc-gradient-end) 100%);
}

.gsc-table th {
	padding: 14px 16px;
	text-align: left;
	font-size: 12px;
	font-weight: 700;
	color: white;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.gsc-table td {
	padding: 14px 16px;
	border-bottom: 1px solid var(--gsc-border);
}

.gsc-table tbody tr:hover { background: #f8fafc; }
.gsc-table tbody tr:last-child td { border-bottom: none; }

.page-info { display: flex; flex-direction: column; gap: 4px; }

.page-title {
	font-weight: 600;
	color: var(--gsc-text);
	text-decoration: none;
}

.page-title:hover { color: var(--gsc-primary); }

.page-actions { display: flex; align-items: center; gap: 8px; }

.action-link {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
	color: var(--gsc-text-muted);
	text-decoration: none;
}

.action-link:hover { color: var(--gsc-primary); }

.query-text {
	font-weight: 500;
	color: var(--gsc-text);
	word-break: break-word;
}

.clicks-value { font-weight: 700; color: var(--gsc-primary); font-size: 15px; }

.gsc-badge {
	display: inline-block;
	padding: 5px 10px;
	border-radius: 6px;
	font-size: 13px;
	font-weight: 700;
}

.badge-good { background: #dcfce7; color: #166534; }
.badge-medium { background: #fef3c7; color: #92400e; }
.badge-low { background: #fee2e2; color: #991b1b; }

/* Empty State */
.gsc-empty-state {
	text-align: center;
	padding: 50px 40px;
}

.gsc-empty-state .empty-icon { color: var(--gsc-border); margin-bottom: 16px; }
.gsc-empty-state h3 { font-size: 18px; font-weight: 700; color: var(--gsc-text); margin: 0 0 8px 0; }
.gsc-empty-state p { color: var(--gsc-text-muted); margin: 0 0 20px 0; }

/* Responsive */
@media (max-width: 1024px) {
	.gsc-dashboard-header {
		flex-direction: column;
		align-items: flex-start;
		gap: 20px;
	}
	.gsc-header-actions { width: 100%; flex-wrap: wrap; }
	.gsc-tables-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
	.gsc-connect-container, .gsc-dashboard { padding: 20px; }
	.gsc-hero-title { font-size: 24px; }
	.gsc-stats-grid { grid-template-columns: 1fr; }
	.gsc-stat-value { font-size: 28px; }
	.gsc-features-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
	.gsc-features-grid { grid-template-columns: 1fr; }
}
</style>
