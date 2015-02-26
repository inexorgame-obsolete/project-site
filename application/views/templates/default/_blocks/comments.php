<?php
if(!function_exists('display_comments'))
{
	function display_comments($comments, $module, $user, $is_answer = false)
	{
		if(isset($_GET['answer']) && isint($_GET['answer']))
			$answer = $_GET['answer'];
		else
			$answer = 0;

		foreach($comments as $c): 
?>
		<div class="comment<?=($is_answer)? ' answer' : '';?>" id="comment-<?=$c->id?>">
			<div class="comment-text">
				<a href="<?=site_url('user/'.$c->creator->id)?>"><?=showname($c->creator)?></a>
				<span class="text"><?=p_r($c->comment)?></span>
			</div>
			<div class="comment-actions">
				<?=dt_tm($c->date)?> &sdot;
				<?php if($answer != $c->id) : ?>
					<a href="?answer=<?=$c->id?>#comment-<?=$c->id?>">Answer</a>
				<?php endif; ?>
				<?php if(!isset($c->answers) && $c->count_answers > 0) : ?>
					&sdot; <a href="?answers=<?=$c->id?>">Show answers (<?=$c->count_answers?>)</a>
				<?php endif; ?>
			</div>
			<a class="user-image" href="<?=site_url('user/'.$c->creator->id)?>"><img src="<?=avatar_image($c->creator->id);?>" class="avatar" /></a>
			<?=($is_answer && true == false) ? '<span class="voting">123</span>':''; ?>
			<?php if(isset($c->answers)) display_comments($c->answers, $module, $user, true); ?>
			<?php if($answer == $c->id) : ?>
				<?=form_open(NULL, array('class' => 'create-answer'))?>
					<?=form_hidden('comment-answer-to', $c->id)?>
					<div class="comments-wrapper">
						<textarea name="comment" placeholder="<?=$user?'Your comment...':'You have to log in or create an account to comment.';?>"<?=$user?'autofocus':' disabled="disabled"';?>></textarea>
					</div>
					<?php
						$submit_data = array(
							'type' => 'submit',
							'name' => 'comments-submit',
							'value' => 'Submit comment'
						);
						if(!$user)
							$submit_data['disabled'] = 'disabled';
					?>
					<?=form_input($submit_data)?>
				<?=form_close();?>
			<?php endif; ?>
		</div>
		<?php endforeach;
	}
}
?>



	<div class="comments">
		<?php if(isset($commented) && $commented === false) : ?>
		Sorry, but there was an error processing your comment. please try again.
		<?php endif; ?>
		<div class="comment">
		<?=form_open(NULL, array('class' => 'create-comment'))?>
			<?=form_hidden('comment-module', $module)?>
			<?=form_hidden('comment-identifier', $identifier)?>
			<div class="comments-wrapper">
				<textarea name="comment" placeholder="<?=$user?'Your comment...':'You have to log in or create an account to comment.';?>"<?=$user?'':' disabled="disabled"';?>></textarea>
			</div>
			<?php 
				$submit_data = array(
					'type' => 'submit',
					'name' => 'comments-submit',
					'value' => 'Submit comment'
				);
				if(!$user)
					$submit_data['disabled'] = 'disabled';
			?>
			<?=form_input($submit_data)?>
		<?=form_close()?>
		<a class="user-image"><img src="<?=avatar_image($user ? $user->id : 0);?>" class="avatar" /></a>
		</div>
	<?php display_comments($comments, $module, $user); ?>	
	
	</div>