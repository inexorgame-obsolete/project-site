<?=form_open(current_url() . $anchor, array('class' => 'rating-large', 'data-current-user-rating' => $user_vote))?>
<span class="rating-positive" title="positive ratings" data-positive="<?=$positive?>" data-content-replace="true"><?=$positive?></span><!--
--><span class="rating-negative" title="negative ratings" data-negative="<?=$negative?>" data-content-replace="true"><?=$negative?></span>
<?=form_hidden('rate_module', $module)?>
<?=form_hidden('rate_identifier', $identifier)?> 
<button type="submit" name="rating"<?=($logged_in ? '' : ' disabled="disabled" title="You have to log in or create an account to vote."')?> <?=($own_post ? 'disabled="disabled" title="You can not rate your own posts." ' : '')?>data-rating-button="down" class="down fa fa-minus-square<?=$user_vote != -1 ? '-o' : ''?> fa-only" value="<?=$user_vote != -1 ? 'down' : 'remove'?>" /></button>
<span class="rating"><?=$rating?></span>
<button type="submit" name="rating"<?=($logged_in ? '' : ' disabled="disabled" title="You have to log in or create an account to vote."')?> <?=($own_post ? 'disabled="disabled" title="You can not rate your own posts." ' : '')?>data-rating-button="up" class="up fa fa-plus-square<?=$user_vote != 1 ? '-o' : ''?> fa-only" value="<?=$user_vote != 1 ? 'up' : 'remove'?>" /></button>
<?=form_close();?>