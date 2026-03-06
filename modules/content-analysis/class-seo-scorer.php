<?php
/**
 * Seovela SEO Scorer
 *
 * Combines keyword and readability analysis into overall SEO score
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela SEO Scorer Class
 */
class Seovela_SEO_Scorer {

	/**
	 * Keyword analyzer instance
	 *
	 * @var Seovela_Keyword_Analyzer
	 */
	private $keyword_analyzer;

	/**
	 * Readability analyzer instance
	 *
	 * @var Seovela_Readability_Analyzer
	 */
	private $readability_analyzer;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once SEOVELA_PLUGIN_DIR . 'modules/content-analysis/class-keyword-analyzer.php';
		require_once SEOVELA_PLUGIN_DIR . 'modules/content-analysis/class-readability-analyzer.php';

		$this->keyword_analyzer = new Seovela_Keyword_Analyzer();
		$this->readability_analyzer = new Seovela_Readability_Analyzer();
	}

	/**
	 * Analyze post and calculate SEO score
	 *
	 * @param int $post_id Post ID
	 * @return array Analysis results with score
	 */
	public function analyze_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'score'       => 0,
				'status'      => 'error',
				'errors'      => array( __( 'Post not found', 'seovela' ) ),
				'warnings'    => array(),
				'good'        => array(),
				'keyword'     => array(),
				'readability' => array(),
			);
		}

		// Get focus keyword
		$focus_keyword = get_post_meta( $post_id, '_seovela_focus_keyword', true );

		// Get meta data
		$meta_title = get_post_meta( $post_id, '_seovela_meta_title', true );
		$meta_description = get_post_meta( $post_id, '_seovela_meta_description', true );

		// Use defaults if meta is empty
		if ( empty( $meta_title ) ) {
			$meta_title = get_the_title( $post_id );
		}
		if ( empty( $meta_description ) ) {
			$meta_description = wp_trim_words( $post->post_content, 20, '...' );
		}

		// Prepare data for analysis
		$data = array(
			'title'       => $meta_title,
			'description' => $meta_description,
			'content'     => $post->post_content,
			'url'         => get_permalink( $post_id ),
		);

		return $this->analyze_data( $data, $focus_keyword, $post_id );
	}

	/**
	 * Analyze prepared data and calculate SEO score
	 *
	 * @param array  $data Content data (title, description, content, url)
	 * @param string $focus_keyword Focus keyword
	 * @param int    $post_id Optional. Post ID for context-specific checks
	 * @return array Analysis results with score
	 */
	public function analyze_data( $data, $focus_keyword = '', $post_id = 0 ) {
		// Analyze keyword (if set)
		$keyword_analysis = array(
			'score'   => 0,
			'results' => array(),
		);

		if ( ! empty( $focus_keyword ) ) {
			$keyword_analysis = $this->keyword_analyzer->analyze( $focus_keyword, $data );
		}

		// Analyze readability
		$readability_analysis = $this->readability_analyzer->analyze( $data['content'] );

		// Calculate overall score (keyword 60%, readability 40%)
		$keyword_weight = 0.6;
		$readability_weight = 0.4;
		
		$keyword_score = isset( $keyword_analysis['score'] ) ? $keyword_analysis['score'] : 0;
		$readability_score = isset( $readability_analysis['score'] ) ? $readability_analysis['score'] : 0;

		$overall_score = round( 
			( $keyword_score * $keyword_weight ) + 
			( $readability_score * $readability_weight )
		);

		// Categorize issues
		$errors = array();
		$warnings = array();
		$good = array();

		// Process keyword results
		if ( ! empty( $focus_keyword ) && isset( $keyword_analysis['results'] ) ) {
			$this->categorize_keyword_issues( $keyword_analysis['results'], $errors, $warnings, $good );
		} elseif ( empty( $focus_keyword ) ) {
			$warnings[] = __( 'No focus keyword set', 'seovela' );
		}

		// Process readability results
		if ( isset( $readability_analysis['results'] ) ) {
			$this->categorize_readability_issues( $readability_analysis['results'], $errors, $warnings, $good );
		}

		// Basic content checks
		$this->add_basic_checks( $post_id, $data, $errors, $warnings, $good );

		// Determine overall status
		$status = $this->get_status_from_score( $overall_score );

		return array(
			'score'       => $overall_score,
			'status'      => $status,
			'errors'      => $errors,
			'warnings'    => $warnings,
			'good'        => $good,
			'keyword'     => $keyword_analysis,
			'readability' => $readability_analysis,
		);
	}

	/**
	 * Categorize keyword analysis issues
	 *
	 * @param array $results Keyword results
	 * @param array &$errors Errors array (by reference)
	 * @param array &$warnings Warnings array (by reference)
	 * @param array &$good Good array (by reference)
	 */
	private function categorize_keyword_issues( $results, &$errors, &$warnings, &$good ) {
		// Title
		if ( isset( $results['in_title'] ) ) {
			if ( $results['in_title']['status'] === 'great' ) {
				$good[] = $results['in_title']['message'];
			} elseif ( $results['in_title']['status'] === 'good' ) {
				$good[] = $results['in_title']['message'];
			} else {
				$errors[] = $results['in_title']['message'];
			}
		}

		// Description
		if ( isset( $results['in_description'] ) ) {
			if ( $results['in_description']['status'] === 'good' ) {
				$good[] = $results['in_description']['message'];
			} else {
				$warnings[] = $results['in_description']['message'];
			}
		}

		// URL
		if ( isset( $results['in_url'] ) ) {
			if ( $results['in_url']['status'] === 'good' ) {
				$good[] = $results['in_url']['message'];
			} else {
				$warnings[] = $results['in_url']['message'];
			}
		}

		// Content
		if ( isset( $results['in_content'] ) ) {
			if ( $results['in_content']['status'] === 'good' ) {
				$good[] = $results['in_content']['message'];
			} elseif ( $results['in_content']['status'] === 'warning' ) {
				$warnings[] = $results['in_content']['message'];
			} else {
				$errors[] = $results['in_content']['message'];
			}
		}

		// First paragraph
		if ( isset( $results['in_first_paragraph'] ) ) {
			if ( $results['in_first_paragraph']['status'] === 'good' ) {
				$good[] = $results['in_first_paragraph']['message'];
			} else {
				$warnings[] = $results['in_first_paragraph']['message'];
			}
		}

		// Headings
		if ( isset( $results['in_headings'] ) ) {
			if ( $results['in_headings']['status'] === 'good' ) {
				$good[] = $results['in_headings']['message'];
			} else {
				$warnings[] = $results['in_headings']['message'];
			}
		}

		// Density
		if ( isset( $results['density'] ) ) {
			if ( $results['density']['status'] === 'good' ) {
				$good[] = $results['density']['message'];
			} else {
				$warnings[] = $results['density']['message'];
			}
		}

		// Alt text
		if ( isset( $results['in_alt_text'] ) && $results['in_alt_text']['status'] !== 'neutral' ) {
			if ( $results['in_alt_text']['status'] === 'good' ) {
				$good[] = $results['in_alt_text']['message'];
			} else {
				$warnings[] = $results['in_alt_text']['message'];
			}
		}
	}

	/**
	 * Categorize readability analysis issues
	 *
	 * @param array $results Readability results
	 * @param array &$errors Errors array (by reference)
	 * @param array &$warnings Warnings array (by reference)
	 * @param array &$good Good array (by reference)
	 */
	private function categorize_readability_issues( $results, &$errors, &$warnings, &$good ) {
		// Flesch score
		if ( isset( $results['flesch'] ) ) {
			if ( $results['flesch']['status'] === 'good' ) {
				$good[] = $results['flesch']['message'];
			} elseif ( $results['flesch']['status'] === 'warning' ) {
				$warnings[] = $results['flesch']['message'];
			} else if ( $results['flesch']['status'] === 'error' ) {
				$errors[] = $results['flesch']['message'];
			}
		}

		// Sentences
		if ( isset( $results['sentences'] ) ) {
			if ( $results['sentences']['status'] === 'good' ) {
				$good[] = $results['sentences']['message'];
			} elseif ( $results['sentences']['status'] === 'warning' ) {
				$warnings[] = $results['sentences']['message'];
			} else if ( $results['sentences']['status'] === 'error' ) {
				$errors[] = $results['sentences']['message'];
			}
		}

		// Paragraphs
		if ( isset( $results['paragraphs'] ) ) {
			if ( $results['paragraphs']['status'] === 'good' ) {
				$good[] = $results['paragraphs']['message'];
			} elseif ( $results['paragraphs']['status'] === 'warning' ) {
				$warnings[] = $results['paragraphs']['message'];
			} else if ( $results['paragraphs']['status'] === 'error' ) {
				$errors[] = $results['paragraphs']['message'];
			}
		}

		// Subheadings
		if ( isset( $results['subheadings'] ) && $results['subheadings']['status'] !== 'neutral' ) {
			if ( $results['subheadings']['status'] === 'good' ) {
				$good[] = $results['subheadings']['message'];
			} elseif ( $results['subheadings']['status'] === 'warning' ) {
				$warnings[] = $results['subheadings']['message'];
			} else if ( $results['subheadings']['status'] === 'error' ) {
				$errors[] = $results['subheadings']['message'];
			}
		}

		// Passive voice
		if ( isset( $results['passive_voice'] ) ) {
			if ( $results['passive_voice']['status'] === 'good' ) {
				$good[] = $results['passive_voice']['message'];
			} elseif ( $results['passive_voice']['status'] === 'warning' ) {
				$warnings[] = $results['passive_voice']['message'];
			} else if ( $results['passive_voice']['status'] === 'error' ) {
				$warnings[] = $results['passive_voice']['message']; // Demote to warning
			}
		}

		// Transition words
		if ( isset( $results['transition_words'] ) ) {
			if ( $results['transition_words']['status'] === 'good' ) {
				$good[] = $results['transition_words']['message'];
			} elseif ( $results['transition_words']['status'] === 'warning' ) {
				$warnings[] = $results['transition_words']['message'];
			} else if ( $results['transition_words']['status'] === 'error' ) {
				$warnings[] = $results['transition_words']['message']; // Demote to warning
			}
		}
	}

	/**
	 * Add basic content checks
	 *
	 * @param int   $post_id Post ID
	 * @param array $data Content data
	 * @param array &$errors Errors array (by reference)
	 * @param array &$warnings Warnings array (by reference)
	 * @param array &$good Good array (by reference)
	 */
	private function add_basic_checks( $post_id, $data, &$errors, &$warnings, &$good ) {
		// Check title length
		$title_length = strlen( $data['title'] );
		if ( $title_length < 30 ) {
			$warnings[] = sprintf( __( 'Meta title is too short (%d characters). Aim for 50-60', 'seovela' ), $title_length );
		} elseif ( $title_length > 60 ) {
			$warnings[] = sprintf( __( 'Meta title is too long (%d characters). Keep it under 60', 'seovela' ), $title_length );
		} else {
			$good[] = sprintf( __( 'Meta title length is good (%d characters)', 'seovela' ), $title_length );
		}

		// Check description length
		$desc_length = strlen( $data['description'] );
		if ( $desc_length < 120 ) {
			$warnings[] = sprintf( __( 'Meta description is too short (%d characters). Aim for 150-160', 'seovela' ), $desc_length );
		} elseif ( $desc_length > 160 ) {
			$warnings[] = sprintf( __( 'Meta description is too long (%d characters). Keep it under 160', 'seovela' ), $desc_length );
		} else {
			$good[] = sprintf( __( 'Meta description length is good (%d characters)', 'seovela' ), $desc_length );
		}

		// Check content length
		$content_length = str_word_count( strip_tags( $data['content'] ) );
		if ( $content_length < 300 ) {
			$warnings[] = sprintf( __( 'Content is short (%d words). Aim for at least 300 words', 'seovela' ), $content_length );
		} else {
			$good[] = sprintf( __( 'Content length is good (%d words)', 'seovela' ), $content_length );
		}

		// Check featured image
		if ( ! has_post_thumbnail( $post_id ) ) {
			$warnings[] = __( 'No featured image set', 'seovela' );
		} else {
			$good[] = __( 'Featured image is set', 'seovela' );
		}

		// Check internal links
		$internal_link_count = substr_count( $data['content'], get_site_url() );
		if ( $internal_link_count === 0 ) {
			$warnings[] = __( 'No internal links found. Add links to related content', 'seovela' );
		} else {
			$good[] = sprintf( __( '%d internal link(s) found', 'seovela' ), $internal_link_count );
		}

		// Check external links
		$external_link_count = substr_count( $data['content'], 'href="http' ) - $internal_link_count;
		if ( $external_link_count === 0 ) {
			$warnings[] = __( 'No external links found. Consider adding relevant external resources', 'seovela' );
		}

		// Image SEO checks
		$this->add_image_seo_checks( $post_id, $data, $errors, $warnings, $good );
	}

	/**
	 * Add image SEO checks
	 *
	 * @param int   $post_id Post ID
	 * @param array $data Content data
	 * @param array &$errors Errors array (by reference)
	 * @param array &$warnings Warnings array (by reference)
	 * @param array &$good Good array (by reference)
	 */
	private function add_image_seo_checks( $post_id, $data, &$errors, &$warnings, &$good ) {
		// Check if Image SEO module is enabled
		if ( ! get_option( 'seovela_image_seo_enabled', true ) ) {
			return;
		}

		// Parse images from content
		preg_match_all( '/<img[^>]+>/i', $data['content'], $images );
		
		if ( empty( $images[0] ) ) {
			// No images in content
			return;
		}

		$total_images = count( $images[0] );
		$images_without_alt = 0;
		$images_with_alt = 0;

		foreach ( $images[0] as $img_tag ) {
			// Check for alt attribute
			if ( ! preg_match( '/alt=["\'][^"\']*["\']/i', $img_tag ) || 
				 preg_match( '/alt=["\']["\']/i', $img_tag ) ) {
				$images_without_alt++;
			} else {
				$images_with_alt++;
			}
		}

		// Report results
		if ( $images_without_alt > 0 ) {
			if ( $images_without_alt === $total_images ) {
				$errors[] = sprintf( 
					__( 'All %d image(s) are missing alt text. This is bad for SEO and accessibility', 'seovela' ), 
					$total_images 
				);
			} else {
				$warnings[] = sprintf( 
					__( '%d out of %d image(s) are missing alt text', 'seovela' ), 
					$images_without_alt,
					$total_images 
				);
			}
		} elseif ( $total_images > 0 ) {
			$good[] = sprintf( 
				__( 'All %d image(s) have alt text', 'seovela' ), 
				$total_images 
			);
		}

		// Check if featured image has alt text
		if ( has_post_thumbnail( $post_id ) ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			$thumbnail_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
			
			if ( empty( $thumbnail_alt ) ) {
				$warnings[] = __( 'Featured image is missing alt text', 'seovela' );
			} else {
				$good[] = __( 'Featured image has alt text', 'seovela' );
			}
		}
	}

	/**
	 * Get status from score
	 *
	 * @param int $score SEO score
	 * @return string Status (good, warning, error)
	 */
	private function get_status_from_score( $score ) {
		if ( $score >= 70 ) {
			return 'good';
		} elseif ( $score >= 40 ) {
			return 'warning';
		} else {
			return 'error';
		}
	}

	/**
	 * Get status color
	 *
	 * @param string $status Status
	 * @return string Color code
	 */
	public static function get_status_color( $status ) {
		$colors = array(
			'good'    => '#46b450', // Green
			'warning' => '#ffb900', // Orange
			'error'   => '#dc3232', // Red
		);

		return isset( $colors[ $status ] ) ? $colors[ $status ] : '#999';
	}

	/**
	 * Get status label
	 *
	 * @param string $status Status
	 * @return string Label
	 */
	public static function get_status_label( $status ) {
		$labels = array(
			'good'    => __( 'Good', 'seovela' ),
			'warning' => __( 'Needs Improvement', 'seovela' ),
			'error'   => __( 'Poor', 'seovela' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Unknown', 'seovela' );
	}
}

