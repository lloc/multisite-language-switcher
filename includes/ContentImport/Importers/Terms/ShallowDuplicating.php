<?php

namespace lloc\Msls\ContentImport\Importers\Terms;

use lloc\Msls\ContentImport\Importers\BaseImporter;
use lloc\Msls\MslsOptionsTaxTerm;

/**
 * Class ShallowDuplicating
 *
 * Duplicates, if needed, the terms assigned to the post without recursion for hierarchical taxonomies.
 *
 * @package lloc\Msls\ContentImport\Importers\Terms
 */
class ShallowDuplicating extends BaseImporter {

	const TYPE = 'shallow-duplicating';

	/**
	 * Returns an array of information about the importer.
	 *
	 * @return \stdClass
	 */
	public static function info() {
		return (object) [
			'slug'        => static::TYPE,
			'name'        => __( 'Shallow Duplicating', 'multisite-language-switcher' ),
			'description' => __( 'Shallow (one level deep) duplication or assignment of the source post taxonomy terms to the destnation post.', 'multisite-language-switcher' )
		];
	}

	public function import( array $data ) {
		$source_blog_id = $this->import_coordinates->source_blog_id;
		$source_post_id = $this->import_coordinates->source_post_id;
		$dest_post_id   = $this->import_coordinates->dest_post_id;
		$dest_lang      = $this->import_coordinates->dest_lang;

		switch_to_blog( $source_blog_id );

		$source_terms     = wp_get_post_terms( $source_post_id, get_taxonomies() );
		$source_terms_ids = wp_list_pluck( $source_terms, 'term_id' );
		$msls_terms       = array_combine(
			$source_terms_ids,
			array_map( array( MslsOptionsTaxTerm::class, 'create' ), $source_terms_ids )
		);

		restore_current_blog();

		/** @var \WP_Term $term */
		foreach ( $source_terms as $term ) {
			// is there a translation for the term in this blog?
			$msls_term    = $msls_terms[ $term->term_id ];
			$dest_term_id = $msls_term->{$dest_lang};

			if ( null === $dest_term_id ) {
				$meta         = get_term_meta( $term->term_id );
				$dest_term_id = wp_create_term( $term->name, $term->taxonomy );

				if ( $dest_term_id instanceof \WP_Error ) {
					$this->logger->log_error( "term/created/{$term->taxonomy}", [ $term->name ] );
					continue;
				}

				$dest_term_id = (int) reset( $dest_term_id );
				$this->relations->should_create( $msls_term, $dest_lang, $dest_term_id );
				$this->logger->log_success( "term/created/{$term->taxonomy}", [ $term->name => $term->term_id ] );
				$meta = $this->filter_term_meta( $meta, $term );
				if ( ! empty( $meta ) ) {
					foreach ( $meta as $key => $value ) {
						add_term_meta( $dest_term_id, $key, $value );
					}
				}
			}

			$added = wp_add_object_terms( $dest_post_id, $dest_term_id, $term->taxonomy );

			if ( $added instanceof \WP_Error ) {
				$this->logger->log_error( "term/added/{$term->taxonomy}", array( $term->name => $term->term_id ) );
			} else {
				$this->logger->log_success( "term/added/{$term->taxonomy}", array( $term->name => $term->term_id ) );
			}
		}

		return $data;
	}

	protected function filter_term_meta( array $meta, \WP_Term $term ) {
		/**
		 * Filters the list of term meta that should not be imported for a term.
		 *
		 * @since TBD
		 *
		 * @param array $blacklist
		 * @param \WP_Term $term
		 * @param array $meta
		 * @param ImportCoordinates $import_coordinates
		 */
		$blacklist = apply_filters( 'msls_content_import_term_meta_blacklist', array(), $term, $meta, $this->import_coordinates );

		return array_diff_key( $meta, array_combine( $blacklist, $blacklist ) );
	}
}