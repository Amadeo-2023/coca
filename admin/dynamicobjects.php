<?php
	require_once("header.php");
?>
<div id="imAdminPage">
	<div id="imBody">
		<div class="imContent">
		<h2><?php echo l10n("dynamicobj_list", "List of the Dynamic Objects in your Site") ?></h2>
<?php
		if (isset($imSettings['dynamicobjects'])) {
			foreach ($imSettings['dynamicobjects'] as $objId => $obj) {
				echo "<a href=\"../" . $obj['Page'] . "#" . $obj['ObjectId'] . "\" class=\"dynobj\">" . $obj['PageTitle'] . " - " . (strlen($obj['ObjectTitle']) ? $obj['ObjectTitle'] : $obj['ObjectId']) . "</a>";
			}
		}
?>
		<div style="clear: both;"></div>
		</div>
	</div>
</div>
<?php
require_once("footer.php");
?>
