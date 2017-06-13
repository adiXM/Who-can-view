<?php


// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if(my_strpos($_SERVER['PHP_SELF'], 'showthread.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'showthread_moderationoptions_canview';
}

$plugins->add_hook("moderation_start", "whocanview_run");
$plugins->add_hook("showthread_start", "whocanview_thread");

function whocanview_info()
{
	return array(
		"name"				=> "Who can view the threads",
		"description"		=> "Which groups can view certain threads",
		"website"			=> "http://mybb.ro",
		"author"			=> "adiXM",
		"authorsite"		=> "http://mybb.ro",
		"version"			=> "1.1",
		"codename"			=> "whocanview",
		"compatibility"		=> "18*"
	);
}
function whocanview_activate() 
{
	global $db;
	$insert_array = array(
		'title'		=> 'moderation_canview',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Who can view this thread</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="moderation.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>Who can view this thread?</strong></td>
</tr>
{$loginbox}
<tr>
<td class="trow1"><strong>Who can view this thread: </strong></td>
<td class="trow2"><strong>{$grupz}</strong></td>
</tr>
<tr>
<td class="trow1"><strong>Usergroups</strong><br /><span class="smalltext">Usergroups can view this thread. Select multiple usergroups with CTRL and click.</span></td>
<td class="trow2"><select name="id_s[]" multiple>
<option value="1">Guests</option>
<option value="2">Registered</option>
<option value="3">Super Moderators</option>
<option value="4">Administrators</option>
<option value="5">Awaiting Activation</option>
<option value="6">Moderators</option>
<option value="7">Banned</option>
<option value="8">Black Belt</option>
<option value="9">Green Belt</option>
<option value="10">Yellow Belt</option>
<option value="11">White Belt</option>
</td>
</tr>
</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="Save" /></div>
<input type="hidden" name="action" value="do_canview" />
<input type="hidden" name="tid" value="{$tid}" />
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'showthread_moderationoptions_canview',
		'template'	=> $db->escape_string('<option value="canview">Who can view this thread?</option>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	$insert_array = array(
		'title'		=> 'usergroups_canview',
		'template'	=> $db->escape_string('<option value="{$grup_id}">{$groups}</option>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	$query = "ALTER TABLE " . TABLE_PREFIX . "threads ADD canview VARCHAR(255) NOT NULL default '0'";
	$db->write_query($query);
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_moderationoptions_manage", "#".preg_quote('{$lang->remove_subscriptions}</option>')."#i", '{$lang->remove_subscriptions}</option>{$canview}');

}
function whocanview_deactivate() 
{
	global $db;
	$query = "ALTER TABLE " . TABLE_PREFIX . "threads DROP canview";
	$db->write_query($query);
	$db->delete_query("templates", "title IN('moderation_canview','showthread_moderationoptions_canview','usergroups_canview')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_moderationoptions_manage", "#".preg_quote('{$canview}')."#i", '', 0);

	rebuild_settings();
}
function whocanview_run() {

	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $header, $footer, $loginbox, $canview, $moderation, $inlineids;

	if($mybb->input['action'] != "canview" && $mybb->input['action'] != "do_canview")
	{
		return;
	}
	if($mybb->user['uid'] != 0)
	{
		$mybb->user['username'] = htmlspecialchars_uni($mybb->user['username']);
		eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
	}
	else
	{
		eval("\$loginbox = \"".$templates->get("loginbox")."\";");
	}

	$tid = $mybb->get_input('tid', MyBB::INPUT_INT);
	$thread = get_thread($tid);
	if($mybb->input['action'] == "canview" && $mybb->request_method == "post")
	{

		verify_post_check($mybb->get_input('my_post_key'));

		if(!is_moderator($thread['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}
		if(!$thread['tid'])
		{
			error($lang->error_invalidthread);
		}
		check_forum_password($thread['fid']);

		$thread['subject'] = htmlspecialchars_uni($thread['subject']); 

		build_forum_breadcrumb($thread['fid']);
		add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
		$idt = (int)$thread['tid'];
		$query = $db->query("SELECT * FROM " . TABLE_PREFIX . "threads WHERE tid='$idt' ");
		$result = $db->fetch_array($query);
		$grupuri = $result['canview'];
		$doargrupuri = explode(',', $grupuri);
		//$grupuletze = array_keys($doargrupuri);
		//echo $grupuletze[2];
		$gr = array();
		$cnt = 0;
		foreach ($doargrupuri as $i) {
			$query1 = $db->query("SELECT * FROM " . TABLE_PREFIX . "usergroups WHERE gid='$i' ");
			$result1 = $db->fetch_array($query1);
			array_push($gr, $result1['title']);
		}
		$grupz = implode(',', $gr);
		eval("\$canview = \"".$templates->get("moderation_canview")."\";");
		output_page($canview);
	}
	if($mybb->input['action'] == "do_canview" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->get_input('my_post_key'));

		if(!is_moderator($thread['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}
		$groups_view = $mybb->get_input('id_s', MyBB::INPUT_STRING);

		$gr = array();
		foreach ($_POST['id_s'] as $gid) {
			array_push($gr, $gid);
		}
		$grupz = implode(',', $gr);
		$updated_group=array('canview' => $grupz);
		$idt = (int)$thread['tid'];
		$db->update_query("threads",$updated_group,"tid = '$idt'"); 
		moderation_redirect(get_thread_link($thread['tid']), "Succes!");
	}
	exit;
}
function Check ($val, $grups) {
	if(!in_array($val, $grups)) {
		return 1;
	}
	return 0;
}
function whocanview_thread()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $header, $footer, $loginbox, $canview, $moderation, $inlineids;
	$tid = $mybb->get_input('tid', MyBB::INPUT_INT);
	$thread = get_thread($tid);
	$idt = (int)$thread['tid'];
	$query = $db->query("SELECT * FROM " . TABLE_PREFIX . "threads WHERE tid='$idt' ");
	$result = $db->fetch_array($query);
	$grupuri = $result['canview'];
	$doargrupuri = explode(',', $grupuri);
	$grupuletze = array_keys($doargrupuri);
	if($mybb->user['usergroup'] != 0) {
		$eu  = $mybb->user['usergroup'];
	}
	if($eu == 3 || $eu == 6 || $eu == 4) {
		eval("\$canview = \"".$templates->get("showthread_moderationoptions_canview")."\";");
		return;
	}
	if(!in_array($eu, $doargrupuri)) {
		error_no_permission();
		return;
	}
	eval("\$canview = \"".$templates->get("showthread_moderationoptions_canview")."\";");

}


?>
