<?php

	include("../include/init.php");
	loadlib("god");

	features_ensure_enabled("flickr_push");

	loadlib("flickr_push");
	loadlib("flickr_push_subscriptions");

	$id = get_int32("id");
	$sub = flickr_push_subscriptions_get_by_id($id);

	if (! $sub){
		error_404();
	}

	$crumb_key = "delete_feed";
	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	if ((post_str("delete") && (crumb_check($crumb_key)))){
		# please write me
	}

	$topic_map = flickr_push_topic_map();
	$sub['str_topic'] = $topic_map[$sub['topic_id']];

	if ($sub['last_update_details']){
		$sub['last_update_details'] = json_decode($sub['last_update_details'], "as hash");
	}

	$sub['owner'] = users_get_by_id($sub['user_id']);

	$GLOBALS['smarty']->assign_by_ref("subscription", $sub);

	$GLOBALS['smarty']->display("page_god_push_subscription.txt");
	exit();
?>