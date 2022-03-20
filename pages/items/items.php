<?php
/** If the parameter 'isearch' is set, queries for the items matching the criterias and displays them, along with an item search form.
 *    If only one and only one item is found then this item is displayed.
 *  If 'isearch' is not set, displays a search item form.
 *  If no criteria is set then it is equivalent to searching for all items.
 *  For compatbility with Wikis and multi-word searches, underscores are treated as jokers in 'iname'.
 */

$isearch = (isset($_GET['isearch']) ? $_GET['isearch'] : '');
$iname = (isset($_GET['iname']) ? $_GET['iname'] : '');
$iclass = (isset($_GET['iclass']) ? addslashes($_GET['iclass']) : '');
$irace = (isset($_GET['irace']) ? addslashes($_GET['irace']) : '');
$islot = (isset($_GET['islot']) ? addslashes($_GET['islot']) : '');
$istat1 = (isset($_GET['istat1']) ? addslashes($_GET['istat1']) : '');
$istat1comp = (isset($_GET['istat1comp']) ? addslashes($_GET['istat1comp']) : '');
$istat1value = (isset($_GET['istat1value']) ? addslashes($_GET['istat1value']) : '');
$istat2 = (isset($_GET['istat2']) ? addslashes($_GET['istat2']) : '');
$istat2comp = (isset($_GET['istat2comp']) ? addslashes($_GET['istat2comp']) : '');
$istat2value = (isset($_GET['istat2value']) ? addslashes($_GET['istat2value']) : '');
$iresists = (isset($_GET['iresists']) ? addslashes($_GET['iresists']) : '');
$iresistscomp = (isset($_GET['iresistscomp']) ? addslashes($_GET['iresistscomp']) : '');
$iresistsvalue = (isset($_GET['iresistsvalue']) ? addslashes($_GET['iresistsvalue']) : '');
$iheroics = (isset($_GET['iheroics']) ? addslashes($_GET['iheroics']) : '');
$iheroicscomp = (isset($_GET['iheroicscomp']) ? addslashes($_GET['iheroicscomp']) : '');
$iheroicsvalue = (isset($_GET['iheroicsvalue']) ? addslashes($_GET['iheroicsvalue']) : '');
$imod = (isset($_GET['imod']) ? addslashes($_GET['imod']) : '');
$imodcomp = (isset($_GET['imodcomp']) ? addslashes($_GET['imodcomp']) : '');
$imodvalue = (isset($_GET['imodvalue']) ? addslashes($_GET['imodvalue']) : '');
$itype = (isset($_GET['itype']) ? addslashes($_GET['itype']) : -1);
$iaugslot = (isset($_GET['iaugslot']) ? addslashes($_GET['iaugslot']) : '');
$ieffect = (isset($_GET['ieffect']) ? addslashes($_GET['ieffect']) : '');
$ireqlevel = (isset($_GET['ireqlevel']) ? addslashes($_GET['ireqlevel']) : '');
$iminlevel = (isset($_GET['iminlevel']) ? addslashes($_GET['iminlevel']) : '');
$inodrop = (isset($_GET['inodrop']) ? addslashes($_GET['inodrop']) : '');
$iavailability = (isset($_GET['iavailability']) ? addslashes($_GET['iavailability']) : '');
$iavailevel = (isset($_GET['iavailevel']) ? addslashes($_GET['iavailevel']) : '');
$ideity = (isset($_GET['ideity']) ? addslashes($_GET['ideity']) : '');
$discovered  = (isset($_GET['idiscovered']) ? addslashes($_GET['idiscovered']) : '');

if (count($_GET) > 2) {
    $query = "
	SELECT DISTINCT 
		$items_table.id, 
		$items_table.icon,
		$items_table.Name,
		$items_table.itemtype,
		$items_table.ac, 
		$items_table.hp, 
		$items_table.damage, 
		$items_table.delay   
	FROM ($items_table";

    if ($discovered) {
        $query .= ",discovered_items";
    }

    if ($iavailability == 1) // mob dropped
    {
		$ignore_zone_str = get_ignore_zones_str();
        $query .= " 
			INNER JOIN $loot_drop_entries_table ON $loot_drop_entries_table.item_id=$items_table.id
			INNER JOIN $loot_table_entries ON $loot_table_entries.lootdrop_id = $loot_drop_entries_table.lootdrop_id
			INNER JOIN $npc_types_table ON $npc_types_table.loottable_id = $loot_table_entries.loottable_id
			INNER JOIN $spawn_entry_table ON $spawn_entry_table.npcID = $npc_types_table.id
			INNER JOIN $spawn2_table ON $spawn2_table.spawngroupID=$spawn_entry_table.spawngroupID
			INNER JOIN $zones_table ON $zones_table.short_name = $spawn2_table.zone
		";
        if ($iavaillevel > 0) {
            $query .= " AND $npc_types_table.level<=$iavaillevel";
        }
		$ignore_zones_str = get_ignore_zones_str();
		$query .= " AND $zones_table.short_name NOT IN $ignore_zones_str";
    }
    if ($iavailability == 2) // merchant sold
    {
		$query .= " INNER JOIN $merchant_list_table ON $merchant_list_table.item=$items_table.id";
    }
    $query .= ")";
    $s = " WHERE";
    if ($ieffect != "") {
        $effect = "%" . str_replace(',', '%', str_replace(' ', '%', addslashes($ieffect))) . "%";

        $query .= " LEFT JOIN $spells_table AS proc_s ON proceffect=proc_s.id";
        $query .= " LEFT JOIN $spells_table AS worn_s ON worneffect=worn_s.id";
        $query .= " LEFT JOIN $spells_table AS focus_s ON focuseffect=focus_s.id";
        $query .= " LEFT JOIN $spells_table AS click_s ON clickeffect=click_s.id";
        $query .= " WHERE (proc_s.`name` LIKE '$effect'
				OR worn_s.`name` LIKE '$effect'
				OR focus_s.`name` LIKE '$effect'
				OR click_s.`name` LIKE '$effect') ";
        $s = "AND";
    }
    if (($istat1 != "") AND ($istat1value != "")) {
        if ($istat1 == "ratio") {
            $query .= " $s ($items_table.delay/$items_table.damage $istat1comp $istat1value) AND ($items_table.damage>0)";
            $s = "AND";
        } else {
            $query .= " $s ($items_table.$istat1 $istat1comp $istat1value)";
            $s = "AND";
        }
    }
    if (($istat2 != "") AND ($istat2value != "")) {
        if ($istat2 == "ratio") {
            $query .= " $s ($items_table.delay/$items_table.damage $istat2comp $istat2value) AND ($items_table.damage>0)";
            $s = "AND";
        } else {
            $query .= " $s ($items_table.$istat2 $istat2comp $istat2value)";
            $s = "AND";
        }
    }
    if (($imod != "") AND ($imodvalue != "")) {
        $query .= " $s ($items_table.$imod $imodcomp $imodvalue)";
        $s = "AND";
    }
    if ($discovered) {
        $query .= " $s discovered_items.item_id=$items_table.id";
        $s = "AND";
    }
    if ($iname != "") {
        $name = addslashes(str_replace("_", "%", str_replace(" ", "%", $iname)));
        $query .= " $s ($items_table.Name like '%" . $name . "%')";
        $s = "AND";
    }
    if ($iclass > 0) {
        $query .= " $s ($items_table.classes & $iclass) ";
        $s = "AND";
    }
    if ($ideity > 0) {
        $query .= " $s ($items_table.deity   & $ideity) ";
        $s = "AND";
    }
    if ($irace > 0) {
        $query .= " $s ($items_table.races   & $irace) ";
        $s = "AND";
    }
    if ($itype >= 0) {
        $query .= " $s ($items_table.itemtype=$itype) ";
        $s = "AND";
    }
    if ($islot > 0) {
        $query .= " $s ($items_table.slots   & $islot) ";
        $s = "AND";
    }
    if ($iaugslot > 0) {
        $AugSlot = pow(2, $iaugslot) / 2;
        $query .= " $s ($items_table.augtype & $AugSlot) ";
        $s = "AND";
    }
    if ($iminlevel > 0) {
        $query .= " $s ($items_table.reqlevel>=$iminlevel) ";
        $s = "AND";
    }
    if ($ireqlevel > 0) {
        $query .= " $s ($items_table.reqlevel<=$ireqlevel) ";
        $s = "AND";
    }
    if ($inodrop) {
        $query .= " $s ($items_table.nodrop=1)";
        $s = "AND";
    }
    $query .= " ORDER BY $items_table.Name LIMIT 1000";
    $QueryResult = db_mysql_query($query);

    $field_values = '';
    foreach ($_GET as $key => $val){
        $field_values .= '$("#'. $key . '").val("'. $val . '");';
    }

    $footer_javascript .= '<script type="text/javascript">' . $field_values . '</script>';


} else {
    $iname = "";
}

$page_title = "Item Search";

$print_buffer .= '<table><tr><td>';

$print_buffer .= file_get_contents('pages/items/item_search_form.html');

if(!isset($_GET['v_ajax'])){
    $footer_javascript .= '
        <script src="pages/items/items.js"></script>
    ';
}

// Print the query results if any
if (isset($QueryResult)) {

    $Tableborder = 0;

    $num_rows = mysqli_num_rows($QueryResult);
    $print_buffer .= "";
    if ($num_rows == 0) {
        $print_buffer .= "<b>No items found...</b><br>";
    } else {
        $print_buffer .= "<table class='display_table container_div datatable' id='item_search_results' style='width:100%'>";
        $print_buffer .= "
            <thead>
                <th class='menuh'>Icon</th>
                <th class='menuh'>Item Name</th>
                <th class='menuh'>Item Type</th>
                <th class='menuh'>AC</th>
                <th class='menuh'>HPs</th>
                <th class='menuh'>Mana</th>
                <th class='menuh'>Damage</th>
                <th class='menuh'>Delay</th>
                <th class='menuh'>Item ID</th>
            </thead>
        ";
        $RowClass = "lr";
        for ($count = 1; $count <= $num_rows; $count++) {
            $TableData = "";
            $row = mysqli_fetch_array($QueryResult);
            $TableData .= "<tr valign='top' class='" . $RowClass . "'><td>";
            if (file_exists($icons_dir . "item_" . $row["icon"] . ".png")) {
                $TableData .= "<img src='" . $icons_url . "item_" . $row["icon"] . ".png' align='left'/>";
            } else {
                $TableData .= "<img src='" . $icons_url . "item_.gif' align='left'/>";
            }
            $TableData .= "</td><td>";
            $TableData .= "<a href='?a=item&id=" . $row["id"] . "' id='" . $row["id"] . "'>" . $row["Name"] . "</a>";
            $TableData .= "</td><td>";
            $TableData .= $dbitypes[$row["itemtype"]];
            $TableData .= "</td><td>";
            $TableData .= $row["ac"];
            $TableData .= "</td><td>";
            $TableData .= $row["hp"];
            $TableData .= "</td><td>";
            $TableData .= $row["mana"];
            $TableData .= "</td><td>";
            $TableData .= $row["damage"];
            $TableData .= "</td><td>";
            $TableData .= $row["delay"];
            $TableData .= "</td><td>";
            $TableData .= $row["id"];
            $TableData .= "</td></tr>";

            if ($RowClass == "lr") {
                $RowClass = "dr";
            } else {
                $RowClass = "lr";
            }

            $print_buffer .= $TableData;
        }
        $print_buffer .= "</table>";
    }

    $footer_javascript .= '
        <script>
            $(document).ready(function() {
                var table = $(".datatable").DataTable( {
                    "paging": true,
                    "searching": true,
                    "ordering": true
                } );
                table.order( [ 1, "asc" ] );
                table.draw();
            });
        </script>
    ';
}

$print_buffer .= '</td></tr></table>';


?>
