<div class="centered">
	<h1 class="in-eyecatcher text-contrast"><?php he($info->name); ?></h1>

	<?php if(!empty($form['validation_message'])): ?>
	<div class="message">
		<div class="container"><?=$form['validation_message']; ?></div>
	</div>
	<?php endif; ?>
	<div id="shoutbox">
		<div class="info">
			<div class="tooltip-container avatar-container">
				<a href="<?=site_url('user/'.$creator->id)?>">
					<span class="avatar" style="background-image:url({eyecatcher_image:<?=avatar_image($creator->id)?>});"></span>
				</a>
				<div class="tooltip short">
					<a href="<?=site_url('user/'.$creator->id)?>" class="content"><?=showname($creator);?></a>
				</div>
			</div>
			<div class="container">
				<p class="description">
					<?=nl2br(h($info->description));?>
				</p>
			</div>
			<div class="clear"></div>
		</div>
		<?php if($permissions == 'write') : ?>
			<div class="shout self">
			<?php echo form_open("shoutbox/" . $info->id); ?>
				<div class="container">
					<div class="speech-bubble left"></div>
					<?php echo form_textarea($form['text']); ?>
					<?php 
					$form['submit']['class'] = 'submit-shout';
					echo form_submit($form['submit'], $form['submit']['value']); 
					?>
				</div>
				<div class="avatar-container">
					<span class="avatar" style="background-image:url(<?=avatar_image($user->id)?>);"></span>
				</div>

			<?php echo form_close(); ?>
			</div>
		<?php endif; ?>
		<?php foreach($shouts as $shout) : 
		$shout_author = $authors[$shout->user_id];
		?>
		<div class="shout<?php if($user && $user->id == $shout->user_id) echo " self"; ?>">
			<div class="container">
				<div class="speech-bubble <?php if($user && $user->id == $shout->user_id) echo "left"; else echo "right"; ?>"></div>
				<p><?=link_links(nl2br(h($shout->shout)));?></p>
			</div>
			<div class="tooltip-container<?php if($user && $user->id == $shout->user_id) echo " right"; ?> avatar-container">
				<a href="<?=site_url('user/'.$shout_author->id)?>">
					<span class="avatar" style="background-image:url(<?=avatar_image($shout_author->id)?>);"></span>
				</a>
				<div class="tooltip short">
					<a href="<?=site_url('user/'.$shout_author->id)?>" class="content"><?=showname($shout_author);?></a>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<?php endforeach; ?>
	</div>
</div>