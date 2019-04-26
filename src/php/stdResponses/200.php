<?php $relPath = explode(\lx::sitePath(), \lx::$conductor->getSystemPath('core'))[1]; ?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?=isset($title)?$title:'lx'?></title>
		<link href="<?=$relPath?>/css/lx.css" type="text/css" rel="stylesheet">
		<link rel="shortcut icon" href="<?=isset($icon)?$icon:$relPath.'/img/icon.png'?>" type="image/png">
		<?=isset($css)?$css:''?>
		<?=isset($headScripts)?$headScripts:''?>
	</head>
	<body>
		<div id="lx-alerts" class="lxbody"></div>
		<div id="lx-tosts" class="lxbody"></div>
		<div id="lx" class="lxbody"></div>
		<script id=__js>
			document.body.removeChild(document.getElementById('__js'));
			<?=\lx::getJsCore()?>
			lx.lang=<?=lx\JsCompiler::arrayToJsCode(\lx::$components->language->getCurrentData())?>;
			<?=isset($js)?$js:''?>
		</script>
	</body>
</html>