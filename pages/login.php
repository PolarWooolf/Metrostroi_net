<?php
Mitrastroi::TakeClass('openid');
if($tox1n_lenvaya_jopa) {
	include MITRASTROI_ROOT . "page/404.php";
	return;
}
try {
	$openid = new LightOpenID('http://' . $_SERVER['SERVER_NAME'] . '/login/');
	if (!$openid->mode) {
		if (isset($_GET['login'])) {
			$openid->identity = 'http://steamcommunity.com/openid/?l=english';
			header('Location: ' . $openid->authUrl());
		}
//		include (MITRASTROI_ROOT."pages/404.php");
	} elseif ($openid->mode == 'cancel') {
		echo 'User has canceled authentication!';
	} else {
		if ($openid->validate()) {
			$id = $openid->identity;
			// identity is something like: http://steamcommunity.com/openid/id/76561197960435530
			// we only care about the unique account ID at the end of the URL.
			$ptn = "/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
			preg_match($ptn, $id, $matches);
//			echo "User is logged in (steamID: $matches[1])\n";

			$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$_STEAMAPI&steamids=$matches[1]";
			$json_object = file_get_contents($url);
			$json_decoded = json_decode($json_object);

			foreach ($json_decoded->response->players as $player) {
				/*echo "
                    <br/>Player ID: $player->steamid
                    <br/>Player Name: $player->personaname
                    <br/>Profile URL: $player->profileurl
                    <br/>SmallAvatar: <img src='$player->avatar'/>
                    <br/>MediumAvatar: <img src='$player->avatarmedium'/>
                    <br/>LargeAvatar: <img src='$player->avatarfull'/>
                    ";*/
				$status = json_encode(
					array(
						'admin'=>'',
						'nom'=>1,
						'date'=>time()
					)
				);
				$sessionID = Mitrastroi::randString(128);
				$db->execute("INSERT INTO `players` (`SID`, `group`, `status`, `session`) VALUES ('" . $db->safe(Mitrastroi::ToSteamID($player->steamid)) . "', 'user', '$status', '$sessionID')"
					. "ON DUPLICATE KEY UPDATE `session`='$sessionID'");
				$db->execute("INSERT INTO `user_info_cache` (`steamid`, `steam_url`, `avatar_url`, `nickname`) VALUES ('" . $db->safe(Mitrastroi::ToSteamID($player->steamid)) . "', '" . $db->safe($player->profileurl) . "', '" . $db->safe($player->avatarfull) . "', '" . $db->safe($player->personaname) . "')"
					. "ON DUPLICATE KEY UPDATE `steam_url`='" . $db->safe($player->profileurl) . "', `avatar_url`='" . $db->safe($player->avatarfull) . "', `nickname`='" . $db->safe($player->personaname) . "'") or die($db->error());
				setcookie("mitrastroi_sid", $sessionID, time() + 3600 * 24 * 30, '/');
				header("Location: /players");
			}

		} else {
			header("Location: /");
		}
	}
} catch (ErrorException $e) {
	echo $e->getMessage();
}