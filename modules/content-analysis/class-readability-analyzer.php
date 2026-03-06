<?php
/**
 * Seovela Readability Analyzer
 *
 * Analyzes content readability using various metrics
 *
 * @package Seovela
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seovela Readability Analyzer Class
 */
class Seovela_Readability_Analyzer {

	/**
	 * Analyze content readability
	 *
	 * @param string $content Content to analyze
	 * @return array Analysis results
	 */
	public function analyze( $content ) {
		if ( empty( $content ) ) {
			return array(
				'score'   => 0,
				'results' => array(),
			);
		}

		$content_stripped = strip_tags( $content );
		$results = array();

		// 1. Flesch Reading Ease score
		$results['flesch'] = $this->calculate_flesch_score( $content_stripped );

		// 2. Sentence length analysis
		$results['sentences'] = $this->analyze_sentences( $content_stripped );

		// 3. Paragraph length analysis
		$results['paragraphs'] = $this->analyze_paragraphs( $content );

		// 4. Subheading distribution
		$results['subheadings'] = $this->analyze_subheadings( $content );

		// 5. Passive voice detection (basic)
		$results['passive_voice'] = $this->detect_passive_voice( $content_stripped );

		// 6. Transition words
		$results['transition_words'] = $this->analyze_transition_words( $content_stripped );

		// Calculate overall readability score
		$score = $this->calculate_readability_score( $results );

		return array(
			'score'   => $score,
			'results' => $results,
		);
	}

	/**
	 * Calculate Flesch Reading Ease score
	 *
	 * @param string $content Content
	 * @return array Result
	 */
	private function calculate_flesch_score( $content ) {
		$sentences = $this->count_sentences( $content );
		$words = str_word_count( $content );
		$syllables = $this->count_syllables( $content );

		if ( $sentences === 0 || $words === 0 ) {
			return array(
				'score'  => 0,
				'level'  => 'none',
				'status' => 'warning',
				'message' => __( 'Not enough content to calculate readability', 'seovela' ),
			);
		}

		// Flesch Reading Ease formula: 206.835 - 1.015(words/sentences) - 84.6(syllables/words)
		$score = 206.835 - 1.015 * ( $words / $sentences ) - 84.6 * ( $syllables / $words );
		$score = max( 0, min( 100, $score ) ); // Clamp between 0-100

		// Determine readability level
		$level = $this->get_flesch_level( $score );
		$status = $this->get_flesch_status( $score );

		return array(
			'score'     => round( $score, 1 ),
			'level'     => $level,
			'status'    => $status,
			'message'   => sprintf( __( 'Flesch Reading Ease: %.1f (%s)', 'seovela' ), $score, $level ),
			'words'     => $words,
			'sentences' => $sentences,
			'syllables' => $syllables,
		);
	}

	/**
	 * Get Flesch readability level
	 *
	 * @param float $score Flesch score
	 * @return string Level description
	 */
	private function get_flesch_level( $score ) {
		if ( $score >= 90 ) {
			return __( 'Very Easy', 'seovela' );
		} elseif ( $score >= 80 ) {
			return __( 'Easy', 'seovela' );
		} elseif ( $score >= 70 ) {
			return __( 'Fairly Easy', 'seovela' );
		} elseif ( $score >= 60 ) {
			return __( 'Standard', 'seovela' );
		} elseif ( $score >= 50 ) {
			return __( 'Fairly Difficult', 'seovela' );
		} elseif ( $score >= 30 ) {
			return __( 'Difficult', 'seovela' );
		} else {
			return __( 'Very Difficult', 'seovela' );
		}
	}

	/**
	 * Get Flesch status
	 *
	 * @param float $score Flesch score
	 * @return string Status
	 */
	private function get_flesch_status( $score ) {
		if ( $score >= 60 ) {
			return 'good';
		} elseif ( $score >= 40 ) {
			return 'warning';
		} else {
			return 'error';
		}
	}

	/**
	 * Count sentences in content
	 *
	 * @param string $content Content
	 * @return int Sentence count
	 */
	private function count_sentences( $content ) {
		// Split by sentence-ending punctuation
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		return count( $sentences );
	}

	/**
	 * Count syllables in content (approximation)
	 *
	 * @param string $content Content
	 * @return int Syllable count
	 */
	private function count_syllables( $content ) {
		$words = str_word_count( strtolower( $content ), 1 );
		$syllable_count = 0;

		foreach ( $words as $word ) {
			$syllable_count += $this->count_syllables_in_word( $word );
		}

		return max( 1, $syllable_count );
	}

	/**
	 * Count syllables in a single word
	 *
	 * @param string $word Word
	 * @return int Syllable count
	 */
	private function count_syllables_in_word( $word ) {
		$word = strtolower( $word );
		$word = preg_replace( '/[^a-z]/', '', $word );

		if ( strlen( $word ) <= 3 ) {
			return 1;
		}

		// Remove silent 'e' at the end
		$word = preg_replace( '/e$/', '', $word );

		// Count vowel groups
		$vowels = array( 'a', 'e', 'i', 'o', 'u', 'y' );
		$syllables = 0;
		$previous_was_vowel = false;

		for ( $i = 0; $i < strlen( $word ); $i++ ) {
			$is_vowel = in_array( $word[ $i ], $vowels );

			if ( $is_vowel && ! $previous_was_vowel ) {
				$syllables++;
			}

			$previous_was_vowel = $is_vowel;
		}

		return max( 1, $syllables );
	}

	/**
	 * Analyze sentence length
	 *
	 * @param string $content Content
	 * @return array Result
	 */
	private function analyze_sentences( $content ) {
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		
		if ( empty( $sentences ) ) {
			return array(
				'count'          => 0,
				'average_length' => 0,
				'status'         => 'warning',
				'message'        => __( 'No sentences detected', 'seovela' ),
			);
		}

		$total_words = 0;
		$long_sentences = 0;

		foreach ( $sentences as $sentence ) {
			$word_count = str_word_count( $sentence );
			$total_words += $word_count;

			if ( $word_count > 20 ) {
				$long_sentences++;
			}
		}

		$average_length = $total_words / count( $sentences );
		$long_percentage = ( $long_sentences / count( $sentences ) ) * 100;

		// Good: average < 20 words, <25% long sentences
		$status = 'good';
		$message = sprintf( __( 'Average sentence length: %.1f words', 'seovela' ), $average_length );

		if ( $average_length > 25 || $long_percentage > 30 ) {
			$status = 'error';
			$message = __( 'Sentences are too long. Aim for average of 15-20 words', 'seovela' );
		} elseif ( $average_length > 20 || $long_percentage > 25 ) {
			$status = 'warning';
			$message = __( 'Some sentences are long. Consider breaking them up', 'seovela' );
		}

		return array(
			'count'           => count( $sentences ),
			'average_length'  => round( $average_length, 1 ),
			'long_sentences'  => $long_sentences,
			'long_percentage' => round( $long_percentage, 1 ),
			'status'          => $status,
			'message'         => $message,
		);
	}

	/**
	 * Analyze paragraph length
	 *
	 * @param string $content Content (with HTML)
	 * @return array Result
	 */
	private function analyze_paragraphs( $content ) {
		// Split by paragraph tags or double newlines
		$content_stripped = strip_tags( $content, '<p>' );
		$paragraphs = preg_split( '/<p[^>]*>|<\/p>|\n\n+/', $content_stripped, -1, PREG_SPLIT_NO_EMPTY );
		$paragraphs = array_filter( array_map( 'trim', $paragraphs ) );

		if ( empty( $paragraphs ) ) {
			return array(
				'count'          => 0,
				'average_length' => 0,
				'status'         => 'warning',
				'message'        => __( 'No paragraphs detected', 'seovela' ),
			);
		}

		$total_words = 0;
		$long_paragraphs = 0;

		foreach ( $paragraphs as $paragraph ) {
			$word_count = str_word_count( $paragraph );
			$total_words += $word_count;

			if ( $word_count > 150 ) {
				$long_paragraphs++;
			}
		}

		$average_length = $total_words / count( $paragraphs );
		$long_percentage = ( $long_paragraphs / count( $paragraphs ) ) * 100;

		// Good: average < 150 words
		$status = 'good';
		$message = sprintf( __( 'Average paragraph length: %.1f words', 'seovela' ), $average_length );

		if ( $average_length > 200 || $long_percentage > 30 ) {
			$status = 'error';
			$message = __( 'Paragraphs are too long. Aim for 50-150 words per paragraph', 'seovela' );
		} elseif ( $average_length > 150 || $long_percentage > 25 ) {
			$status = 'warning';
			$message = __( 'Some paragraphs are long. Consider breaking them up', 'seovela' );
		}

		return array(
			'count'           => count( $paragraphs ),
			'average_length'  => round( $average_length, 1 ),
			'long_paragraphs' => $long_paragraphs,
			'long_percentage' => round( $long_percentage, 1 ),
			'status'          => $status,
			'message'         => $message,
		);
	}

	/**
	 * Analyze subheading distribution
	 *
	 * @param string $content Content (with HTML)
	 * @return array Result
	 */
	private function analyze_subheadings( $content ) {
		// Count H2 and H3 tags
		preg_match_all( '/<h2[^>]*>.*?<\/h2>/i', $content, $h2_matches );
		preg_match_all( '/<h3[^>]*>.*?<\/h3>/i', $content, $h3_matches );

		$h2_count = count( $h2_matches[0] );
		$h3_count = count( $h3_matches[0] );
		$total_headings = $h2_count + $h3_count;

		// Count words
		$word_count = str_word_count( strip_tags( $content ) );

		if ( $word_count < 300 ) {
			return array(
				'count'   => $total_headings,
				'status'  => 'neutral',
				'message' => __( 'Content is short, subheadings not critical', 'seovela' ),
			);
		}

		// Good: at least 1 subheading per 300 words
		$recommended = max( 1, floor( $word_count / 300 ) );

		$status = 'good';
		$message = sprintf( __( '%d subheadings found', 'seovela' ), $total_headings );

		if ( $total_headings === 0 ) {
			$status = 'error';
			$message = __( 'No subheadings found. Add H2/H3 tags to improve readability', 'seovela' );
		} elseif ( $total_headings < $recommended ) {
			$status = 'warning';
			$message = sprintf( 
				__( 'Consider adding more subheadings (recommended: %d for %d words)', 'seovela' ),
				$recommended,
				$word_count
			);
		}

		return array(
			'count'       => $total_headings,
			'h2_count'    => $h2_count,
			'h3_count'    => $h3_count,
			'recommended' => $recommended,
			'status'      => $status,
			'message'     => $message,
		);
	}

	/**
	 * Detect passive voice (basic implementation)
	 *
	 * @param string $content Content
	 * @return array Result
	 */
	private function detect_passive_voice( $content ) {
		// Common passive voice indicators
		$passive_indicators = array(
			'/\b(am|is|are|was|were|be|been|being)\s+\w+ed\b/i',
			'/\b(am|is|are|was|were|be|been|being)\s+\w+en\b/i',
		);

		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$passive_count = 0;

		foreach ( $sentences as $sentence ) {
			foreach ( $passive_indicators as $pattern ) {
				if ( preg_match( $pattern, $sentence ) ) {
					$passive_count++;
					break;
				}
			}
		}

		$total_sentences = count( $sentences );
		$passive_percentage = $total_sentences > 0 ? ( $passive_count / $total_sentences ) * 100 : 0;

		// Good: < 10% passive voice
		$status = 'good';
		$message = sprintf( __( '%.1f%% passive voice', 'seovela' ), $passive_percentage );

		if ( $passive_percentage > 20 ) {
			$status = 'error';
			$message = sprintf( 
				__( '%.1f%% passive voice detected. Aim for less than 10%%', 'seovela' ),
				$passive_percentage
			);
		} elseif ( $passive_percentage > 10 ) {
			$status = 'warning';
			$message = sprintf( 
				__( '%.1f%% passive voice. Consider using more active voice', 'seovela' ),
				$passive_percentage
			);
		}

		return array(
			'count'      => $passive_count,
			'percentage' => round( $passive_percentage, 1 ),
			'status'     => $status,
			'message'    => $message,
		);
	}

	/**
	 * Analyze transition words usage
	 *
	 * @param string $content Content
	 * @return array Result
	 */
	private function analyze_transition_words( $content ) {
		// Common transition words
		$transition_words = array(
			'however', 'therefore', 'furthermore', 'moreover', 'consequently',
			'nevertheless', 'thus', 'hence', 'accordingly', 'meanwhile',
			'additionally', 'similarly', 'likewise', 'conversely', 'alternatively',
			'specifically', 'especially', 'notably', 'particularly', 'indeed',
			'in fact', 'for example', 'for instance', 'in other words', 'as a result',
			'in conclusion', 'to summarize', 'in summary', 'first', 'second', 'third',
			'finally', 'lastly', 'next', 'then', 'also', 'besides', 'in addition',
		);

		$content_lower = strtolower( $content );
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$transition_count = 0;

		foreach ( $sentences as $sentence ) {
			$sentence_lower = strtolower( $sentence );
			foreach ( $transition_words as $word ) {
				if ( preg_match( '/\b' . preg_quote( $word, '/' ) . '\b/', $sentence_lower ) ) {
					$transition_count++;
					break;
				}
			}
		}

		$total_sentences = count( $sentences );
		$transition_percentage = $total_sentences > 0 ? ( $transition_count / $total_sentences ) * 100 : 0;

		// Good: > 20% of sentences with transition words
		$status = 'good';
		$message = sprintf( __( '%.1f%% of sentences contain transition words', 'seovela' ), $transition_percentage );

		if ( $transition_percentage < 10 ) {
			$status = 'error';
			$message = sprintf( 
				__( 'Only %.1f%% of sentences have transition words. Aim for at least 20%%', 'seovela' ),
				$transition_percentage
			);
		} elseif ( $transition_percentage < 20 ) {
			$status = 'warning';
			$message = sprintf( 
				__( '%.1f%% of sentences have transition words. Aim for at least 20%%', 'seovela' ),
				$transition_percentage
			);
		}

		return array(
			'count'      => $transition_count,
			'percentage' => round( $transition_percentage, 1 ),
			'status'     => $status,
			'message'    => $message,
		);
	}

	/**
	 * Calculate overall readability score (0-100)
	 *
	 * @param array $results Analysis results
	 * @return int Score
	 */
	private function calculate_readability_score( $results ) {
		$score = 0;

		// Flesch score (40 points) - normalize to 0-40 range
		if ( isset( $results['flesch']['score'] ) && $results['flesch']['score'] > 0 ) {
			$flesch_normalized = ( $results['flesch']['score'] / 100 ) * 40;
			$score += $flesch_normalized;
		}

		// Sentence length (20 points)
		if ( $results['sentences']['status'] === 'good' ) {
			$score += 20;
		} elseif ( $results['sentences']['status'] === 'warning' ) {
			$score += 10;
		}

		// Paragraph length (15 points)
		if ( $results['paragraphs']['status'] === 'good' ) {
			$score += 15;
		} elseif ( $results['paragraphs']['status'] === 'warning' ) {
			$score += 8;
		}

		// Subheadings (10 points)
		if ( $results['subheadings']['status'] === 'good' ) {
			$score += 10;
		} elseif ( $results['subheadings']['status'] === 'warning' ) {
			$score += 5;
		}

		// Passive voice (10 points)
		if ( $results['passive_voice']['status'] === 'good' ) {
			$score += 10;
		} elseif ( $results['passive_voice']['status'] === 'warning' ) {
			$score += 5;
		}

		// Transition words (5 points)
		if ( $results['transition_words']['status'] === 'good' ) {
			$score += 5;
		} elseif ( $results['transition_words']['status'] === 'warning' ) {
			$score += 2;
		}

		return round( min( 100, $score ) );
	}
}

