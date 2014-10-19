<div class="centered">
	<h1 class="text-contrast in-eyecatcher">IRC-Log</h1>
	<table id="irc-log">
		<thead>
			<tr>
				<td id="tbl-time">Time</td>
				<td id="tbl-msg">Message</td>
			</tr>
		</thead>
		<tbody>
			<?php foreach($log as $l) : ?>
				<tr class="<?=$l->mtype?>">
					<td><?=tm($l->timestamp);?></td>
				<?php if($l->mtype == 'user_message'): ?>
					<td>
						<?php if($l->type == 'message') : ?>
							<?=ph($l->nickname)?>: <?=ph($l->text)?>
						<?php elseif($l->type == 'action') : ?>
							&mdash; <em><?=ph($l->nickname)?> <?=ph($l->text)?></em>
						<?php endif; ?>
					</td>
				<?php elseif($l->mtype == 'user_connection'): ?>
					<td>
					<?php if($l->type == 'join') : ?>
						<em><?=ph($l->nickname)?></em> joined <?=$channel;?>.
					<?php elseif($l->type == 'part' || $l->type == 'quit') : ?>
						<em><?=ph($l->nickname)?></em> left <?=$channel?>.
					<?php elseif($l->type == 'bot-connect') : ?>
						The bot joined the channel.
					<?php endif; ?>
					</td>
				<?php elseif($l->mtype == 'user_renaming') : ?>
					<td>
					<em><?=ph($l->nickname);?></em> is now known as <em><?=ph($l->newnick);?>.</em>
					</td>
				<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="2">
				In the channel were at this moment: 
				<?php $i = 0; foreach($start_users as $n => $r): 
					if($i > 0) : ?>,<?php endif; ?>
					<?=$r?><?=ph($n)?><?php $i++; endforeach; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="vertical-nav">
	<?php 
	if($start <= $max_pagination):
		if($start != 1):
			$i = ($start > 5) ? $start-5 : 1;
			while($start != $i) :
		?><a href="<?=site_url('irclog/' . $i . '/' . $results);?>"><?=$i?></a><?php 
			$i++;
			endwhile;
		endif;
		?><a class="current"><?=$start?></a><?php
		if($start != $max_pagination):
			$i = ($start < ($max_pagination-5)) ? $start+5 : $max_pagination;
			while($start != $i) :
			$start++;
		?><a href="<?=site_url('irclog/' . $start . '/' . $results);?>"><?=$start?></a><?php
			endwhile;
		endif;
	else:
		$i = ($max_pagination > 5) ? $max_pagination-5 : 1;
		while($max_pagination >= $i):
		?><a href="<?=site_url('irclog/' . $i . '/' . $results);?>"><?=$i;?></a><?php 
		$i++;
		endwhile; 
	endif;
	?>
	</div>
</div>