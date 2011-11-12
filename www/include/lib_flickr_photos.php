<?php

	loadlib("flickr_photos_lookup");
	loadlib("flickr_photos_permissions");

	#################################################################

	function flickr_photos_get_by_id($id){

		$cache_key = "photo_{$id}";

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$lookup = flickr_photos_lookup_photo($id);

		if (! $lookup){
			return;
		}

		$user = users_get_by_id($lookup['user_id']);
		$cluster_id = $user['cluster_id'];

		# Temporary – this should never happen

		if (! $cluster_id){
			dumper($lookup);
			return null;
		}

		$enc_id = AddSlashes($id);
		$sql = "SELECT * FROM FlickrPhotos WHERE id='{$enc_id}'";

		$rsp = db_fetch_users($cluster_id, $sql);
		$row = db_single($rsp);

		if ($row){
			cache_set($cache_key, $row, "cache_locally");
		}

		return $row;
	}

	#################################################################

	function flickr_photos_update_photo(&$photo, $update){

		$cache_key = "photo_{$photo['id']}";

		#

		$lookup = flickr_photos_lookup_photo($photo['id']);

		if (! $lookup){
			return;
		}

		$user = users_get_by_id($lookup['user_id']);
		$cluster_id = $user['cluster_id'];

		$enc_id = AddSlashes($photo['id']);
		$where = "id={$enc_id}";

		$hash = array();

		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_update_users($cluster_id, 'FlickrPhotos', $hash, $where);

		if ($rsp['ok']){
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_count_for_user(&$user, $viewer_id=0){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		if ($extra = flickr_photos_permissions_photos_where($user['id'], $viewer_id)){
			$extra = " AND {$extra}";
		}

		$sql = "SELECT COUNT(id) AS cnt FROM FlickrPhotos WHERE user_id='{$enc_user}' {$extra}";
		$row = db_single(db_fetch_users($cluster_id, $sql));

		return $row['cnt'];
	}

	#################################################################

	function flickr_photos_for_user(&$user, $viewer_id=0, $more=array()){

		$cluster_id = $user['cluster_id'];
		$enc_user = AddSlashes($user['id']);

		if ($extra = flickr_photos_permissions_photos_where($user['id'], $viewer_id)){
			$extra = " AND {$extra}";
		}

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id='{$enc_user}' {$extra} ORDER BY dateupload DESC";
		return db_fetch_paginated_users($cluster_id, $sql, $more);
	}

	#################################################################

	function flickr_photos_add_photo($photo){

		$user = users_get_by_id($photo['user_id']);
		$cluster_id = $user['cluster_id'];

		$insert = array();

		foreach ($photo as $k => $v){
			$insert[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($cluster_id, 'FlickrPhotos', $insert);

		if ($rsp['ok']){
			$rsp['photo'] = $photo;
		}

		return $rsp;
	}

	#################################################################

	function flickr_photos_get_bookends(&$photo, $viewer_id=0){

		$user = users_get_by_id($photo['user_id']);
		$cluster_id = $user['cluster_id'];

		$enc_id = AddSlashes($photo['id']);
		$enc_user = AddSlashes($photo['user_id']);

		if ($extra = flickr_photos_permissions_photos_where($photo['user_id'], $viewer_id)){
			$extra = " AND {$extra}";
		}

		# FIX ME: INDEXES

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id = '{$enc_user}' AND id < '{$enc_id}' {$extra} ORDER BY id DESC LIMIT 1";
		$rsp = db_fetch_users($cluster_id, $sql);

		$before = $rsp['rows'];

		# FIX ME: INDEXES

		$sql = "SELECT * FROM FlickrPhotos WHERE user_id='{$enc_user}' AND id > '{$enc_id}' {$extra} ORDER BY id ASC LIMIT 1";
		$rsp = db_fetch_users($cluster_id, $sql);

		$after = $rsp['rows'];

		return array(
			'ok' => 1,
			'before' => $before,
			'after' => $after,
		);
	}

	#################################################################

	function flickr_photos_id_to_path($id){

		$parts = array();

		while (strlen($id)){

			$parts[] = substr($id, 0, 3);
			$id = substr($id, 3);
		}

		return implode("/", $parts);
	}

	#################################################################
?>