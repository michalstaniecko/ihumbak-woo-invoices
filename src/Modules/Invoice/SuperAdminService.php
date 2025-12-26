<?php
/**
 * Super Admin Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

use IHumbak\Invoices\Models\Document;

/**
 * Service for checking super-admin permissions.
 *
 * Super-admins can perform privileged operations like reverting
 * document status from issued/sent/paid to draft.
 */
class SuperAdminService {

	/**
	 * Constant name in wp-config.php.
	 */
	public const CONFIG_CONSTANT = 'IHUMBAK_SUPER_ADMIN_IDS';

	/**
	 * Statuses that can be reverted to draft.
	 *
	 * @var string[]
	 */
	private const REVERTABLE_STATUSES = array(
		Document::STATUS_ISSUED,
		Document::STATUS_SENT,
		Document::STATUS_PAID,
	);

	/**
	 * Check if current user is a super-admin.
	 *
	 * @return bool True if current user is a super-admin.
	 */
	public function isCurrentUserSuperAdmin(): bool {
		$current_user_id = get_current_user_id();

		if ( 0 === $current_user_id ) {
			return false;
		}

		$is_super_admin = $this->isUserSuperAdmin( $current_user_id );

		/**
		 * Filter whether the current user is a super-admin.
		 *
		 * Allows extending the super-admin check with custom logic.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $is_super_admin Whether user is super-admin based on config.
		 * @param int  $user_id        Current user ID.
		 */
		return apply_filters( 'ihumbak_is_current_user_super_admin', $is_super_admin, $current_user_id );
	}

	/**
	 * Check if a specific user is a super-admin.
	 *
	 * @param int $user_id User ID to check.
	 * @return bool True if user is a super-admin.
	 */
	public function isUserSuperAdmin( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		$super_admin_ids = $this->getSuperAdminIds();
		$is_super_admin  = in_array( $user_id, $super_admin_ids, true );

		/**
		 * Filter whether a specific user is a super-admin.
		 *
		 * Allows extending the super-admin check with custom logic.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $is_super_admin Whether user is super-admin based on config.
		 * @param int  $user_id        User ID being checked.
		 */
		return apply_filters( 'ihumbak_is_user_super_admin', $is_super_admin, $user_id );
	}

	/**
	 * Get list of super-admin user IDs.
	 *
	 * Reads from IHUMBAK_SUPER_ADMIN_IDS constant defined in wp-config.php.
	 * Format: comma-separated list of user IDs, e.g., "1,5,12"
	 *
	 * @return int[] Array of super-admin user IDs.
	 */
	public function getSuperAdminIds(): array {
		if ( ! defined( self::CONFIG_CONSTANT ) ) {
			return array();
		}

		$ids_string = constant( self::CONFIG_CONSTANT );

		if ( ! is_string( $ids_string ) || '' === trim( $ids_string ) ) {
			return array();
		}

		$ids = array_map( 'trim', explode( ',', $ids_string ) );
		$ids = array_filter( $ids, fn( $id ) => is_numeric( $id ) && (int) $id > 0 );

		return array_map( 'intval', $ids );
	}

	/**
	 * Check if user can revert document status to draft.
	 *
	 * Only super-admins can revert issued/sent/paid documents to draft.
	 * Cancelled documents cannot be reverted.
	 * Draft documents are already in draft status.
	 *
	 * @param int    $user_id        User ID.
	 * @param string $current_status Current document status.
	 * @return bool True if user can revert status.
	 */
	public function canRevertToDraft( int $user_id, string $current_status ): bool {
		// Only issued, sent, paid documents can be reverted.
		if ( ! in_array( $current_status, self::REVERTABLE_STATUSES, true ) ) {
			return false;
		}

		return $this->isUserSuperAdmin( $user_id );
	}

	/**
	 * Get list of statuses that can be reverted to draft.
	 *
	 * @return string[] Array of revertable status constants.
	 */
	public static function getRevertableStatuses(): array {
		return self::REVERTABLE_STATUSES;
	}
}
