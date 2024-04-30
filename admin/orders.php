<?php
require_once("header.php");

/**
 * Format a number to look like a currency
 * @param  float $number The number
 * @return strng         The formatted string
 */
function cnumber_format($number) {
	global $imSettings;
	return number_format($number, 2) . $imSettings['ecommerce']['database']['currency'];
}

?>
<div id="imAdminPage" style="width: 80%;">
	<div id="imBody">
		<div class="imContent">
<?php
if (isset($imSettings['ecommerce']) && isset($imSettings['ecommerce']['database'])) {
	$dbconf = getDbData($imSettings['ecommerce']['database']['id']);
	$prefix = $imSettings['ecommerce']['database']['table'];
	$pagination_length = 15;
	$pagination_start = (isset($_GET['page']) ? $_GET['page'] * $pagination_length : 0);
	$ecommerce = new ImCart();
	if ($ecommerce->setDatabaseConnection($dbconf['host'], $dbconf['user'], $dbconf['password'], $dbconf['database'], $prefix)) {
		if (isset($_GET['delete'])) {
			$ecommerce->deleteOrderFromDb($_GET['delete']);
		}
		if (!isset($_GET['id'])) {

			/*
			|-----------------------------
			|	Show the summary table
			|-----------------------------
			 */
			
			$result = $ecommerce->getOrders($pagination_start, $pagination_length, @$_GET['search']);
?>
			<form action="orders.php" method="get" style="margin: 5px; float: right;">
				<input name="search" id="search" type="text" style="padding: 5px;" value="<?php echo @$_GET['search'] ?>"/>
				<input type="submit" value="<?php echo l10n('search_search') ?>">
			</form>
			<table class="border">
				<thead>
					<tr>
						<th style="width: 15%;"><?php echo l10n('cart_order_no') ?></th>
						<th><?php echo l10n('cart_date', 'Date') ?></th>
						<th><?php echo l10n('cart_total') ?></th>
						<th><?php echo l10n('cart_payment') ?></th>
						<th><?php echo l10n('cart_shipping') ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
<?php // Empty search! ?>
<?php if (isset($_GET['search']) && !count($result['orders'])): ?>
					<tr>
						<td colspan="6" style="text-align: center;"><?php echo l10n('search_empty', 'Empty results') ?></td>
					</tr>
<?php else:?>
<?php // Orders ?>
<?php foreach ($result['orders'] as $order): ?>
					<tr>
						<td style="text-align: center;"><a href="orders.php?id=<?php echo $order['id'] ?>"><?php echo $order['id'] ?></a></td>
						<td tyle="text-align: center;"><?php echo $order['ts'] ?></td>
						<td style="text-align: center;"><?php echo cnumber_format($order['vat_type'] != "excluded" ? $order['price_plus_vat'] : $order['price']) ?></td>
						<td><?php echo $order['payment_name'] . ($order['payment_price'] > 0 ? " (" . cnumber_format($order['vat_type'] != "excluded" ? $order['payment_price_plus_vat'] : $order['payment_price']) . ")" : "") ?></td>
						<td><?php echo $order['shipping_name'] . ($order['shipping_price'] > 0 ? " (" . cnumber_format($order['vat_type'] != "excluded" ? $order['shipping_price_plus_vat'] : $order['shipping_price']) . ")" : "") ?></td>
						<td><a href="?delete=<?php echo $order['id']?>" onclick="return confirm('<?php echo str_replace("'", "\\'", l10n('cart_delete_order_q', 'Are you sure?')) ?>')"><?php echo l10n('cart_delete_order', 'Delete') ?></a></td>
					</tr>
<?php endforeach; ?>
<?php endif; ?>
				</tbody>
			</table>
<?php // PAGINATION ?>
<?php if ($result['paginationCount'] > $pagination_length): ?>
			<div style="text-align: center; margin: 5px;">
	<?php if (@$_GET['page'] != 0): ?>
				<a href="orders.php?page=0&amp;search=<?php echo @$_GET['search'] ?>">&lt;&lt;</a>
	<?php endif; ?>
	<?php if (@$_GET['page'] - 2 >= 0): ?>
				<a href="orders.php?page=<?php echo @$_GET['page'] - 2 ?>&amp;search=<?php echo @$_GET['search'] ?>">&lt;</a>
	<?php endif; ?>
	<?php for ($i = max(@$_GET['page'] - 3, 0); $i < min($ordersCount/$pagination_length, max(@$_GET['page'] - 3, 0) + 6); $i++): ?>
				<a href="orders.php?page=<?php echo $i ?>&amp;search=<?php echo @$_GET['search'] ?>"><?php echo $i + 1?></a>
	<?php endfor; ?>
	<?php if (@$_GET['page'] + 1 <= $ordersCount/$pagination_length - 2): ?>
				<a href="orders.php?page=<?php echo @$_GET['page'] + 1 ?>&amp;search=<?php echo @$_GET['search'] ?>">&gt;</a>
	<?php endif; ?>
	<?php if (@$_GET['page'] != $ordersCount/$pagination_length - 1): ?>
				<a href="orders.php?page=<?php echo $ordersCount/$pagination_length - 1?>&amp;search=<?php echo @$_GET['search'] ?>">&gt;&gt;</a>
			</div>
	<?php endif; ?>
<?php endif; ?>
<?php
		} else {

			/*
			|-----------------------------
			|	Show the order page
			|-----------------------------
			 */
			
			$orderArray = $ecommerce->getOrder($_GET['id']);
			if (count($orderArray)) {
				$order = $orderArray['order'];
?>
			<h1><?php echo l10n('cart_order_no') . ": " . $order['id'] ?></h1>
			<div class="personal-data" style="float: left;">
				<table style="width: 100%;">
					<thead>
						<tr>
							<th colspan="2">
								<?php
									if (count($orderArray['shipping']) === 0) {
										echo l10n('cart_vat_address') . "/" . l10n('cart_shipping_address');
									} else {
										echo l10n('cart_vat_address');
									}
								?>
							</th>
						</tr>
					</thead>
					<tbody>
<?php foreach ($orderArray['invoice'] as $line): ?>
						<tr>
							<td style="font-weight: bold;"><?php echo $line['label'] . ":" ?></td>
							<td><?php echo $line['value'] ?></td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
			</div>
<?php if (count($orderArray['shipping']) > 0): ?>
			<div class="personal-data" style="float: right;">
				<table style="width: 100%;">
					<thead>
						<tr>
							<th colspan="2">
								<?php echo l10n('cart_shipping_address') ?>	
							</th>
						</tr>
					</thead>
					<tbody>
<?php foreach ($orderArray['shipping'] as $line): ?>
						<tr>
							<td style="font-weight: bold;"><?php echo $line['label'] . ":" ?></td>
							<td><?php echo $line['value'] ?></td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
			</div>
<?php endif; // End shipping table ?>
			<div style="clear:both;"></div>
			<h2 style="margin-bottom: 5px; margin-top: 15px;"><?php echo l10n('cart_product_list') ?></h2>
			<div style="margin: 0 5px 15px 5px;">
				<table class="border">
					<thead>
						<tr>
							<th><?php echo l10n('cart_descr') ?></th>
							<th><?php echo l10n('cart_price') ?></th>
							<th><?php echo l10n('cart_qty') ?></th>
							<?php if ($order['vat_type'] != "none"): ?>
							<th><?php echo l10n($order['vat_type'] == "excluded" ? 'cart_vat' : 'cart_vat_included') ?></th>
							<?php endif; ?>
							<th><?php echo l10n('cart_subtot') ?></th>
						</tr>
					</thead>
					<tbody>
<?php foreach ($orderArray['products'] as $product): ?>
						<tr>
							<td>
								<?php echo $product['name'] . ($product['option'] != "" ? " - " . $product['option'] . ($product['suboption'] != "" ? " - " . $product['suboption'] : "") : "") ?>
							</td>
							<td class="align-right"><?php echo cnumber_format($product['price'] / $product['quantity']) ?></td>
							<td class="align-right"><?php echo $product['quantity'] ?></td>
							<?php if ($order['vat_type'] != "none"): ?>
							<td class="align-right"><?php echo cnumber_format($product['vat']) ?></td>
							<?php endif; ?>
							<td class="align-right"><?php echo cnumber_format($order['vat_type'] == "excluded" ? $product['price'] : $product['price_plus_vat']) ?></td>
						</tr>
<?php endforeach; ?>
						<tr>
							<td colspan="3" style="border: none;"></td>
						</tr>
						<!-- Shipping data -->
						<?php if ($order['shipping_name'] != "" || $order['shipping_price'] != 0): ?>
						<tr class="head">
							<th colspan="3"><?php echo l10n('cart_shipping') ?></th>
							<?php if ($order['vat_type'] != "none"): ?>
							<th><?php echo l10n($order['vat_type'] == "excluded" ? 'cart_vat' : 'cart_vat_included') ?></th>
							<?php endif; ?>
							<th><?php echo l10n('cart_price') ?></th>
						</tr>
						<tr>
							<td colspan="3"><?php echo $order['shipping_name'] ?></td>
							<?php if ($order['vat_type'] != "none"): ?>
							<td class="align-right"><?php echo cnumber_format($order['shipping_vat']) ?></td>
							<?php endif; ?>
							<td class="align-right"><?php echo cnumber_format($order['vat_type'] == "excluded" ? $order['shipping_price'] : $order['shipping_price_plus_vat']) ?></td>
						</tr>
						<tr>
							<td colspan="3" style="border: none;"></td>
						</tr>
						<?php endif; ?>						
						<!-- Payment data -->
						<?php if ($order['payment_name'] != "" || $order['payment_price'] != 0): ?>
						<tr class="head">
							<th colspan="3"><?php echo l10n('cart_payment') ?></th>
							<?php if ($order['vat_type'] != "none"): ?>
							<th><?php echo l10n($order['vat_type'] == "excluded" ? 'cart_vat' : 'cart_vat_included') ?></th>
							<?php endif; ?>
							<th><?php echo l10n('cart_price') ?></th>
						</tr>
						<tr>
							<td colspan="3"><?php echo $order['payment_name'] ?></td>
							<?php if ($order['vat_type'] != "none"): ?>
							<td class="align-right"><?php echo cnumber_format($order['payment_vat']) ?></td>
							<?php endif; ?>
							<td class="align-right"><?php echo cnumber_format($order['vat_type'] == "excluded" ? $order['payment_price'] : $order['payment_price_plus_vat']) ?></td>
						</tr>
						<tr>
							<td colspan="3" style="border: none;"></td>
						</tr>
						<?php endif; ?>
						<!-- Coupon Code -->
						<?php if (isset($order['coupon']) && $order['coupon'] != ""): ?>
						<tr>
							<th colspan="3" style="border-left: none; border-bottom: none; border-top: none;"></th>
							<th class="head"><?php echo l10n('cart_coupon', "Coupon Code") ?></th>
							<td class="align-right"><?php echo $order['coupon'] ?></td>
						</tr>
						<?php endif; ?>
						<!-- Total Amounts -->
						<?php switch($order['vat_type']) {
							case "included": ?>
						<tr>
							<th colspan="3" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_total_vat') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['price_plus_vat']) ?></td>
						</tr>
						<tr>
							<th colspan="3" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_vat_included') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['vat']) ?></td>
						</tr>
						<?php
							break;
							case "excluded": ?>
						<tr>
							<th colspan="3" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_total') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['price']) ?></td>
						</tr>
						<tr>
							<th colspan="3" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_vat') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['vat']) ?></td>
						</tr>
						<tr>
							<th colspan="3" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_total_vat') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['price_plus_vat']) ?></td>
						</tr>
						<?php
							break;
							case "none":?>
						<tr>
							<th colspan="2" style="border: none;"></th>
							<th class="head"><?php echo l10n('cart_total') ?></th>
							<td class="align-right"><?php echo cnumber_format($order['price_plus_vat']) ?></td>
						</tr>
						<? break; ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<div style="padding: 5px; text-align: center;">
				<input type="button" onclick="location.href='<?php echo strpos($_SERVER['HTTP_REFERER'], basename($_SERVER['PHP_SELF'])) ? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'] ?>';" value="<?php echo l10n('cart_goback', "Back") ?>" />
			</div>
<?php
			}
		}
	}
}
?>
		</div>
	</div>
</div>
<?php require_once("footer.php"); ?>
