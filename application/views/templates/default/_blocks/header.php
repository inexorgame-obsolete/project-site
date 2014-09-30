<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
 	<title>{title}</title>

	<?php
		dcss('style');
		dcss('OpenSans');
	?>
	<script src="<?=js('jquery')?>"></script>
	<script src="<?=js('bg-pointer')?>"></script>
	<script>
		var default_search_api = '<?=$default_search_api;?>';
		var base_url = <?="'".$base."'";?>;
		var user_dir = <?php if(isset($user['id'])) echo "'data/user_upload/".$user['id']."/'"; else echo 'false'; ?>;
	</script>
	<script src="<?=js('search')?>"></script>
	{head}
</head>
<body>
	<a id="closer"></a>
	<div id="loader"><div></div></div>
	<div id="browse-data" class="hidden">
		<div class="browse-data close background"></div>
		<div class="browse-data window">
			<h3 class="browse-data window-title">Select data<span class="browse-data close">&times;</span></h3>
			<div class="browse-data content"></div>
			<div class="browse-data edit">
				<div class="browse-data upload">
					<div class="browse-data label">Upload file in this directory</div>
					<?=form_open_multipart('data/api/upload', array('class' => 'ajax-upload'));?>
						<input type="hidden" name="directory">
						<input type="hidden" name="type" value="file">
						<input type="file" name="file" class="browse-data upload-input">
						<input type="submit" class="browse-data upload-submit">
					<?=form_close();?>
				</div>
				<div class="browse-data create-dir">
					<div class="borwse-data label">Create folder</div>
					<?=form_open('data/api/createdir', array('class' => 'ajax-submit')); ?>
						<input type="hidden" name="parent_dirs">
						<input type="text" class="browse-data create-dir-input" name="dir"><!--
						--><input type="submit" class="browse-data create-dir-submit">
					<?=form_close();?>
				</div>
				<div class="browse-data message-container">
					<div class="browse-data label">Message</div>
					<div class="browse-data messages"></div>
				</div>
			</div>
		</div></div>
	<header>
		<div class="border menu">
			<div class="helper">
				<a href="<?=site_url()?>"><img src="<?=image('logo_small.png'); ?>" alt="sauerfork" /></a>
				<span class="title"><a href="<?=site_url()?>">{sitetitle}</a></span>
			</div>
			<ul id="main-menu">
				<li><a href="<?=site_url();?>">Project</a></li>
				<li><a href="<?=site_url('team')?>">Team</a></li>
				<li><a href="<?=site_url()?>#main-download-game">Download</a></li>
				<li><a href="<?=site_url('blog')?>">Blog</a></li>
			</ul>
			<?php if ($logged_in === false): ?>

			<?php else: ?>
			<div class="user-showcase">
				<div class="links">
					<a href="<?=site_url('user/'.$user['id'])?>"><?=showname($user);?></a><br />
					<a href="<?=site_url('user/edit')?>">Edit profile</a> | <a href="<?=site_url('auth/logout')?>">Logout</a>
				</div>
				<a href="<?=site_url('user/'.$user['id'])?>"><span class="avatar" style="background-image:url(<?=avatar_image($user['id'])?>);"></span></a>
			</div>
			<?php endif; ?>
			<div class="clear"></div>
		</div>
	</header>
	<div id="search-window">
		<a href="#" class="close-background"></a>
		<div class="centered content">
			<div class="search">
				<?=form_open('search');?>
					<?php if(count($search_form['radio']['inputs'])>1) : ?>
						<div class="radio">
							<noscript>Search for:</noscript>
							<?php foreach($search_form['radio']['inputs'] as $i => $f): ?>
								<?=form_label($search_form['radio']['labels'][$i]['value'], $search_form['radio']['labels'][$i]['for']);?>
								<?=form_radio($f);?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="text"><?=form_input($search_form['search']);?></div>
					<div class="submit"><?=form_submit($search_form['submit']);?></div>
				<?=form_close();?>
				<div class="result" data-searchid="<?=$main_search_id;?>"></div>
			</div>
		</div>
	</div>
	<div id="main-eyecatcher" class="eyecatcher image-mover" style="background-image:url({eyecatcher_image:<?=iimage('eyecatcher');?>});">
	</div>
	<div class="outfader">
	</div>
	<div class="wrapper">