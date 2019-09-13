<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?=$head->render()?>
	</head>
	<body>
		<div lxid="<?=lx\WidgetHelper::LXID_ALERTS?>" class="lxbody"></div>
		<div lxid="<?=lx\WidgetHelper::LXID_TOSTS?>" class="lxbody"></div>
		<div lxid="<?=lx\WidgetHelper::LXID_BODY?>" class="lxbody"></div>
		<script id=__js>
			document.body.removeChild(document.getElementById('__js'));
			<?=$js?>
		</script>
	</body>
</html>