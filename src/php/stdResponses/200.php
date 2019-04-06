<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?=$title?></title>
		<link rel="shortcut icon" href="<?=$icon?>" type="image/png">
		<?= $lxCss ?>		
		<?= $css ?>
		<?= $headScripts ?>
	</head>
	<body>
		<div id="lx-alerts" class="lxbody"></div>
		<div id="lx-tosts" class="lxbody"></div>
		<div id="lx" class="lxbody"></div>
		<script id=__js>
			document.body.removeChild(document.getElementById('__js'));
			<?=$core?>
			lx.start(<?=$settings?>, <?=$data?>, `<?=$jsBootstrap?>`, `<?=$moduleInfo?>`, `<?=$jsMain?>`);
		</script>
	</body>
</html>