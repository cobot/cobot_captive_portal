<?php
/*
	services_captiveportal.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
/*
	pfSense_MODULE:	captiveportal
*/

##|+PRIV
##|*IDENT=page-services-captiveportal
##|*NAME=Services: Captive portal page
##|*DESCR=Allow access to the 'Services: Captive portal' page.
##|*MATCH=services_captiveportal.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("captiveportal.inc");

$cpzone = $_GET['zone'];
if (isset($_POST['zone']))
	$cpzone = $_POST['zone'];

if (empty($cpzone) || empty($config['captiveportal'][$cpzone])) {
	header("Location: services_captiveportal_zones.php");
	exit;
}

if (!is_array($config['captiveportal']))
	$config['captiveportal'] = array();
$a_cp =& $config['captiveportal'];

$pgtitle = array(gettext("Services"),gettext("Captive portal"), $a_cp[$cpzone]['zone']);
$shortcut_section = "captiveportal";

if ($_GET['act'] == "viewhtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['htmltext'])
		echo base64_decode($a_cp[$cpzone]['page']['htmltext']);
	exit;
} else if ($_GET['act'] == "viewerrhtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['errtext'])
		echo base64_decode($a_cp[$cpzone]['page']['errtext']);
	exit;
} else if ($_GET['act'] == "viewlogouthtml") {
	if ($a_cp[$cpzone] && $a_cp[$cpzone]['page']['logouttext'])
		echo base64_decode($a_cp[$cpzone]['page']['logouttext']);
	exit;
}

if (!is_array($config['ca']))
	$config['ca'] = array();

$a_ca =& $config['ca'];

if (!is_array($config['cert']))
	$config['cert'] = array();

$a_cert =& $config['cert'];

if ($a_cp[$cpzone]) {
	$pconfig['zoneid'] = $a_cp[$cpzone]['zoneid'];
	$pconfig['cinterface'] = $a_cp[$cpzone]['interface'];
	$pconfig['webapi_space'] = $a_cp[$cpzone]['webapi_space'];
  $pconfig['webapi_token'] = $a_cp[$cpzone]['webapi_token'];
	$pconfig['maxproc'] = $a_cp[$cpzone]['maxproc'];
	$pconfig['maxprocperip'] = $a_cp[$cpzone]['maxprocperip'];
	$pconfig['timeout'] = $a_cp[$cpzone]['timeout'];
	$pconfig['idletimeout'] = $a_cp[$cpzone]['idletimeout'];
	$pconfig['freelogins_count'] = $a_cp[$cpzone]['freelogins_count'];
	$pconfig['freelogins_resettimeout'] = $a_cp[$cpzone]['freelogins_resettimeout'];
	$pconfig['freelogins_updatetimeouts'] = isset($a_cp[$cpzone]['freelogins_updatetimeouts']);
	$pconfig['enable'] = isset($a_cp[$cpzone]['enable']);
	$pconfig['auth_method'] = $a_cp[$cpzone]['auth_method'];
	$pconfig['localauth_priv'] = isset($a_cp[$cpzone]['localauth_priv']);
	$pconfig['preauthurl'] = strtolower($a_cp[$cpzone]['preauthurl']);
	$pconfig['logoutwin_enable'] = isset($a_cp[$cpzone]['logoutwin_enable']);
	$pconfig['peruserbw'] = isset($a_cp[$cpzone]['peruserbw']);
	$pconfig['bwdefaultdn'] = $a_cp[$cpzone]['bwdefaultdn'];
	$pconfig['bwdefaultup'] = $a_cp[$cpzone]['bwdefaultup'];
	$pconfig['nomacfilter'] = isset($a_cp[$cpzone]['nomacfilter']);
	$pconfig['noconcurrentlogins'] = isset($a_cp[$cpzone]['noconcurrentlogins']);
	$pconfig['redirurl'] = $a_cp[$cpzone]['redirurl'];
	$pconfig['passthrumacadd'] = isset($a_cp[$cpzone]['passthrumacadd']);
	$pconfig['passthrumacaddusername'] = isset($a_cp[$cpzone]['passthrumacaddusername']);
	$pconfig['reverseacct'] = isset($a_cp[$cpzone]['reverseacct']);
	$pconfig['page'] = array();
	if ($a_cp[$cpzone]['page']['htmltext'])
		$pconfig['page']['htmltext'] = $a_cp[$cpzone]['page']['htmltext'];
	if ($a_cp[$cpzone]['page']['errtext'])
		$pconfig['page']['errtext'] = $a_cp[$cpzone]['page']['errtext'];
	if ($a_cp[$cpzone]['page']['logouttext'])
		$pconfig['page']['logouttext'] = $a_cp[$cpzone]['page']['logouttext'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "zone cinterface");
		$reqdfieldsn = array(gettext("Zone name"), gettext("Interface"));

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		/* make sure no interfaces are bridged or used on other zones */
		if (is_array($_POST['cinterface'])) {
			foreach ($pconfig['cinterface'] as $cpbrif) {
				if (link_interface_to_bridge($cpbrif))
					$input_errors[] = sprintf(gettext("The captive portal cannot be used on interface %s since it is part of a bridge."), $cpbrif);
				foreach ($a_cp as $cpkey => $cp) {
					if ($cpkey != $cpzone || empty($cpzone)) {
						if (in_array($cpbrif, explode(",", $cp['interface'])))
							$input_errors[] = sprintf(gettext("The captive portal cannot be used on interface %s since it is used already on %s instance."), $cpbrif, $cp['zone']);
					}
				}
			}
		}
	}

	if ($_POST['timeout']) {
		if (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1))
			$input_errors[] = gettext("The timeout must be at least 1 minute.");
		else if (isset($config['dhcpd']) && is_array($config['dhcpd'])) {
			foreach ($config['dhcpd'] as $dhcpd_if => $dhcpd_data) {
				if (!isset($dhcpd_data['enable']))
					continue;
				if (!is_array($_POST['cinterface']) || !in_array($dhcpd_if, $_POST['cinterface']))
					continue;

				$deftime = 7200; // Default lease time
				if (isset($dhcpd_data['defaultleasetime']) && is_numeric($dhcpd_data['defaultleasetime']))
					$deftime = $dhcpd_data['defaultleasetime'];

				if ($_POST['timeout'] > $deftime)
					$input_errors[] = gettext("Hard timeout must be less or equal Default lease time set on DHCP Server");
			}
		}
	}
	if ($_POST['idletimeout'] && (!is_numeric($_POST['idletimeout']) || ($_POST['idletimeout'] < 1))) {
		$input_errors[] = gettext("The idle timeout must be at least 1 minute.");
	}
	if ($_POST['freelogins_count'] && (!is_numeric($_POST['freelogins_count']))) {
		$input_errors[] = gettext("The pass-through credit count must be a number or left blank.");
	} else if ($_POST['freelogins_count'] && is_numeric($_POST['freelogins_count']) && ($_POST['freelogins_count'] >= 1)) {
		if (empty($_POST['freelogins_resettimeout']) || !is_numeric($_POST['freelogins_resettimeout']) || ($_POST['freelogins_resettimeout'] <= 0)) {
			$input_errors[] = gettext("The waiting period to restore pass-through credits must be above 0 hours.");
		}
	}
	if ($_POST['maxproc'] && (!is_numeric($_POST['maxproc']) || ($_POST['maxproc'] < 4) || ($_POST['maxproc'] > 100))) {
		$input_errors[] = gettext("The maximum number of concurrent connections per client IP address may not be larger than the global maximum.");
	}

	if (!$input_errors) {
		$newcp =& $a_cp[$cpzone];
		//$newcp['zoneid'] = $a_cp[$cpzone]['zoneid'];
		if (empty($newcp['zoneid'])) {
			$newcp['zoneid'] = 8000;
			foreach ($a_cp as $keycpzone => $cp)
				if ($cp['zoneid'] == $newcp['zoneid'] && $keycpzone != $cpzone)
					$newcp['zoneid'] += 2; /* Resreve space for SSL config if needed */
		}
		$oldifaces = explode(",", $newcp['interface']);
		if (is_array($_POST['cinterface']))
			$newcp['interface'] = implode(",", $_POST['cinterface']);
		$newcp['webapi_space'] = $_POST['webapi_space'];
    $newcp['webapi_token'] = $_POST['webapi_token'];
		$newcp['maxproc'] = $_POST['maxproc'];
		$newcp['maxprocperip'] = $_POST['maxprocperip'] ? $_POST['maxprocperip'] : false;
		$newcp['timeout'] = $_POST['timeout'];
		$newcp['idletimeout'] = $_POST['idletimeout'];
		$newcp['freelogins_count'] = $_POST['freelogins_count'];
		$newcp['freelogins_resettimeout'] = $_POST['freelogins_resettimeout'];
		$newcp['freelogins_updatetimeouts'] = $_POST['freelogins_updatetimeouts'] ? true : false;
		if ($_POST['enable'])
			$newcp['enable'] = true;
		else
			unset($newcp['enable']);
		$newcp['auth_method'] = $_POST['auth_method'];
		$newcp['localauth_priv'] = isset($_POST['localauth_priv']);
		$newcp['preauthurl'] = $_POST['preauthurl'];
		$newcp['peruserbw'] = $_POST['peruserbw'] ? true : false;
		$newcp['bwdefaultdn'] = $_POST['bwdefaultdn'];
		$newcp['bwdefaultup'] = $_POST['bwdefaultup'];
		$newcp['logoutwin_enable'] = $_POST['logoutwin_enable'] ? true : false;
		$newcp['nomacfilter'] = $_POST['nomacfilter'] ? true : false;
		$newcp['noconcurrentlogins'] = $_POST['noconcurrentlogins'] ? true : false;
		$newcp['redirurl'] = $_POST['redirurl'];
		$newcp['passthrumacadd'] = $_POST['passthrumacadd'] ? true : false;
		$newcp['passthrumacaddusername'] = $_POST['passthrumacaddusername'] ? true : false;
		$newcp['reverseacct'] = $_POST['reverseacct'] ? true : false;
		if (!is_array($newcp['page']))
			$newcp['page'] = array();

		/* file upload? */
		if (is_uploaded_file($_FILES['htmlfile']['tmp_name']))
			$newcp['page']['htmltext'] = base64_encode(file_get_contents($_FILES['htmlfile']['tmp_name']));
		if (is_uploaded_file($_FILES['errfile']['tmp_name']))
			$newcp['page']['errtext'] = base64_encode(file_get_contents($_FILES['errfile']['tmp_name']));
		if (is_uploaded_file($_FILES['logoutfile']['tmp_name']))
			$newcp['page']['logouttext'] = base64_encode(file_get_contents($_FILES['logoutfile']['tmp_name']));

		write_config();

		/* Clear up unselected interfaces */
		$newifaces = explode(",", $newcp['interface']);
		$toremove = array_diff($oldifaces, $newifaces);
		if (!empty($toremove)) {
			foreach ($toremove as $removeif) {
				$removeif = get_real_interface($removeif);
				mwexec("/usr/local/sbin/ipfw_context -d {$cpzone} -x {$removeif}");
			}
		}
		captiveportal_configure_zone($newcp);
		unset($newcp, $newifaces, $toremove);
		filter_configure();
		header("Location: services_captiveportal_zones.php");
		exit;
	} else {
		if (is_array($_POST['cinterface']))
			$pconfig['cinterface'] = implode(",", $_POST['cinterface']);
	}
}
include("head.inc");
?>
<script language="JavaScript">
<!--
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);

	document.iform.cinterface.disabled = endis;
	document.iform.webapi_space.disabled = endis;
  document.iform.webapi_token.disabled = endis;
	document.iform.maxprocperip.disabled = endis;
	document.iform.idletimeout.disabled = endis;
	document.iform.freelogins_count.disabled = endis;
	document.iform.freelogins_resettimeout.disabled = endis;
	document.iform.freelogins_updatetimeouts.disabled = endis;
	document.iform.timeout.disabled = endis;
	document.iform.preauthurl.disabled = endis;
	document.iform.redirurl.disabled = endis;
	document.iform.peruserbw.disabled = endis;
	document.iform.bwdefaultdn.disabled = endis;
	document.iform.bwdefaultup.disabled = endis;
	document.iform.auth_method[0].disabled = endis;
	document.iform.logoutwin_enable.disabled = endis;
	document.iform.nomacfilter.disabled = endis;
	document.iform.noconcurrentlogins.disabled = endis;
	document.iform.htmlfile.disabled = endis;
	document.iform.errfile.disabled = endis;
	document.iform.logoutfile.disabled = endis;
}
//-->
</script>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_captiveportal.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[] = array(gettext("Captive portal(s)"), true, "services_captiveportal.php?zone={$cpzone}");
	$tab_array[] = array(gettext("Pass-through MAC"), false, "services_captiveportal_mac.php?zone={$cpzone}");
	$tab_array[] = array(gettext("Allowed IP addresses"), false, "services_captiveportal_ip.php?zone={$cpzone}");
	$tab_array[] = array(gettext("Allowed Hostnames"), false, "services_captiveportal_hostname.php?zone={$cpzone}");
	$tab_array[] = array(gettext("Vouchers"), false, "services_captiveportal_vouchers.php?zone={$cpzone}");
	$tab_array[] = array(gettext("File Manager"), false, "services_captiveportal_filemanager.php?zone={$cpzone}");
	display_top_tabs($tab_array, true);
?>    </td></tr>
  <tr>
  <td class="tabcont">
  <table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
	  <td width="22%" valign="top" class="vtable">&nbsp;</td>
	  <td width="78%" class="vtable">
		<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
		<strong><?=gettext("Enable captive portal"); ?> </strong></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncellreq"><?=gettext("Interfaces"); ?></td>
	  <td width="78%" class="vtable">
		<select name="cinterface[]" multiple="true" size="<?php echo count($config['interfaces']); ?>" class="formselect" id="cinterface">
		  <?php
		  $interfaces = get_configured_interface_with_descr();
		  $cselected = explode(",", $pconfig['cinterface']);
		  foreach ($interfaces as $iface => $ifacename): ?>
			  <option value="<?=$iface;?>" <?php if (in_array($iface, $cselected)) echo "selected"; ?>>
			  <?=htmlspecialchars($ifacename);?>
			  </option>
		  <?php endforeach; ?>
		</select> <br>
		<span class="vexpl"><?=gettext("Select the interface(s) to enable for captive portal."); ?></span></td>
	</tr>
	<tr>
	  <td valign="top" class="vncell"><?=gettext("Maximum concurrent connections"); ?></td>
	  <td class="vtable">
		<table cellpadding="0" cellspacing="0">
                 <tr>
           			<td><input name="maxprocperip" type="text" class="formfld unknown" id="maxprocperip" size="5" value="<?=htmlspecialchars($pconfig['maxprocperip']);?>"> <?=gettext("per client IP address (0 = no limit)"); ?></td>
                 </tr>
               </table>
<?=gettext("This setting limits the number of concurrent connections to the captive portal HTTP(S) server. This does not set how many users can be logged in " .
"to the captive portal, but rather how many users can load the portal page or authenticate at the same time! " .
"Possible setting allowed is: minimum 4 connections per client IP address, with a total maximum of 100 connections."); ?></td>
	</tr>
	<tr>
	  <td valign="top" class="vncell"><?=gettext("Idle timeout"); ?></td>
	  <td class="vtable">
		<input name="idletimeout" type="text" class="formfld unknown" id="idletimeout" size="6" value="<?=htmlspecialchars($pconfig['idletimeout']);?>">
<?=gettext("minutes"); ?><br>
<?=gettext("Clients will be disconnected after this amount of inactivity. They may log in again immediately, though. Leave this field blank for no idle timeout."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Hard timeout"); ?></td>
	  <td width="78%" class="vtable">
		<input name="timeout" type="text" class="formfld unknown" id="timeout" size="6" value="<?=htmlspecialchars($pconfig['timeout']);?>">
		<?=gettext("minutes"); ?><br>
	  <?=gettext("Clients will be disconnected after this amount of time, regardless of activity. They may log in again immediately, though. Leave this field blank for no hard timeout (not recommended unless an idle timeout is set)."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Pass-through credits allowed per MAC address"); ?></td>
	  <td width="78%" class="vtable">
		<input name="freelogins_count" type="text" class="formfld unknown" id="freelogins_count" size="6" value="<?=htmlspecialchars($pconfig['freelogins_count']);?>">
		<?=gettext("per client MAC address (0 or blank = none)"); ?><br>
		<?=gettext("This setting allows passing through the captive portal without authentication a limited number of times per MAC address. Once used up, the client can only log in with valid credentials until the waiting period specified below has expired. Recommended to set a hard timeout and/or idle timeout when using this for it to be effective."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Waiting period to restore pass-through credits"); ?></td>
	  <td width="78%" class="vtable">
		<input name="freelogins_resettimeout" type="text" class="formfld unknown" id="freelogins_resettimeout" size="6" value="<?=htmlspecialchars($pconfig['freelogins_resettimeout']);?>">
		<?=gettext("hours"); ?><br>
		<?=gettext("Clients will have their available pass-through credits restored to the original count after this amount of time since using the first one. This must be above 0 hours if pass-through credits are enabled."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Reset waiting period on attempted access"); ?></td>
	  <td width="78%" class="vtable">
		<input name="freelogins_updatetimeouts" type="checkbox" class="formfld" id="freelogins_updatetimeouts" value="yes" <?php if($pconfig['freelogins_updatetimeouts']) echo "checked"; ?>>
		<strong><?=gettext("Enable waiting period reset on attempted access"); ?></strong><br>
		<?=gettext("If enabled, the waiting period is reset to the original duration if access is attempted when all pass-through credits have already been exhausted."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Logout popup window"); ?></td>
	  <td width="78%" class="vtable">
		<input name="logoutwin_enable" type="checkbox" class="formfld" id="logoutwin_enable" value="yes" <?php if($pconfig['logoutwin_enable']) echo "checked"; ?>>
		<strong><?=gettext("Enable logout popup window"); ?></strong><br>
	  <?=gettext("If enabled, a popup window will appear when clients are allowed through the captive portal. This allows clients to explicitly disconnect themselves before the idle or hard timeout occurs."); ?></td>
	</tr>
	<tr>
      <td valign="top" class="vncell"><?=gettext("Pre-authentication redirect URL"); ?> </td>
      <td class="vtable">
        <input name="preauthurl" type="text" class="formfld url" id="preauthurl" size="60" value="<?=htmlspecialchars($pconfig['preauthurl']);?>"><br>
		<?php printf(gettext("Use this field to set \$PORTAL_REDIRURL\$ variable which can be accessed using your custom captive portal index.php page or error pages."));?>
	  </td>
	</tr>
	<tr>
	  <td valign="top" class="vncell"><?=gettext("After authentication Redirection URL"); ?></td>
	  <td class="vtable">
		<input name="redirurl" type="text" class="formfld url" id="redirurl" size="60" value="<?=htmlspecialchars($pconfig['redirurl']);?>">
		<br>
<?=gettext("If you provide a URL here, clients will be redirected to that URL instead of the one they initially tried " .
"to access after they've authenticated."); ?></td>
	</tr>
	<tr>
      <td valign="top" class="vncell"><?=gettext("Concurrent user logins"); ?></td>
      <td class="vtable">
	<input name="noconcurrentlogins" type="checkbox" class="formfld" id="noconcurrentlogins" value="yes" <?php if ($pconfig['noconcurrentlogins']) echo "checked"; ?>>
	<strong><?=gettext("Disable concurrent logins"); ?></strong><br>
	<?=gettext("If this option is set, only the most recent login per username will be active. Subsequent logins will cause machines previously logged in with the same username to be disconnected."); ?></td>
	</tr>
	<tr>
      <td valign="top" class="vncell"><?=gettext("MAC filtering"); ?> </td>
      <td class="vtable">
        <input name="nomacfilter" type="checkbox" class="formfld" id="nomacfilter" value="yes" <?php if ($pconfig['nomacfilter']) echo "checked"; ?>>
        <strong><?=gettext("Disable MAC filtering"); ?></strong><br>
    <?=gettext("If this option is set, no attempts will be made to ensure that the MAC address of clients stays the same while they're logged in." .
    "This is required when the MAC address of the client cannot be determined (usually because there are routers between"); ?> <?php echo $g['product_name'] ?> <?=gettext("and the clients)."); ?>
    <?=gettext("If this is enabled, RADIUS MAC authentication cannot be used."); ?></td>
	</tr>
	<tr>
      <td valign="top" class="vncell"><?=gettext("Pass-through MAC Auto Entry"); ?></td>
      <td class="vtable">
        <input name="passthrumacadd" type="checkbox" class="formfld" id="passthrumacadd" value="yes" <?php if ($pconfig['passthrumacadd']) echo "checked"; ?>>
        <strong><?=gettext("Enable Pass-through MAC automatic additions"); ?></strong><br>
    <?=gettext("If this option is set, a MAC passthrough entry is automatically added after the user has successfully authenticated. Users of that MAC address will never have to authenticate again."); ?>
    <?=gettext("To remove the passthrough MAC entry you either have to log in and remove it manually from the"); ?> <a href="services_captiveportal_mac.php"><?=gettext("Pass-through MAC tab"); ?></a> <?=gettext("or send a POST from another system to remove it."); ?>
    <?=gettext("If this is enabled, RADIUS MAC authentication cannot be used. Also, the logout window will not be shown."); ?>
	<br/><br/>
        <input name="passthrumacaddusername" type="checkbox" class="formfld" id="passthrumacaddusername" value="yes" <?php if ($pconfig['passthrumacaddusername']) echo "checked"; ?>>
        <strong><?=gettext("Enable Pass-through MAC automatic addition with username"); ?></strong><br>
    <?=gettext("If this option is set, with the automatically MAC passthrough entry created the username, used during authentication, will be saved."); ?>
    <?=gettext("To remove the passthrough MAC entry you either have to log in and remove it manually from the"); ?> <a href="services_captiveportal_mac.php"><?=gettext("Pass-through MAC tab"); ?></a> <?=gettext("or send a POST from another system to remove it."); ?>
	</td>
	</tr>
	<tr>
      <td valign="top" class="vncell"><?=gettext("Per-user bandwidth restriction"); ?></td>
      <td class="vtable">
        <input name="peruserbw" type="checkbox" class="formfld" id="peruserbw" value="yes" <?php if ($pconfig['peruserbw']) echo "checked"; ?>>
        <strong><?=gettext("Enable per-user bandwidth restriction"); ?></strong><br><br>
        <table cellpadding="0" cellspacing="0">
        <tr>
        <td><?=gettext("Default download"); ?></td>
        <td><input type="text" class="formfld unknown" name="bwdefaultdn" id="bwdefaultdn" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultdn']);?>"> <?=gettext("Kbit/s"); ?></td>
        </tr>
        <tr>
        <td><?=gettext("Default upload"); ?></td>
        <td><input type="text" class="formfld unknown" name="bwdefaultup" id="bwdefaultup" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultup']);?>"> <?=gettext("Kbit/s"); ?></td>
        </tr></table>
        <br>
        <?=gettext("If this option is set, the captive portal will restrict each user who logs in to the specified default bandwidth. RADIUS can override the default settings. Leave empty or set to 0 for no limit."); ?> </td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Authentication"); ?></td>
	  <td width="78%" class="vtable">
  		<table cellpadding="0" cellspacing="0">
  		  <tr>
  		    <td colspan="2"><input name="auth_method" type="radio" id="auth_method" value="webapi" disabled="disabled" checked="checked"/><?=gettext("Cobot Web API"); ?></td>
  		  </tr>
  		  <tr>
  		    <td>&nbsp;</td>
  		    <td>&nbsp;</td>
  		  </tr>
        <tr>
          <td width="22%"><?=gettext("Space subdomain (&lt;subdomain&gt;.cobot.me)"); ?></td>
          <td width="78%">
            <input name="webapi_space" type="text" class="formfld unknown" id="webapi_space" size="20" value="<?=htmlspecialchars($pconfig['webapi_space']);?>">
            <input type="hidden" name="auth_method" value="webapi" />
          </td>
        </tr>
        <tr>
          <td width="22%"><?=gettext("Access Token"); ?></td>
          <td width="78%">
            <input name="webapi_token" type="text" class="formfld unknown" id="webapi_token" size="20" value="<?=htmlspecialchars($pconfig['webapi_token']);?>">
          </td>
        </tr>
  		</table>
		</td>
	</tr>
	<tr>
		<td valign="top" class="vncell"><?=gettext("HTTPS login"); ?></td>
		<td class="vtable">
			<input name="httpslogin_enable" type="checkbox" class="formfld" id="httpslogin_enable" value="yes" onClick="enable_change(false)" <?php if($pconfig['httpslogin_enable']) echo "checked"; ?>>
			<strong><?=gettext("Enable HTTPS login"); ?></strong><br>
			<?=gettext("If enabled, the username and password will be transmitted over an HTTPS connection to protect against eavesdroppers. A server name and certificate must also be specified below."); ?></td>
	</tr>
	<tr>
		<td valign="top" class="vncell"><?=gettext("HTTPS server name"); ?> </td>
		<td class="vtable">
			<input name="httpsname" type="text" class="formfld unknown" id="httpsname" size="30" value="<?=htmlspecialchars($pconfig['httpsname']);?>"><br>
			<?php printf(gettext("This name will be used in the form action for the HTTPS POST and should match the Common Name (CN) in your certificate (otherwise, the client browser will most likely display a security warning). Make sure captive portal clients can resolve this name in DNS and verify on the client that the IP resolves to the correct interface IP on %s."), $g['product_name']);?> </td>
	</tr>
	<tr id="ssl_opts">
		<td width="22%" valign="top" class="vncell"><?=gettext("SSL Certificate"); ?></td>
		<td width="78%" class="vtable">
			<?php if (count($a_cert)): ?>
			<select name="certref" id="certref" class="formselect">
				<?php
					foreach($a_cert as $cert):
						$selected = "";
						if ($pconfig['certref'] == $cert['refid'])
							$selected = "selected";
				?>
				<option value="<?=$cert['refid'];?>"<?=$selected;?>><?=$cert['descr'];?></option>
			<?php endforeach; ?>
			</select>
			<?php else: ?>
				<b><?=gettext("No Certificates defined."); ?></b> <br/>Create one under <a href="system_certmanager.php">System &gt; Cert Manager</a>.
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell"><?=gettext("Portal page contents"); ?></td>
		<td width="78%" class="vtable">
		<?=$mandfldhtml;?><input type="file" name="htmlfile" class="formfld file" id="htmlfile"><br>
		<?php
			list($host) = explode(":", $_SERVER['HTTP_HOST']);
			$zoneid = $pconfig['zoneid'] ? $pconfig['zoneid'] : 8000;
			if ($pconfig['httpslogin_enable']) {
				$port = $pconfig['listenporthttps'] ? $pconfig['listenporthttps'] : ($zoneid + 1);
				$href = "https://{$host}:{$port}";
			} else {
				$port = $pconfig['listenporthttp']  ? $pconfig['listenporthttp']  : $zoneid;
				$href = "http://{$host}:{$port}";
			}
		?>
		<?php if ($pconfig['page']['htmltext']): ?>
		<a href="<?=$href?>" target="_new"><?=gettext("View current page"); ?></a>
		  <br>
		  <br>
		<?php endif; ?>
			<?php
				printf(
					gettext('Upload an HTML/PHP file for the portal page here (leave blank to keep the current one). ' .
							'Make sure to include a form (POST to %1$s) with a submit button (%2$s) and a hidden field with %3$s and %4$s. ' .
							'Include the %5$s and %6$s and/or %7$s input fields if authentication is enabled, otherwise it will always fail.'),
					"&quot;{$PORTAL_ACTION}&quot;",
					"name=&quot;accept&quot;",
					"name=&quot;redirurl&quot;",
					"value=&quot;{$PORTAL_REDIRURL}&quot;",
					"&quot;auth_user&quot;",
					"&quot;auth_pass&quot;",
					"&quot;auth_voucher&quot;");
			?>
			<?=gettext("Example code for the form:"); ?><br>
		  <br>
		  <tt>&lt;form method=&quot;post&quot; action=&quot;$PORTAL_ACTION$&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_user&quot; type=&quot;text&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_pass&quot; type=&quot;password&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_voucher&quot; type=&quot;text&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;redirurl&quot; type=&quot;hidden&quot; value=&quot;$PORTAL_REDIRURL$&quot;&gt;<br>
&nbsp;&nbsp;&nbsp;&lt;input name=&quot;accept&quot; type=&quot;submit&quot; value=&quot;Continue&quot;&gt;<br>
		  &lt;/form&gt;</tt></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Authentication"); ?><br>
		<?=gettext("error page"); ?><br>
		<?=gettext("contents"); ?></td>
	  <td class="vtable">
		<input name="errfile" type="file" class="formfld file" id="errfile"><br>
		<?php if ($pconfig['page']['errtext']): ?>
		<a href="?zone=<?=$cpzone?>&amp;act=viewerrhtml" target="_blank"><?=gettext("View current page"); ?></a>
		  <br>
		  <br>
		<?php endif; ?>
<?=gettext("The contents of the HTML/PHP file that you upload here are displayed when an authentication error occurs. " .
"You may include"); ?> &quot;$PORTAL_MESSAGE$&quot;, <?=gettext("which will be replaced by the error or reply messages from the RADIUS server, if any."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top" class="vncell"><?=gettext("Logout"); ?><br>
		<?=gettext("page"); ?><br>
		<?=gettext("contents"); ?></td>
	  <td class="vtable">
		<input name="logoutfile" type="file" class="formfld file" id="logoutfile"><br>
		<?php if ($pconfig['page']['logouttext']): ?>
		<a href="?zone=<?=$cpzone?>&amp;act=viewlogouthtml" target="_blank"><?=gettext("View current page"); ?></a>
		  <br>
		  <br>
		<?php endif; ?>
<?=gettext("The contents of the HTML/PHP file that you upload here are displayed on authentication success when the logout popup is enabled."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%">
		<?php echo "<input name='zone' id='zone' type='hidden' value='{$cpzone}'/>"; ?>
		<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save"); ?>" onClick="enable_change(true)">
		<a href="services_captiveportal_zones.php"><input name="Cancel" type="button" class="formbtn" value="<?=gettext("Cancel"); ?>" onClick="enable_change(true)"></a>
	  </td>
	</tr>
	<tr>
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"><span class="vexpl"><span class="red"><strong><?=gettext("Note:"); ?><br>
		</strong></span><?=gettext("Changing any settings on this page will disconnect all clients! Don't forget to enable the DHCP server on your captive portal interface! Make sure that the default/maximum DHCP lease time is higher than the timeout entered on this page. Also, the DNS forwarder needs to be enabled for DNS lookups by unauthenticated clients to work."); ?> </span></td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
</body>
</html>
