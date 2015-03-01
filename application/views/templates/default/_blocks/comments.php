<?php
if(!function_exists('display_comments'))
{
	function display_comments($comments, $module, $user, $is_answer = false, $offset)
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
					<a href="?answer=<?=$c->id?>#comment-<?=$c->id?>" data-comment-answer="<?=$c->id?>">Answer</a>
				<?php endif; ?>
				<?php if(!isset($c->answers) && $c->count_answers > 0) : ?>
					&sdot; <a href="?answers=<?=$c->id?>#comment-<?=$c->id?>" data-comment-answers="true" data-comment-reference="<?=$c->id?>" data-comment-action="next">Show answers (<?=$c->count_answers?>)</a>
				<?php endif; ?>
			</div>
			<a class="user-image" href="<?=site_url('user/'.$c->creator->id)?>"><img src="<?=avatar_image($c->creator->id);?>" class="avatar" /></a>
			<?=/*($is_answer) ? '<span class="voting">123</span>':'';*/'' ?>
			<?php 
			if(isset($c->answers)) :
				$is_this_answer_post = (isset($_GET['answers']) && $_GET['answers'] == $c->id);
				if($is_this_answer_post && $offset > 0):
					?>
					<a class="show-answers-button" data-comment-reference="<?=$c->id?>" data-comment-action="previous" href="?answers=<?=$c->id?>&end=<?=($_GET['end'] > 30) ? $_GET['end']-30 : 0;?>">Show <?=($offset > 30) ? 30 : $offset;?> previous answers.</a>
					<?php
				endif;

				display_comments($c->answers, $module, $user, true, $offset); 
				$remaining_answers = $c->count_answers - count($c->answers);
				if($is_this_answer_post) $remaining_answers -= $offset;
				if($remaining_answers > 0) :
					$next_answers = (isset($_GET['end']) && $is_this_answer_post && isint($_GET['end'])) ? $_GET['end'] + 30 : 30;
					?>
					<a class="show-answers-button" data-comment-reference="<?=end($c->answers)->id?>" data-comment-action="next" href="?answers=<?=$c->id?>&end=<?=$next_answers?>">Show <?=($remaining_answers > 30) ? 30 : $remaining_answers;?> more answers.</a>
					<?php
				endif;
			endif; ?>
			<?php if($answer == $c->id) : ?>
				<?=form_open(uri_string() . '?answers=' . $c->id . '#comment-' . $c->id, array('class' => 'create-answer'))?>
					<?=form_hidden('duplicate-preventer', uniqid())?>
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
	<div class="comments" data-comment-module="<?=$module?>" data-comment-identifier="<?=$identifier?>">
		<div class="comment">
			<?=form_open(NULL, array('class' => 'create-comment'))?>
					<?=form_hidden('duplicate-preventer', uniqid())?>
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
		<div class="comments-container">
				<?php if(isset($commented) && $commented === false) : ?>
				Sorry, but there was an error processing your comment. please try again.
				<?php endif; ?>

			<?php if(isset($_GET['end']) && $offset[0] > 0) : ?>
				<a class="show-comments-button" data-comment-action="next" data-comment-reference="<?=array_values($comments)[0]->id?>" href="<?=$_GET['end'] > 30 ? '?end=' . ($_GET['end']-30) : '?';?>">Show <?=$offset[0]>30? 30 : $offset[0]?> next comments.</a>
			<?php endif; ?>
			<?php display_comments($comments, $module, $user, false, $answers_offset); ?>
			<?php 
			$c_more = $count_comments-count($comments)-$offset[0];
			if($c_more > 0) : ?>
				<a class="show-comments-button" data-comment-action="previous" data-comment-reference="<?=array_pop($comments)->id?>" href="?end=<?=isset($_GET['end']) && !isset($_GET['answer']) ? $_GET['end']+30 : 60; ?>">Show previous <?=($c_more > 30) ? 30 : $c_more;?> comments.</a>
			<?php endif; ?>
		</div>

	</div>