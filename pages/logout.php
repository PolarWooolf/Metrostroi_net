<?php
if ($tox1n_lenvaya_jopa)
	$tox1n_lenvaya_jopa->logout();
header("Location: /" . ((isset($_GET['redirect']))? $_GET['redirect']:''));