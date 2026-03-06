<?php
/**
 * Seovela Keyword Analyzer
 *
 * Analyzes content for focus keyword optimization
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Keyword Analyzer Class
 */
class Seovela_Keyword_Analyzer {

	/**
	 * Analyze content for focus keyword
	 *
	 * @param string $keyword Focus keyword
	 * @param array  $data Content data (title, content, description, etc.)
	 * @return array Analysis results
	 */
	public function analyze( $keyword, $data ) {
		if ( empty( $keyword ) ) {
			return array(
				'score'   => 0,
				'results' => array(),
			);
		}

		$keyword = strtolower( trim( $keyword ) );
		$results = array();

		// 1. Keyword in title
		$results['in_title'] = $this->check_in_title( $keyword, $data['title'] );

		// 2. Keyword in meta description
		$results['in_description'] = $this->check_in_description( $keyword, $data['description'] );

		// 3. Keyword in URL
		$results['in_url'] = $this->check_in_url( $keyword, $data['url'] );

		// 4. Keyword in content
		$results['in_content'] = $this->check_in_content( $keyword, $data['content'] );

		// 5. Keyword in first paragraph
		$results['in_first_paragraph'] = $this->check_in_first_paragraph( $keyword, $data['content'] );

		// 6. Keyword in headings
		$results['in_headings'] = $this->check_in_headings( $keyword, $data['content'] );

		// 7. Keyword density
		$results['density'] = $this->calculate_density( $keyword, $data['content'] );

		// 8. Keyword in alt text (if images present)
		$results['in_alt_text'] = $this->check_in_alt_text( $keyword, $data['content'] );

		// Calculate overall keyword score
		$score = $this->calculate_keyword_score( $results );

		return array(
			'score'   => $score,
			'results' => $results,
		);
	}

	/**
	 * Check if keyword is in title
	 *
	 * @param string $keyword Keyword
	 * @param string $title Title
	 * @return array Result
	 */
	private function check_in_title( $keyword, $title ) {
		$title_lower = strtolower( $title );
		$found = strpos( $title_lower, $keyword ) !== false;

		// Check if keyword is at the beginning (bonus)
		$at_beginning = strpos( $title_lower, $keyword ) === 0;

		return array(
			'found'        => $found,
			'at_beginning' => $at_beginning,
			'status'       => $found ? ( $at_beginning ? 'great' : 'good' ) : 'missing',
			'message'      => $found 
				? ( $at_beginning 
					? __( 'Focus keyword appears at the beginning of the title', 'seovela' )
					: __( 'Focus keyword appears in the title', 'seovela' ) )
				: __( 'Focus keyword not found in title', 'seovela' ),
		);
	}

	/**
	 * Check if keyword is in meta description
	 *
	 * @param string $keyword Keyword
	 * @param string $description Description
	 * @return array Result
	 */
	private function check_in_description( $keyword, $description ) {
		$description_lower = strtolower( $description );
		$found = strpos( $description_lower, $keyword ) !== false;

		return array(
			'found'   => $found,
			'status'  => $found ? 'good' : 'missing',
			'message' => $found 
				? __( 'Focus keyword appears in meta description', 'seovela' )
				: __( 'Focus keyword not found in meta description', 'seovela' ),
		);
	}

	/**
	 * Check if keyword is in URL
	 *
	 * @param string $keyword Keyword
	 * @param string $url URL
	 * @return array Result
	 */
	private function check_in_url( $keyword, $url ) {
		$url_lower = strtolower( $url );
		$keyword_slug = sanitize_title( $keyword );
		$found = strpos( $url_lower, $keyword_slug ) !== false;

		return array(
			'found'   => $found,
			'status'  => $found ? 'good' : 'warning',
			'message' => $found 
				? __( 'Focus keyword appears in URL', 'seovela' )
				: __( 'Focus keyword not found in URL', 'seovela' ),
		);
	}

	/**
	 * Check if keyword is in content
	 *
	 * @param string $keyword Keyword
	 * @param string $content Content
	 * @return array Result
	 */
	private function check_in_content( $keyword, $content ) {
		$content_lower = strtolower( strip_tags( $content ) );
		$found = strpos( $content_lower, $keyword ) !== false;
		$count = substr_count( $content_lower, $keyword );

		return array(
			'found'   => $found,
			'count'   => $count,
			'status'  => $found ? ( $count >= 2 ? 'good' : 'warning' ) : 'missing',
			'message' => $found 
				? sprintf( __( 'Focus keyword appears %d time(s) in content', 'seovela' ), $count )
				: __( 'Focus keyword not found in content', 'seovela' ),
		);
	}

	/**
	 * Check if keyword is in first paragraph
	 *
	 * @param string $keyword Keyword
	 * @param string $content Content
	 * @return array Result
	 */
	private function check_in_first_paragraph( $keyword, $content ) {
		// Extract first paragraph
		$content_stripped = strip_tags( $content );
		$paragraphs = preg_split( '/\n\n+/', trim( $content_stripped ) );
		$first_paragraph = ! empty( $paragraphs[0] ) ? $paragraphs[0] : '';

		$first_para_lower = strtolower( $first_paragraph );
		$found = strpos( $first_para_lower, $keyword ) !== false;

		return array(
			'found'   => $found,
			'status'  => $found ? 'good' : 'warning',
			'message' => $found 
				? __( 'Focus keyword appears in first paragraph', 'seovela' )
				: __( 'Focus keyword not found in first paragraph', 'seovela' ),
		);
	}

	/**
	 * Check if keyword is in headings (H1-H3)
	 *
	 * @param string $keyword Keyword
	 * @param string $content Content
	 * @return array Result
	 */
	private function check_in_headings( $keyword, $content ) {
		// Extract headings
		preg_match_all( '/<h[123][^>]*>(.*?)<\/h[123]>/i', $content, $matches );
		$headings = $matches[1];

		$found = false;
		foreach ( $headings as $heading ) {
			if ( strpos( strtolower( strip_tags( $heading ) ), $keyword ) !== false ) {
				$found = true;
				break;
			}
		}

		return array(
			'found'   => $found,
			'status'  => $found ? 'good' : 'warning',
			'message' => $found 
				? __( 'Focus keyword appears in headings', 'seovela' )
				: __( 'Focus keyword not found in headings (H1-H3)', 'seovela' ),
		);
	}

	/**
	 * Calculate keyword density
	 *
	 * @param string $keyword Keyword
	 * @param string $content Content
	 * @return array Result
	 */
	private function calculate_density( $keyword, $content ) {
		$content_stripped = strip_tags( $content );
		$content_lower = strtolower( $content_stripped );
		
		// Count total words
		$total_words = str_word_count( $content_stripped );
		
		if ( $total_words === 0 ) {
			return array(
				'density' => 0,
				'status'  => 'warning',
				'message' => __( 'No content to analyze', 'seovela' ),
			);
		}

		// Count keyword occurrences
		$keyword_count = substr_count( $content_lower, $keyword );
		
		// Calculate density percentage
		$density = ( $keyword_count / $total_words ) * 100;

		// Ideal density is 0.5% - 2.5%
		$status = 'good';
		$message = sprintf( __( 'Keyword density: %.2f%%', 'seovela' ), $density );

		if ( $density < 0.5 ) {
			$status = 'warning';
			$message = sprintf( __( 'Keyword density is low (%.2f%%). Aim for 0.5-2.5%%', 'seovela' ), $density );
		} elseif ( $density > 2.5 ) {
			$status = 'warning';
			$message = sprintf( __( 'Keyword density is high (%.2f%%). Aim for 0.5-2.5%%', 'seovela' ), $density );
		}

		return array(
			'density' => round( $density, 2 ),
			'count'   => $keyword_count,
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Check if keyword is in image alt text
	 *
	 * @param string $keyword Keyword
	 * @param string $content Content
	 * @return array Result
	 */
	private function check_in_alt_text( $keyword, $content ) {
		// Extract images with alt text
		preg_match_all( '/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $content, $matches );
		$alt_texts = $matches[1];

		if ( empty( $alt_texts ) ) {
			return array(
				'found'   => false,
				'status'  => 'neutral',
				'message' => __( 'No images with alt text found', 'seovela' ),
			);
		}

		$found = false;
		foreach ( $alt_texts as $alt ) {
			if ( strpos( strtolower( $alt ), $keyword ) !== false ) {
				$found = true;
				break;
			}
		}

		return array(
			'found'   => $found,
			'status'  => $found ? 'good' : 'warning',
			'message' => $found 
				? __( 'Focus keyword appears in image alt text', 'seovela' )
				: __( 'Consider adding focus keyword to image alt text', 'seovela' ),
		);
	}

	/**
	 * Calculate overall keyword score (0-100)
	 *
	 * @param array $results Analysis results
	 * @return int Score
	 */
	private function calculate_keyword_score( $results ) {
		$score = 0;
		$max_score = 100;

		// Title (25 points)
		if ( $results['in_title']['found'] ) {
			$score += $results['in_title']['at_beginning'] ? 25 : 20;
		}

		// Meta Description (15 points)
		if ( $results['in_description']['found'] ) {
			$score += 15;
		}

		// URL (10 points)
		if ( $results['in_url']['found'] ) {
			$score += 10;
		}

		// Content (20 points)
		if ( $results['in_content']['found'] ) {
			$count = $results['in_content']['count'];
			$score += min( 20, $count * 5 ); // 5 points per occurrence, max 20
		}

		// First Paragraph (10 points)
		if ( $results['in_first_paragraph']['found'] ) {
			$score += 10;
		}

		// Headings (10 points)
		if ( $results['in_headings']['found'] ) {
			$score += 10;
		}

		// Density (10 points)
		if ( $results['density']['status'] === 'good' ) {
			$score += 10;
		} elseif ( $results['density']['status'] === 'warning' ) {
			$score += 5;
		}

		// Alt Text (bonus 5 points, doesn't affect max score)
		if ( $results['in_alt_text']['found'] ) {
			$score += 5;
		}

		return min( $max_score, $score );
	}
}

