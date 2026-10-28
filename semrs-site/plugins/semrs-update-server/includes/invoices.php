<?php
/**
 * Manual invoices (bank transfer, PayPal, Wise…): SEMRS Updates → Invoices.
 *
 * Create an invoice from the plan catalogue, send the customer a link to a
 * printable invoice page, and click "Mark paid" when the money arrives: the
 * key is created (or the customer's key gets the add-ons and one more year)
 * and emailed automatically.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * All invoices: number => data (newest first).
 *
 * @return array
 */
function semrs_us_invoices() {
	$i = get_option( 'semrs_us_invoices', array() );
	return is_array( $i ) ? $i : array();
}

/**
 * Save one invoice.
 *
 * @param array $inv Invoice.
 */
function semrs_us_save_invoice( $inv ) {
	$all                   = semrs_us_invoices();
	$all[ $inv['number'] ] = $inv;
	uksort(
		$all,
		function ( $a, $b ) {
			return strcmp( $b, $a );
		}
	);
	update_option( 'semrs_us_invoices', $all, false );
}

/**
 * Public link to an invoice.
 *
 * @param array $inv Invoice.
 * @return string
 */
function semrs_us_invoice_url( $inv ) {
	return add_query_arg( array( 'semrs_invoice' => $inv['number'], 't' => $inv['token'] ), home_url( '/' ) );
}

/**
 * Create an invoice.
 *
 * @param array $d name, email, address, plans[], discount (%), due (days), key, notes.
 * @return array The invoice.
 */
function semrs_us_create_invoice( $d ) {
	$plans = semrs_us_plans();
	$items = array();
	foreach ( (array) $d['plans'] as $id ) {
		if ( isset( $plans[ $id ] ) ) {
			$items[] = array( 'plan' => $id, 'label' => semrs_us_plan_label( $plans[ $id ] ), 'price' => $plans[ $id ]['price'] );
		}
	}
	$subtotal = array_sum( wp_list_pluck( $items, 'price' ) );
	$discount = min( 100, max( 0, (float) $d['discount'] ) );
	$seq      = (int) get_option( 'semrs_us_invoice_seq', 0 ) + 1;
	update_option( 'semrs_us_invoice_seq', $seq, false );
	$inv = array(
		'number'   => 'SEMRS-' . gmdate( 'Y' ) . '-' . str_pad( (string) $seq, 4, '0', STR_PAD_LEFT ),
		'token'    => bin2hex( random_bytes( 12 ) ),
		'date'     => gmdate( 'Y-m-d' ),
		'due'      => gmdate( 'Y-m-d', strtotime( '+' . max( 0, (int) $d['due'] ) . ' days' ) ),
		'name'     => sanitize_text_field( $d['name'] ),
		'email'    => sanitize_email( $d['email'] ),
		'address'  => sanitize_textarea_field( $d['address'] ),
		'items'    => $items,
		'subtotal' => $subtotal,
		'discount' => $discount,
		'total'    => round( $subtotal * ( 1 - $discount / 100 ), 2 ),
		'currency' => semrs_us_sales()['currency'],
		'key'      => isset( semrs_us_keys()[ $d['key'] ] ) ? $d['key'] : '',
		'notes'    => sanitize_textarea_field( $d['notes'] ),
		'status'   => 'unpaid',
		'paid'     => '',
		'sent'     => '',
	);
	semrs_us_save_invoice( $inv );
	return $inv;
}

/**
 * Email the invoice link.
 *
 * @param array $inv Invoice.
 * @return bool
 */
function semrs_us_send_invoice( $inv ) {
	$s    = semrs_us_sales();
	$body = implode(
		"\n",
		array(
			/* translators: %s: customer name */
			sprintf( __( 'Hi %s,', 'semrs-update-server' ), $inv['name'] ? $inv['name'] : __( 'there', 'semrs-update-server' ) ),
			'',
			/* translators: 1: invoice number, 2: amount, 3: due date */
			sprintf( __( 'Here is invoice %1$s for %2$s, due %3$s:', 'semrs-update-server' ), $inv['number'], semrs_us_money( $inv['total'] ), $inv['due'] ),
			semrs_us_invoice_url( $inv ),
			'',
			__( 'The invoice page has the payment details and a Print / Save as PDF button. Your license key is emailed as soon as the payment arrives.', 'semrs-update-server' ),
			'',
			$s['seller_name'],
		)
	);
	/* translators: %s: invoice number */
	return semrs_us_mail( $inv['email'], sprintf( __( 'Invoice %s from SEMRS', 'semrs-update-server' ), $inv['number'] ), $body );
}

/**
 * Mark an invoice paid: issue or extend the key and email it.
 *
 * @param array $inv Invoice.
 * @return array The invoice.
 */
function semrs_us_invoice_paid( $inv ) {
	$plans  = semrs_us_plans();
	$addons = array();
	$sites  = -1;
	$life   = false;
	foreach ( $inv['items'] as $it ) {
		if ( isset( $plans[ $it['plan'] ] ) ) {
			$p      = $plans[ $it['plan'] ];
			$addons = array_merge( $addons, $p['addons'] );
			$sites  = ( 0 === $p['sites'] || 0 === $sites ) ? 0 : max( $sites, $p['sites'] );
			$life   = $life || 'life' === $p['term'];
		}
	}
	// A renewal continues from the current expiry, so paying early loses nothing.
	$from = gmdate( 'Y-m-d' );
	if ( $inv['key'] && isset( semrs_us_keys()[ $inv['key'] ] ) ) {
		$cur  = (string) semrs_us_keys()[ $inv['key'] ]['expires'];
		$from = '' !== $cur && $cur > $from ? $cur : $from;
	}
	$key = semrs_us_grant(
		'invoice:' . $inv['number'],
		array(
			'plan'    => implode( ',', wp_list_pluck( $inv['items'], 'plan' ) ),
			'addons'  => $addons,
			'sites'   => max( 0, $sites ),
			'expires' => $life ? '' : gmdate( 'Y-m-d', strtotime( $from . ' +1 year' ) ),
		),
		$inv['email'],
		$inv['name'],
		'',
		$inv['key']
	);
	$inv['status'] = 'paid';
	$inv['paid']   = gmdate( 'Y-m-d' );
	$inv['key']    = $key;
	semrs_us_save_invoice( $inv );
	return $inv;
}

/**
 * Admin actions for invoices.
 */
function semrs_us_invoice_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us_invoice' );
	$do  = isset( $_POST['semrs_do'] ) ? sanitize_key( $_POST['semrs_do'] ) : '';
	$num = isset( $_POST['number'] ) ? sanitize_text_field( wp_unslash( $_POST['number'] ) ) : '';
	$inv = semrs_us_invoices()[ $num ] ?? null;
	$msg = '';
	if ( 'create' === $do ) {
		$inv = semrs_us_create_invoice(
			array(
				'name'     => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in semrs_us_create_invoice().
				'email'    => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'address'  => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'plans'    => isset( $_POST['plans'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['plans'] ) ) : array(),
				'discount' => isset( $_POST['discount'] ) ? (float) $_POST['discount'] : 0,
				'due'      => isset( $_POST['due'] ) ? absint( $_POST['due'] ) : 7,
				'key'      => isset( $_POST['key'] ) ? preg_replace( '/[^A-Z0-9-]/', '', strtoupper( sanitize_text_field( wp_unslash( $_POST['key'] ) ) ) ) : '',
				'notes'    => isset( $_POST['notes'] ) ? wp_unslash( $_POST['notes'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			)
		);
		if ( ! empty( $_POST['send'] ) && $inv['items'] ) {
			$inv['sent'] = semrs_us_send_invoice( $inv ) ? gmdate( 'Y-m-d' ) : '';
			semrs_us_save_invoice( $inv );
		}
		$msg = $inv['items'] ? 'created' : 'empty';
	} elseif ( $inv && 'send' === $do ) {
		$inv['sent'] = semrs_us_send_invoice( $inv ) ? gmdate( 'Y-m-d' ) : $inv['sent'];
		semrs_us_save_invoice( $inv );
		$msg = $inv['sent'] ? 'sent' : 'mailfail';
	} elseif ( $inv && 'paid' === $do && 'unpaid' === $inv['status'] ) {
		semrs_us_invoice_paid( $inv );
		$msg = 'paid';
	} elseif ( $inv && 'void' === $do && 'unpaid' === $inv['status'] ) {
		$inv['status'] = 'void';
		semrs_us_save_invoice( $inv );
		$msg = 'void';
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-updates-invoices', 'msg' => $msg ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us_invoice', 'semrs_us_invoice_actions' );

/**
 * Invoices screen.
 */
function semrs_us_page_invoices() {
	$msg    = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$notes  = array(
		'created'  => __( 'Invoice created.', 'semrs-update-server' ),
		'empty'    => __( 'Invoice created without items. Void it and pick at least one plan.', 'semrs-update-server' ),
		'sent'     => __( 'Invoice emailed.', 'semrs-update-server' ),
		'mailfail' => __( 'The email could not be sent. Copy the invoice link and send it yourself.', 'semrs-update-server' ),
		'paid'     => __( 'Marked paid. The license key was created or extended and emailed to the customer.', 'semrs-update-server' ),
		'void'     => __( 'Invoice voided.', 'semrs-update-server' ),
	);
	$prefill = isset( $_GET['key'] ) ? preg_replace( '/[^A-Z0-9-]/', '', strtoupper( sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$pk      = $prefill ? ( semrs_us_keys()[ $prefill ] ?? null ) : null;
	$form    = function ( $do, $num, $label, $class = 'button button-small' ) {
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">' . wp_nonce_field( 'semrs_us_invoice', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="semrs_us_invoice"><input type="hidden" name="semrs_do" value="' . esc_attr( $do ) . '"><input type="hidden" name="number" value="' . esc_attr( $num ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Invoices', 'semrs-update-server' ); ?></h1>
		<?php if ( isset( $notes[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo in_array( $msg, array( 'empty', 'mailfail' ), true ) ? 'warning' : 'success'; ?>"><p><?php echo esc_html( $notes[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'For customers who pay by bank transfer, PayPal, Wise or another manual method. When the money arrives, click Mark paid: the key is created (or extended by one year) and emailed automatically.', 'semrs-update-server' ); ?></p>
		<details <?php echo $prefill ? 'open' : ''; ?> style="max-width:900px;background:#fff;border:1px solid #c3c4c7;padding:12px 16px;margin:12px 0">
			<summary style="cursor:pointer;font-weight:600"><?php esc_html_e( 'New invoice', 'semrs-update-server' ); ?></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'semrs_us_invoice' ); ?>
				<input type="hidden" name="action" value="semrs_us_invoice"><input type="hidden" name="semrs_do" value="create">
				<table class="form-table" role="presentation">
					<tr><th><label for="inv-name"><?php esc_html_e( 'Customer name', 'semrs-update-server' ); ?></label></th><td><input id="inv-name" name="name" class="regular-text" value="<?php echo esc_attr( $pk['name'] ?? '' ); ?>"></td></tr>
					<tr><th><label for="inv-email"><?php esc_html_e( 'Email', 'semrs-update-server' ); ?></label></th><td><input id="inv-email" type="email" name="email" class="regular-text" required value="<?php echo esc_attr( $pk['email'] ?? '' ); ?>"></td></tr>
					<tr><th><label for="inv-address"><?php esc_html_e( 'Billing address / company / tax ID', 'semrs-update-server' ); ?></label></th><td><textarea id="inv-address" name="address" rows="3" class="large-text"></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Plans', 'semrs-update-server' ); ?></th><td>
						<?php foreach ( semrs_us_plans() as $id => $p ) : ?>
							<label style="display:inline-block;min-width:330px;margin:2px 0"><input type="checkbox" name="plans[]" value="<?php echo esc_attr( $id ); ?>"> <?php echo esc_html( semrs_us_plan_label( $p ) . ' — ' . semrs_us_money( $p['price'] ) ); ?></label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Several add-ons on one invoice go on one key. The key gets the largest site limit of the plans picked.', 'semrs-update-server' ); ?></p>
					</td></tr>
					<tr><th><label for="inv-discount"><?php esc_html_e( 'Discount %', 'semrs-update-server' ); ?></label></th><td><input id="inv-discount" type="number" name="discount" min="0" max="100" step="0.01" value="0" style="width:90px"></td></tr>
					<tr><th><label for="inv-due"><?php esc_html_e( 'Due in (days)', 'semrs-update-server' ); ?></label></th><td><input id="inv-due" type="number" name="due" min="0" value="7" style="width:90px"></td></tr>
					<tr><th><label for="inv-key"><?php esc_html_e( 'Existing key (renewal or add-on)', 'semrs-update-server' ); ?></label></th><td><input id="inv-key" name="key" class="regular-text code" value="<?php echo esc_attr( $prefill ); ?>" placeholder="XXXX-XXXX-XXXX-XXXX"><p class="description"><?php esc_html_e( 'Leave empty to create a new key when paid. With a key, its expiry moves one year past the current date or expiry, whichever is later.', 'semrs-update-server' ); ?></p></td></tr>
					<tr><th><label for="inv-notes"><?php esc_html_e( 'Notes on the invoice', 'semrs-update-server' ); ?></label></th><td><textarea id="inv-notes" name="notes" rows="2" class="large-text"></textarea></td></tr>
					<tr><th></th><td><label><input type="checkbox" name="send" value="1" checked> <?php esc_html_e( 'Email the invoice to the customer now', 'semrs-update-server' ); ?></label></td></tr>
				</table>
				<?php submit_button( __( 'Create invoice', 'semrs-update-server' ) ); ?>
			</form>
		</details>
		<table class="widefat striped" style="max-width:1100px"><thead><tr><th><?php esc_html_e( 'Invoice', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Customer', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Items', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Total', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th><th></th></tr></thead><tbody>
		<?php if ( ! semrs_us_invoices() ) : ?><tr><td colspan="6"><?php esc_html_e( 'No invoices yet.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( semrs_us_invoices() as $num => $inv ) : ?>
			<tr>
				<td><a href="<?php echo esc_url( semrs_us_invoice_url( $inv ) ); ?>" target="_blank" rel="noopener"><strong><?php echo esc_html( $num ); ?></strong></a><br><small><?php echo esc_html( $inv['date'] . ' · ' . __( 'due', 'semrs-update-server' ) . ' ' . $inv['due'] ); ?></small></td>
				<td><?php echo esc_html( $inv['name'] ); ?><br><small><?php echo esc_html( $inv['email'] ); ?></small></td>
				<td><small><?php echo esc_html( implode( ' + ', wp_list_pluck( $inv['items'], 'label' ) ) ); ?></small></td>
				<td><?php echo esc_html( semrs_us_money( $inv['total'] ) ); ?><?php echo $inv['discount'] ? '<br><small>−' . esc_html( $inv['discount'] ) . '%</small>' : ''; ?></td>
				<td><?php echo esc_html( $inv['status'] ); ?><?php echo 'paid' === $inv['status'] ? '<br><small>' . esc_html( $inv['paid'] . ' · ' . substr( $inv['key'], 0, 4 ) . '…' . substr( $inv['key'], -4 ) ) . '</small>' : ( $inv['sent'] ? '<br><small>' . esc_html__( 'sent', 'semrs-update-server' ) . ' ' . esc_html( $inv['sent'] ) . '</small>' : '' ); ?></td>
				<td>
					<?php
					if ( 'unpaid' === $inv['status'] ) {
						echo $form( 'paid', $num, __( 'Mark paid', 'semrs-update-server' ), 'button button-small button-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in $form.
						echo $form( 'send', $num, $inv['sent'] ? __( 'Send again', 'semrs-update-server' ) : __( 'Send', 'semrs-update-server' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo $form( 'void', $num, __( 'Void', 'semrs-update-server' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}

/**
 * Public, printable invoice page: /?semrs_invoice=NUMBER&t=TOKEN
 */
function semrs_us_invoice_view() {
	if ( empty( $_GET['semrs_invoice'] ) || empty( $_GET['t'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$num = sanitize_text_field( wp_unslash( $_GET['semrs_invoice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$inv = semrs_us_invoices()[ $num ] ?? null;
	if ( ! $inv || ! hash_equals( $inv['token'], sanitize_text_field( wp_unslash( $_GET['t'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	$s      = semrs_us_sales();
	$status = array(
		'unpaid' => __( 'Unpaid', 'semrs-update-server' ),
		'paid'   => __( 'Paid', 'semrs-update-server' ),
		'void'   => __( 'Void', 'semrs-update-server' ),
	);
	?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( __( 'Invoice', 'semrs-update-server' ) . ' ' . $inv['number'] ); ?></title>
<style>
:root{--ink:#0f172a;--muted:#5b6575;--line:#e3e7ee;--bg:#f5f7fb;--card:#fff;--accent:#1f4fd1}
@media (prefers-color-scheme:dark){:root{--ink:#e8edf5;--muted:#a7b1c2;--line:#2a3550;--bg:#0b1220;--card:#121b2e;--accent:#8fb0ff}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.inv{max-width:820px;margin:24px auto;padding:32px 24px;background:var(--card);border:1px solid var(--line);border-radius:14px}
.top{display:flex;flex-wrap:wrap;justify-content:space-between;gap:16px}h1{margin:0;font-size:1.6rem}.muted{color:var(--muted)}
.tag{display:inline-block;padding:3px 12px;border-radius:999px;font-weight:700;font-size:.85rem;border:2px solid currentColor}.tag.paid{color:#0f7b4d}.tag.unpaid{color:#b45309}.tag.void{color:var(--muted)}
table{width:100%;border-collapse:collapse;margin:24px 0}th,td{text-align:left;padding:10px 6px;border-bottom:1px solid var(--line)}td:last-child,th:last-child{text-align:right;white-space:nowrap}
.total td{font-weight:700;font-size:1.1rem;border-bottom:0}.box{background:var(--bg);border-radius:10px;padding:14px 16px;margin:16px 0;white-space:pre-line}
button{font:inherit;padding:10px 18px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}@media (prefers-color-scheme:dark){button{color:#0b1220}}
@media print{body{background:#fff;color:#000}.inv{border:0;margin:0}.noprint{display:none}}
</style></head><body>
<main class="inv">
	<div class="top">
		<div><h1><?php esc_html_e( 'Invoice', 'semrs-update-server' ); ?> <?php echo esc_html( $inv['number'] ); ?></h1>
		<p class="muted"><?php echo esc_html( __( 'Date', 'semrs-update-server' ) . ': ' . $inv['date'] . ' · ' . __( 'Due', 'semrs-update-server' ) . ': ' . $inv['due'] ); ?></p></div>
		<div><span class="tag <?php echo esc_attr( $inv['status'] ); ?>"><?php echo esc_html( $status[ $inv['status'] ] ?? $inv['status'] ); ?></span></div>
	</div>
	<div class="top">
		<div><strong><?php echo esc_html( $s['seller_name'] ); ?></strong><div class="muted" style="white-space:pre-line"><?php echo esc_html( trim( $s['seller_address'] . "\n" . $s['seller_email'] . ( $s['seller_tax'] ? "\n" . $s['seller_tax'] : '' ) ) ); ?></div></div>
		<div><strong><?php esc_html_e( 'Bill to', 'semrs-update-server' ); ?></strong><div class="muted" style="white-space:pre-line"><?php echo esc_html( trim( $inv['name'] . "\n" . $inv['email'] . "\n" . $inv['address'] ) ); ?></div></div>
	</div>
	<table>
		<thead><tr><th><?php esc_html_e( 'Item', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Amount', 'semrs-update-server' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $inv['items'] as $it ) : ?>
			<tr><td><?php echo esc_html( 'SEMRS Pro: ' . $it['label'] ); ?></td><td><?php echo esc_html( semrs_us_money( $it['price'] ) ); ?></td></tr>
		<?php endforeach; ?>
		<?php if ( $inv['discount'] ) : ?>
			<tr><td><?php echo esc_html( sprintf( /* translators: %s: percent */ __( 'Discount (%s%%)', 'semrs-update-server' ), $inv['discount'] ) ); ?></td><td>−<?php echo esc_html( semrs_us_money( $inv['subtotal'] - $inv['total'] ) ); ?></td></tr>
		<?php endif; ?>
			<tr class="total"><td><?php esc_html_e( 'Total', 'semrs-update-server' ); ?> (<?php echo esc_html( $inv['currency'] ); ?>)</td><td><?php echo esc_html( semrs_us_money( $inv['total'] ) ); ?></td></tr>
		</tbody>
	</table>
	<?php if ( 'paid' === $inv['status'] ) : ?>
		<p class="box"><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Paid on %s. Thank you! Your license key was emailed to you.', 'semrs-update-server' ), $inv['paid'] ) ); ?></p>
	<?php elseif ( 'unpaid' === $inv['status'] && $s['pay_instructions'] ) : ?>
		<h2 style="font-size:1.1rem"><?php esc_html_e( 'How to pay', 'semrs-update-server' ); ?></h2>
		<div class="box"><?php echo esc_html( $s['pay_instructions'] ); ?>

<?php echo esc_html( sprintf( /* translators: %s: invoice number */ __( 'Please put %s in the payment reference.', 'semrs-update-server' ), $inv['number'] ) ); ?></div>
	<?php endif; ?>
	<?php if ( $inv['notes'] ) : ?><p class="muted" style="white-space:pre-line"><?php echo esc_html( $inv['notes'] ); ?></p><?php endif; ?>
	<p class="noprint"><button type="button" onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'semrs-update-server' ); ?></button></p>
</main>
</body></html>
	<?php
	exit;
}
add_action( 'template_redirect', 'semrs_us_invoice_view', 0 );
