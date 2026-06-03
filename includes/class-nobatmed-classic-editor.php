<?php
/**
 * Force Classic Editor (TinyMCE) for NobatMed CPTs — not Gutenberg.
 *
 * All current and future NobatMed post types must register via
 * nobatmed_classic_editor_post_types filter.
 *
 * @package NobatMedCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classic editor policy.
 */
class NobatMed_Classic_Editor {

	/**
	 * Post types that must use the classic editor.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		$types = array(
			'nm_doctor',
			'nm_clinic',
			'nm_service',
		);

		/**
		 * CPT slugs that use Classic Editor instead of Gutenberg.
		 * Add future CPTs here from add-on plugins.
		 *
		 * @param string[] $types Post type slugs.
		 */
		return apply_filters( 'nobatmed_classic_editor_post_types', $types );
	}

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'disable_block_editor' ), 100, 2 );
		add_filter( 'gutenberg_can_edit_post_type', array( self::class, 'disable_block_editor' ), 100, 2 );

		// Classic Editor plugin (if installed).
		add_filter( 'classic_editor_enabled_editors_for_post_type', array( self::class, 'classic_editor_default' ), 100, 2 );
		add_filter( 'classic_editor_plugin_settings', array( self::class, 'force_classic_editor_plugin_default' ) );
	}

	/**
	 * Disable block editor for NobatMed CPTs.
	 *
	 * @param bool   $enabled   Current state.
	 * @param string $post_type Post type.
	 */
	public static function disable_block_editor( bool $enabled, string $post_type ): bool {
		if ( in_array( $post_type, self::post_types(), true ) ) {
			return false;
		}
		return $enabled;
	}

	/**
	 * Classic Editor plugin: only classic for our CPTs.
	 *
	 * @param array<string,bool> $editors   Editors map.
	 * @param string             $post_type Post type.
	 * @return array<string,bool>
	 */
	public static function classic_editor_default( array $editors, string $post_type ): array {
		if ( in_array( $post_type, self::post_types(), true ) ) {
			return array(
				'classic_editor' => true,
				'block_editor'   => false,
			);
		}
		return $editors;
	}

	/**
	 * When Classic Editor plugin uses "default" setting, treat NobatMed CPTs as classic.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>
	 */
	public static function force_classic_editor_plugin_default( array $settings ): array {
		$settings['nobatmed_policy'] = array(
			'post_types' => self::post_types(),
			'note'       => 'NobatMed CPTs always use Classic Editor.',
		);
		return $settings;
	}
}
