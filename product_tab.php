<?php
/* Copyright (C) 2021      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * \file        htdocs/extendedaccountancy/product_tab.php
 * \ingroup     extendedaccountancy
 * \brief       Tab to manage accountancy
 */
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
if (!empty($conf->accounting->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
    require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', "compta", "accountancy", "extendedaccountancy@extendedaccountancy"));

$id = GETPOST('id', 'int');
$ref = (GETPOSTISSET('ref') ? GETPOST('ref', 'alpha') : null);
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$limit = GETPOSTISSET('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == - 1) {
	$page = 0;
} // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") {
	$sortorder = "ASC";
}
if ($sortfield == "") {
	$sortfield = "eappe.fk_c_type_transaction";
}

$object = new Product($db);
$object->id = $id;
$result = $object->fetch($id);

// Security check
if (!empty($user->socid)) {
    $socid = $user->socid;
}
if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

if (empty($conf->accounting->enabled)) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (empty($user->rights->extendedaccountancy->read)) {
	accessforbidden();
}


/*
 * Action
 */



/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $object->label." - ".$langs->trans('Accountancy');
$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
llxHeader('', $title, $help_url);

$head = product_prepare_head($object);

dol_htmloutput_mesg(is_numeric($error) ? '' : $error, $errors, 'error');

print dol_get_fiche_head($head, 'extendedaccountancy', $langs->trans("Accountancy"), 0, 'product');

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'rowid', '', '', '', 0, '', '', 'arearefnobottom');

print dol_get_fiche_end();

$sql = "SELECT eappe.rowid, eappe.fk_product, eappe.fk_c_type_transaction, ";
$sql .= " eappe.accountancy_code_sell, eappe.accountancy_code_sell_intra, eappe.accountancy_code_sell_export,";
$sql .= " eappe.accountancy_code_buy, eappe.accountancy_code_buy_intra, eappe.accountancy_code_buy_export,";
$sql .= " tp.label as tp_label";
$sql .= " FROM ".MAIN_DB_PREFIX."extendedaccountancy_product_perentity as eappe";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on (p.rowid = eappe.fk_product)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_transaction as tp on (tp.rowid = eappe.fk_c_type_transaction)";
$sql .= " WHERE eappe.entity = ".$conf->entity;
$sql .= $db->order($sortfield, $sortorder);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}
$nbtotalofrecords = $db->num_rows($resql);

while ($obj = $db->fetch_object($resql)) {
	$accountancy_code_sell = $obj->accountancy_code_sell;
	$accountancy_code_sell_intra = $obj->accountancy_code_sell_intra;
	$accountancy_code_sell_export = $obj->accountancy_code_sell_export;
    $accountancy_code_buy = $obj->accountancy_code_buy;
	$accountancy_code_buy_intra = $obj->accountancy_code_buy_intra;
	$accountancy_code_buy_export = $obj->accountancy_code_buy_export;
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/extendedaccountancy/product_tab.php", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}

$param = '';
$param .= "&id=".urlencode($id);

$num = $db->num_rows($resql);

if ($resql) {
	$i = 0;

	$param = "&id=".$id;
	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="liste centpercent">'."\n";

	print '<tr class="liste_titre">';
	//print_liste_field_titre("Doctype", $_SERVER["PHP_SELF"], "bk.doc_type", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("TypeTransaction", $_SERVER["PHP_SELF"], "bk.doc_date", "", $param, "", $sortfield, $sortorder, 'center ');
	print_liste_field_titre("ProductAccountancySellCode", $_SERVER["PHP_SELF"], "bk.doc_ref", "", $param, "", $sortfield, $sortorder);
    if ($mysoc->isInEEC()) print_liste_field_titre("ProductAccountancySellIntraCode", $_SERVER["PHP_SELF"], "bk.label_compte", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("ProductAccountancySellExportCode", $_SERVER["PHP_SELF"], "bk.debit", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("ProductAccountancyBuyCode", $_SERVER["PHP_SELF"], "bk.credit", "", $param, "", $sortfield, $sortorder);
    if ($mysoc->isInEEC()) print_liste_field_titre("ProductAccountancyBuyIntraCode", $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("ProductAccountancyBuyExportCode", $_SERVER["PHP_SELF"], "bk.code_journal", "", $param, "", $sortfield, $sortorder, 'center ');
	print "</tr>\n";

	$solde = 0;
	$tmp = '';

	while ($obj = $db->fetch_object($resql)) {
		print '<tr class="oddeven">';

		//print '<td>' . $obj->doc_type . '</td>' . "\n";
		print '<td class="center">'.$obj->tp_label.'</td>';

		print '<td class="nowrap right">';
        if (!empty($conf->accounting->enabled)) {
            if (!empty($obj->accountancy_code_sell)) {
                $accountingaccount = new AccountingAccount($db);
                $accountingaccount->fetch('', $obj->accountancy_code_sell, 1);

                print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
            }
        } else {
            print $obj->accountancy_code_sell;
        }
        print '</td>';

        if ($mysoc->isInEEC()) {
            print '<td class="nowrap right">';
            if (!empty($conf->accounting->enabled)) {
                if (!empty($obj->accountancy_code_sell_intra)) {
                    $accountingaccount2 = new AccountingAccount($db);
                    $accountingaccount2->fetch('', $obj->accountancy_code_sell_intra, 1);

                    print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
                }
            } else {
                print $obj->accountancy_code_sell_intra;
            }
            print '</td>';
        }

        print '<td class="nowrap right">';
        if (!empty($conf->accounting->enabled)) {
            if (!empty($obj->accountancy_code_sell_export)) {
                $accountingaccount3 = new AccountingAccount($db);
                $accountingaccount3->fetch('', $obj->accountancy_code_sell_export, 1);

                print $accountingaccount3->getNomUrl(0, 1, 1, '', 1);
            }
        } else {
            print $obj->accountancy_code_sell_export;
        }
        print '</td>';

        print '<td class="nowrap right">';
        if (!empty($conf->accounting->enabled)) {
            if (!empty($obj->accountancy_code_buy)) {
                $accountingaccount4 = new AccountingAccount($db);
                $accountingaccount4->fetch('', $obj->accountancy_code_buy, 1);

                print $accountingaccount4->getNomUrl(0, 1, 1, '', 1);
            }
        } else {
            print $obj->accountancy_code_buy;
        }
        print '</td>';

        print '<td class="nowrap right">';
        if ($mysoc->isInEEC()) {
            if (!empty($conf->accounting->enabled)) {
                if (!empty($obj->accountancy_code_buy_intra)) {
                    $accountingaccount5 = new AccountingAccount($db);
                    $accountingaccount5->fetch('', $obj->accountancy_code_buy_intra, 1);

                    print $accountingaccount5->getNomUrl(0, 1, 1, '', 1);
                }
            } else {
                print $obj->accountancy_code_buy_intra;
            }
            print '</td>';
        }

        print '<td class="nowrap right">';
        if (!empty($conf->accounting->enabled)) {
            if (!empty($obj->accountancy_code_buy_export)) {
                $accountingaccount6 = new AccountingAccount($db);
                $accountingaccount6->fetch('', $obj->accountancy_code_buy_export, 1);

                print $accountingaccount6->getNomUrl(0, 1, 1, '', 1);
            }
        } else {
            print $obj->accountancy_code_buy_export;
        }
        print '</td>';

		print "</tr>\n";
	}

	print "</table>";

	print "</form>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
