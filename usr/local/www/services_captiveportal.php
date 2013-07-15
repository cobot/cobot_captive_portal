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

$statusurl = "status_captiveportal.php";
$logurl = "diag_logs_auth.php";

require("guiconfig.inc");
require("functions.inc");
require("filter.inc");
require("shaper.inc");
require("captiveportal.inc");

$pgtitle = array(gettext("Services"),gettext("Captive portal"));

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
	$config['captiveportal']['page'] = array();
	$config['captiveportal']['timeout'] = 60;
}

if ($_GET['act'] == "viewhtml") {
	echo base64_decode($config['captiveportal']['page']['htmltext']);
	exit;
} else if ($_GET['act'] == "viewerrhtml") {
	echo base64_decode($config['captiveportal']['page']['errtext']);
	exit;
} else if ($_GET['act'] == "viewlogouthtml") {
	echo base64_decode($config['captiveportal']['page']['logouttext']);
	exit;
}

$pconfig['cinterface'] = $config['captiveportal']['interface'];
$pconfig['webapi_space'] = $config['captiveportal']['webapi_space'];
$pconfig['webapi_token'] = $config['captiveportal']['webapi_token'];
$pconfig['maxprocperip'] = $config['captiveportal']['maxprocperip'];
$pconfig['timeout'] = $config['captiveportal']['timeout'];
$pconfig['idletimeout'] = $config['captiveportal']['idletimeout'];
$pconfig['freelogins_count'] = $config['captiveportal']['freelogins_count'];
$pconfig['freelogins_resettimeout'] = $config['captiveportal']['freelogins_resettimeout'];
$pconfig['freelogins_updatetimeouts'] = isset($config['captiveportal']['freelogins_updatetimeouts']);
$pconfig['enable'] = isset($config['captiveportal']['enable']);
$pconfig['auth_method'] = $config['captiveportal']['auth_method'];
$pconfig['preauthurl'] = strtolower($config['captiveportal']['preauthurl']);
$pconfig['logoutwin_enable'] = isset($config['captiveportal']['logoutwin_enable']);
$pconfig['peruserbw'] = isset($config['captiveportal']['peruserbw']);
$pconfig['bwdefaultdn'] = $config['captiveportal']['bwdefaultdn'];
$pconfig['bwdefaultup'] = $config['captiveportal']['bwdefaultup'];
$pconfig['nomacfilter'] = isset($config['captiveportal']['nomacfilter']);
$pconfig['noconcurrentlogins'] = isset($config['captiveportal']['noconcurrentlogins']);
$pconfig['redirurl'] = $config['captiveportal']['redirurl'];
$pconfig['passthrumacadd'] = isset($config['captiveportal']['passthrumacadd']);
$pconfig['passthrumacaddusername'] = isset($config['captiveportal']['passthrumacaddusername']);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "cinterface");
		$reqdfieldsn = array(gettext("Interface"));

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		/* make sure no interfaces are bridged */
		if (is_array($_POST['cinterface']))
			foreach ($pconfig['cinterface'] as $cpbrif)
				if (link_interface_to_bridge($cpbrif))
					$input_errors[] = sprintf(gettext("The captive portal cannot be used on interface %s since it is part of a bridge."), $cpbrif);
	}

	if ($_POST['timeout'] && (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1))) {
		$input_errors[] = gettext("The timeout must be at least 1 minute.");
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
	if ($_POST['maxprocperip'] && (!is_numeric($_POST['maxprocperip']) || ($_POST['maxprocperip'] < 4) || $_POST['maxprocperip'] > 100)) {
		$input_errors[] = gettext("The maximum number of concurrent connections per client IP address may not be larger than the global maximum.");
	}

	if (!$input_errors) {
		if (is_array($_POST['cinterface']))
			$config['captiveportal']['interface'] = implode(",", $_POST['cinterface']);
		$config['captiveportal']['webapi_space'] = $_POST['webapi_space'];
    $config['captiveportal']['webapi_token'] = $_POST['webapi_token'];      
		$config['captiveportal']['maxprocperip'] = $_POST['maxprocperip'] ? $_POST['maxprocperip'] : false;
		$config['captiveportal']['timeout'] = $_POST['timeout'];
		$config['captiveportal']['idletimeout'] = $_POST['idletimeout'];
		$config['captiveportal']['freelogins_count'] = $_POST['freelogins_count'];
		$config['captiveportal']['freelogins_resettimeout'] = $_POST['freelogins_resettimeout'];
		$config['captiveportal']['freelogins_updatetimeouts'] = $_POST['freelogins_updatetimeouts'] ? true : false;
		$config['captiveportal']['enable'] = $_POST['enable'] ? true : false;
		$config['captiveportal']['auth_method'] = $_POST['auth_method'];
		$config['captiveportal']['preauthurl'] = $_POST['preauthurl'];
		$config['captiveportal']['peruserbw'] = $_POST['peruserbw'] ? true : false;
		$config['captiveportal']['bwdefaultdn'] = $_POST['bwdefaultdn'];
		$config['captiveportal']['bwdefaultup'] = $_POST['bwdefaultup'];
		$config['captiveportal']['logoutwin_enable'] = $_POST['logoutwin_enable'] ? true : false;
		$config['captiveportal']['nomacfilter'] = $_POST['nomacfilter'] ? true : false;
		$config['captiveportal']['noconcurrentlogins'] = $_POST['noconcurrentlogins'] ? true : false;
		$config['captiveportal']['redirurl'] = $_POST['redirurl'];
		$config['captiveportal']['passthrumacadd'] = $_POST['passthrumacadd'] ? true : false;
		$config['captiveportal']['passthrumacaddusername'] = $_POST['passthrumacaddusername'] ? true : false;

		/* file upload? */
		if (is_uploaded_file($_FILES['htmlfile']['tmp_name']))
			$config['captiveportal']['page']['htmltext'] = base64_encode(file_get_contents($_FILES['htmlfile']['tmp_name']));
		if (is_uploaded_file($_FILES['errfile']['tmp_name']))
			$config['captiveportal']['page']['errtext'] = base64_encode(file_get_contents($_FILES['errfile']['tmp_name']));
		if (is_uploaded_file($_FILES['logoutfile']['tmp_name']))
			$config['captiveportal']['page']['logouttext'] = base64_encode(file_get_contents($_FILES['logoutfile']['tmp_name']));

		write_config();

		$retval = 0;
		$retval = captiveportal_configure();

		$savemsg = get_std_save_message($retval);
		
		if (is_array($_POST['cinterface']))
			$pconfig['cinterface'] = implode(",", $_POST['cinterface']);

		filter_configure();
	}
}
include("head.inc");
?>
<?php include("fbegin.inc"); ?>
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
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_captiveportal.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
<?php
	$tab_array = array();
	$tab_array[] = array(gettext("Captive portal"), true, "services_captiveportal.php");
	$tab_array[] = array(gettext("Pass-through MAC"), false, "services_captiveportal_mac.php");
	$tab_array[] = array(gettext("Allowed IP addresses"), false, "services_captiveportal_ip.php");
	$tab_array[] = array(gettext("Allowed Hostnames"), false, "services_captiveportal_hostname.php");	
	$tab_array[] = array(gettext("Vouchers"), false, "services_captiveportal_vouchers.php");
	$tab_array[] = array(gettext("File Manager"), false, "services_captiveportal_filemanager.php");
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
           			<td><input name="maxprocperip" type="text" class="formfld unknown" id="maxprocperip" size="5" 
value="<?=htmlspecialchars($pconfig['maxprocperip']);?>"> <?=gettext("per client IP address (0 = no limit)"); ?></td>
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
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method" value="webapi" disabled="disabled" checked="checked"/>
  <?=gettext("Cobot Web API"); ?></td>
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
	<tr>
	  <td width="22%" valign="top" class="vncellreq"><?=gettext("Portal page contents"); ?></td>
	  <td width="78%" class="vtable">
		<?=$mandfldhtml;?><input type="file" name="htmlfile" class="formfld file" id="htmlfile"><br>
		<?php
			list($host) = explode(":", $_SERVER['HTTP_HOST']);
			if(isset($config['captiveportal']['httpslogin'])) {
				$href = "https://$host:8001";
			} else {
				$href = "http://$host:8000";
			}
		?>
		<?php if ($config['captiveportal']['page']['htmltext']): ?>
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
		<?php if ($config['captiveportal']['page']['errtext']): ?>
		<a href="?act=viewerrhtml" target="_blank"><?=gettext("View current page"); ?></a>
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
		<?php if ($config['captiveportal']['page']['logouttext']): ?>
		<a href="?act=viewlogouthtml" target="_blank"><?=gettext("View current page"); ?></a>
		  <br>
		  <br>
		<?php endif; ?>
<?=gettext("The contents of the HTML/PHP file that you upload here are displayed on authentication success when the logout popup is enabled."); ?></td>
	</tr>
	<tr>
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%">
		<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save"); ?>" onClick="enable_change(true)">
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

