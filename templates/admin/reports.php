<?php
/**
 * Admin reports template.
 *
 * @package IHumbak\Invoices
 *
 * @var array<int, array<string, mixed>> $report_data
 * @var array<string, mixed>             $totals
 * @var int                              $year
 * @var int                              $month
 * @var string                           $document_type
 * @var array<int>                       $available_years
 * @var array<int, string>               $month_options
 * @var array<string, string>            $document_type_options
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap ihumbak-invoices-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Monthly Reports', 'ihumbak-invoices' ); ?>
	</h1>
	<hr class="wp-header-end">

	<!-- Filter Form -->
	<form method="get" class="ihumbak-report-filters" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
		<input type="hidden" name="page" value="ihumbak-invoices-reports">

		<label for="year" class="screen-reader-text"><?php esc_html_e( 'Year', 'ihumbak-invoices' ); ?></label>
		<select name="year" id="year">
			<?php foreach ( $available_years as $ihumbak_year_option ) : ?>
				<option value="<?php echo esc_attr( $ihumbak_year_option ); ?>" <?php selected( $year, $ihumbak_year_option ); ?>>
					<?php echo esc_html( $ihumbak_year_option ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="month" class="screen-reader-text"><?php esc_html_e( 'Month', 'ihumbak-invoices' ); ?></label>
		<select name="month" id="month">
			<?php foreach ( $month_options as $ihumbak_month_num => $ihumbak_month_name ) : ?>
				<option value="<?php echo esc_attr( $ihumbak_month_num ); ?>" <?php selected( $month, $ihumbak_month_num ); ?>>
					<?php echo esc_html( $ihumbak_month_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="document_type" class="screen-reader-text"><?php esc_html_e( 'Document Type', 'ihumbak-invoices' ); ?></label>
		<select name="document_type" id="document_type">
			<?php foreach ( $document_type_options as $ihumbak_doc_type => $ihumbak_doc_label ) : ?>
				<option value="<?php echo esc_attr( $ihumbak_doc_type ); ?>" <?php selected( $document_type, $ihumbak_doc_type ); ?>>
					<?php echo esc_html( $ihumbak_doc_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Generate Report', 'ihumbak-invoices' ); ?>
		</button>
	</form>

	<!-- Report Title -->
	<h2>
		<?php
		printf(
			/* translators: 1: Document type, 2: Month name, 3: Year */
			esc_html__( 'Report: %1$s - %2$s %3$d', 'ihumbak-invoices' ),
			esc_html( $document_type_options[ $document_type ] ?? $document_type ),
			esc_html( $month_options[ $month ] ?? $month ),
			esc_html( $year )
		);
		?>
	</h2>

	<!-- Results Table -->
	<?php if ( ! empty( $report_data ) ) : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Payment Method', 'ihumbak-invoices' ); ?></th>
					<th scope="col" class="num" style="text-align: right;"><?php esc_html_e( 'Documents', 'ihumbak-invoices' ); ?></th>
					<th scope="col" class="num" style="text-align: right;"><?php esc_html_e( 'Net Total', 'ihumbak-invoices' ); ?></th>
					<th scope="col" class="num" style="text-align: right;"><?php esc_html_e( 'VAT Total', 'ihumbak-invoices' ); ?></th>
					<th scope="col" class="num" style="text-align: right;"><?php esc_html_e( 'Gross Total', 'ihumbak-invoices' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $report_data as $ihumbak_report_row ) : ?>
					<tr>
						<td><?php echo esc_html( $ihumbak_report_row['payment_method_name'] ); ?></td>
						<td class="num" style="text-align: right;"><?php echo esc_html( $ihumbak_report_row['document_count'] ); ?></td>
						<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $ihumbak_report_row['net_total'], 2, ',', ' ' ) ); ?></td>
						<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $ihumbak_report_row['vat_total'], 2, ',', ' ' ) ); ?></td>
						<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $ihumbak_report_row['gross_total'], 2, ',', ' ' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr style="font-weight: bold; background-color: #f0f0f1;">
					<th scope="row"><?php esc_html_e( 'TOTAL', 'ihumbak-invoices' ); ?></th>
					<td class="num" style="text-align: right;"><?php echo esc_html( $totals['document_count'] ); ?></td>
					<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $totals['net_total'], 2, ',', ' ' ) ); ?></td>
					<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $totals['vat_total'], 2, ',', ' ' ) ); ?></td>
					<td class="num" style="text-align: right;"><?php echo esc_html( number_format( (float) $totals['gross_total'], 2, ',', ' ' ) ); ?></td>
				</tr>
			</tfoot>
		</table>

		<!-- Export Button -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
			<input type="hidden" name="action" value="ihumbak_export_report_csv">
			<input type="hidden" name="year" value="<?php echo esc_attr( $year ); ?>">
			<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
			<input type="hidden" name="document_type" value="<?php echo esc_attr( $document_type ); ?>">
			<?php wp_nonce_field( 'ihumbak_export_report', 'ihumbak_export_nonce' ); ?>
			<button type="submit" class="button">
				<span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
				<?php esc_html_e( 'Export to CSV', 'ihumbak-invoices' ); ?>
			</button>
		</form>

	<?php else : ?>
		<div class="notice notice-info" style="margin-top: 20px;">
			<p>
				<?php esc_html_e( 'No documents found for the selected period.', 'ihumbak-invoices' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
