
<div id="logonStatus">
	<p>
		<?php 
   if ($_SESSION['userdata']['login'] != '') {
       if (isset($_SESSION['userdata']['can_checkout']) and
           $_SESSION['userdata']['can_checkout']) {
           echo '<font color="red">' . $Text['nav_can_checkout'] . '</font> ';
       }
     echo $Text['nav_signedIn'] . " " . $_SESSION['userdata']['login'] . " | "
       . $Text['uf_long'] . ' ' . $_SESSION['userdata']['uf_id'] . " | " 
       . $_SESSION['userdata']['provider_id'];
     echo '<select size="0" name="role_select" id="role_select">';
     foreach ($_SESSION['userdata']['roles'] as $role) {
       echo '<option';
       $rt = (isset($Text[$role]) ? $Text[$role] : "TRANSLATE[$role]");
       if ($role == $_SESSION['userdata']['current_role'])
	 echo ' selected';
       echo ' value="' . $role. '">' . $rt . '</option>'; 
     } 
     echo '</select> ';
     echo '<select size="0" name="lang_select" id="lang_select">';
       $keys = $_SESSION['userdata']['language_keys'];
       $names = $_SESSION['userdata']['language_names'];
       for ($i=0; $i < count($keys); $i++) {
           echo '<option';
           if ($keys[$i] == $_SESSION['userdata']['language'])
               echo ' selected';
           echo ' value="' . $keys[$i]. '">' . $names[$i] . '</option>'; 
       } 
     echo '</select> ';
     echo " | <a href='ctrlLogin.php?oper=logout'>".$Text['nav_logout']."</a>";
       /*
       if (!isset($_SESSION['userdata']['can_checkout']) or 
           !$_SESSION['userdata']['can_checkout']) {
           echo " | <a href='ctrlCookie.php?oper=try_to_checkout'>"
               . $Text['nav_try_to_checkout'] . "</a>";
       } else {
           echo " | <a href='ctrlCookie.php?oper=stop_checkout'>"
               . $Text['nav_stop_checkout'] . "</a>";
       }
       */
   } else {
     echo ("userdata not set");
     header('Location:login.php');
   }

?>
	</p>
</div>


<div class="ui-widget-header ui-corner-all" id="menuBgBar">
<div  id="topMenu">
<a tabindex="0" href="index.php" 	id="navHome" class="menuTop"><?php echo $Text['nav_home'];?></a>
<a tabindex="1" href="#" 			id="navWizard" class="menuTop"><?php echo $Text['nav_wiz'];?></a>
<a tabindex="2" href="shop_and_order.php?what=Shop" 	id="navShop" class="menuTop"><?php echo $Text['nav_shop'];?></a>
<a tabindex="3" href="shop_and_order.php?what=Order" 		id="navOrder" class="menuTop"><?php echo $Text['nav_order'];?></a>
<a tabindex="4" href="#" 			id="navManage" class="menuTop"><?php echo $Text['nav_mng'];?></a>
<a tabindex="5" href="#" id="navReport" class="menuTop"><?php echo $Text['nav_report'];?></a>
<a tabindex="6" href="#" id="navIncidents" class="menuTop"><?php echo $Text['nav_incidents'];?></a>
<a tabindex="7" href="#" id="navMyAccount" class="menuTop"><?php echo $Text['nav_myaccount'];?></a>
</div>
</div>


<div id="navWizardItems" class="hidden">
	<ul>
		<li><a href="arrived_products.php"><?php echo $Text['nav_wiz_arrived'];?></a></li>
		<li><a href="validate.php"><?php echo $Text['nav_wiz_validate'];?></a></li>
		<li><a href="report_order.php?what=Report"><?php echo $Text['nav_report_order'];?></a></li>
		<li><a href="report_torn.php"><?php echo $Text['nav_wiz_torn'];?></a></li>
		<li><a href="all_prevorders.php"><?php echo $Text['nav_prev_orders'];?></a></li>
		<li><a href="manage_cashbox.php"><?php echo $Text['nav_wiz_cashbox'];?></a></li>
		
		<!-- li><a href="#"><?php echo $Text['nav_wiz_open'] . ' ' . $Text['coop_name'];?></a></li>
		<li><a href="#"><?php echo $Text['nav_wiz_close'] . ' ' . $Text['coop_name'];?></a></li-->
	</ul>
</div>
<div id="navManageItems" class="hidden">
	<ul>
		<li><a href="manage_ufs.php"><?php echo $Text['nav_mng_uf'];?></a></li>
		<li><a href="manage_user.php"><?php echo $Text['nav_mng_member'];?></a>
			<ul>
			<li>
            <?php 
            	if($_SESSION['userdata']['current_role'] == 'Hacker Commission') {
     				echo '<a href="activate_all_roles.php">';
 				} else {
     				echo '<a href="activate_roles.php">';
 				}  
 				echo $Text['nav_mng_roles'];?>
 		</a></li>
			</ul>
		</li>
		
		<li><a href="manage_table.php?table=aixada_provider"><?php echo $Text['nav_mng_providers'];?></a></li>
		<li><a href="manage_table.php?table=aixada_product"><?php echo $Text['nav_mng_products'];?></a>
			<ul>
				<li><a href="manage_orderable_products.php"><?php echo $Text['nav_mng_deactivate'];?></a></li>
				<li><a href="manage_table.php?table=aixada_unit_measure"><?php echo $Text['nav_mng_units'];?></a></li>
			</ul>
		</li>
		<li><a href="#"><?php echo $Text['nav_mng_orders'];?></a>
			<ul>
				<!--  li><a href="manage_dates.php?what=Setdates"><?php echo $Text['nav_mng_setorderable']; ?></a></li-->
				<!--  li><a href="manage_dates.php?what=Move"><?php echo $Text['nav_mng_move'];?></a></li-->
				<li><a href="manage_preorders.php"><?php echo $Text['nav_mng_preorder'];?></a></li>
			</ul>
		</li>
		<li><a href="manage_db.php"><?php echo $Text['nav_mng_db'];?></a></li>
		
	</ul>
</div>

<div id="navReportItems" class="hidden">
	<ul>
		<li><a href="report_order.php?what=Report"><?php echo $Text['nav_report_order'];?></a></li>
		<li><a href="report_account.php"><?php echo $Text['nav_report_account'];?></a></li>
		<li><a href="report_stats.php"><?php echo $Text['nav_report_daystats'];?></a></li>
		<li><a href="#"><?php echo $Text['nav_report_timelines'];?></a>
                <ul>
                 <li><a href="report_timelines.php?oper=uf"><?php echo $Text['nav_report_timelines_uf'];?></a></li>
                 <li><a href="report_timelines.php?oper=provider"><?php echo $Text['nav_report_timelines_provider'];?></a></li>
                 <li><a href="report_timelines.php?oper=product"><?php echo $Text['nav_report_timelines_product'];?></a></li>
                </ul>
                </li>
		<li><a href="report_order.php?what=Preorder"><?php echo $Text['nav_report_preorder'];?></a></li>
		<li><a href="report_incidents.php"><?php echo $Text['nav_report_incidents'];?></a></li>
	</ul>
</div>

<div id="navIncidentsItems" class="hidden">
	<ul>
		<li><a href="incidents.php"><?php echo $Text['nav_browse'];?></a></li>
	</ul>
</div>

<div id="navMyAcountItems" class="hidden">
	<ul>
		<li><a href="manage_mysettings.php"><?php echo $Text['nav_myaccount_settings'];?></a></li>
		<li><a href="manage_mysettings.php?what=pwd"><?php echo $Text['nav_changepwd'];?></a></li>
		<li><a href="report_account.php?what=my_account&uf_id=<? echo $_SESSION['userdata']['uf_id']; ?>"><?php echo $Text['nav_myaccount_account'];?></a></li>		
		<!-- li><a href="my_prevorders.php"><?php echo $Text['nav_prev_orders'];?></a></li-->
	</ul>
</div>
