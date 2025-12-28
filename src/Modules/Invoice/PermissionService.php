<?php
/**
 * Permission Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

/**
 * Service for checking user permissions for invoice management.
 *
 * Provides centralized permission checking with configurable minimum role.
 * Settings page is always restricted to administrators only.
 */
class PermissionService {

	/**
	 * Option key for plugin settings.
	 */
	public const OPTION_KEY = 'ihumbak_invoices_settings';

	/**
	 * Default minimum role for document management.
	 */
	public const DEFAULT_ROLE = 'shop_manager';

	/**
	 * Capability required for accessing settings.
	 */
	public const SETTINGS_CAPABILITY = 'manage_options';

	/**
	 * Map of roles to their required capabilities.
	 *
	 * @var array<string, string>
	 */
	private const ROLE_CAPABILITY_MAP = array(
		'administrator' => 'manage_options',
		'shop_manager'  => 'manage_woocommerce',
	);

	/**
	 * Get the minimum capability required for document management.
	 *
	 * Reads from plugin settings, falls back to manage_woocommerce.
	 *
	 * @return string The capability slug.
	 */
	public function getMinimumCapability(): string {
		$settings     = get_option( self::OPTION_KEY, array() );
		$minimum_role = $settings['permissions']['minimum_role'] ?? self::DEFAULT_ROLE;

		return self::ROLE_CAPABILITY_MAP[ $minimum_role ] ?? 'manage_woocommerce';
	}

	/**
	 * Check if current user can manage documents.
	 *
	 * Uses the configurable minimum role from settings.
	 *
	 * @return bool True if user can manage documents.
	 */
	public function canManageDocuments(): bool {
		$capability = $this->getMinimumCapability();

		$can_manage = current_user_can( $capability );

		/**
		 * Filter whether the current user can manage documents.
		 *
		 * @since 0.1.0
		 *
		 * @param bool   $can_manage Whether user can manage documents.
		 * @param string $capability The required capability.
		 * @param int    $user_id    Current user ID.
		 */
		return apply_filters(
			'ihumbak_can_manage_documents',
			$can_manage,
			$capability,
			get_current_user_id()
		);
	}

	/**
	 * Check if current user can access plugin settings.
	 *
	 * Settings are always restricted to administrators only.
	 *
	 * @return bool True if user can access settings.
	 */
	public function canAccessSettings(): bool {
		return current_user_can( self::SETTINGS_CAPABILITY );
	}

	/**
	 * Get available roles for the settings dropdown.
	 *
	 * @return array<string, string> Role slug => label pairs.
	 */
	public static function getAvailableRoles(): array {
		return array(
			'shop_manager'  => __( 'Shop Manager (default)', 'ihumbak-invoices' ),
			'administrator' => __( 'Administrator only', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Validate if a role is valid for permission settings.
	 *
	 * @param string $role Role to validate.
	 * @return bool True if role is valid.
	 */
	public static function isValidRole( string $role ): bool {
		return array_key_exists( $role, self::ROLE_CAPABILITY_MAP );
	}
}
