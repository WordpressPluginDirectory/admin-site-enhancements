<?php

namespace ASENHA\Classes;

/**
 * Class for Show Custom Taxonomy Filters module
 *
 * @since 6.9.5
 */
class Show_Custom_Taxonomy_Filters {
    // Excluded taxonomies
    private $inapplicable_taxonomies = array(
        'category',
        // Posts Categories
        'product_cat',
        // WooCommerce Product Categories
        'asenha_code_snippet_category',
        // ASE Code Snippets Categories
        'asenha-media-category',
    );

    /**
     * Show custom (hierarchical) taxonomy filter(s) for all post types.
     *
     * @since 1.0.0
     */
    public function show_custom_taxonomy_filters( $post_type ) {
        $object_taxonomies = get_object_taxonomies( $post_type, 'objects' );
        array_walk( $object_taxonomies, [$this, 'output_taxonomy_filter'] );
    }

    /**
     * Output filter on the post type's list table for a taxonomy
     *
     * @since 1.0.0
     */
    public function output_taxonomy_filter( $taxonomy ) {
        // Show filter if taxonomy is hierarchical
        if ( true === $taxonomy->hierarchical && !in_array( $taxonomy->name, $this->inapplicable_taxonomies ) ) {
            $this->render_additional_filter( $taxonomy );
        }
    }

    /**
     * Render additional filter
     * 
     * @since 6.9.7
     */
    public function render_additional_filter( $taxonomy ) {
        wp_dropdown_categories( array(
            'show_option_all' => sprintf( 'All %s', ucwords( $taxonomy->label ) ),
            'orderby'         => 'name',
            'order'           => 'ASC',
            'hide_empty'      => false,
            'hide_if_empty'   => true,
            'selected'        => sanitize_text_field( ( isset( $_GET[$taxonomy->query_var] ) && !empty( $_GET[$taxonomy->query_var] ) ? sanitize_text_field( $_GET[$taxonomy->query_var] ) : '' ) ),
            'hierarchical'    => true,
            'name'            => $taxonomy->query_var,
            'taxonomy'        => $taxonomy->name,
            'value_field'     => 'slug',
        ) );
    }

}
