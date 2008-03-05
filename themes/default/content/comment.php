		<li id="comment_<?php echo $comment->id ?>">
<?php if ($comment->status == "denied") : ?>
			<em><?php echo __("Your comment is awaiting moderation.", "theme"); ?></em>
<?php endif; ?>
			<blockquote>
				<p><?php echo $comment->body; ?></p>
			</blockquote>
			<cite>
				<strong><?php $comment->author_link($comment->id); ?></strong> <?php echo __("on", "theme"); ?> 
				<?php echo when('F jS, Y \a\t g:i a', $comment->created_at) ?>
				<?php $comment->edit_link($comment->id, null, ' <em>/</em> ', null); ?>
				<?php $comment->delete_link($comment->id, null, ' <em>/</em> ', null); ?>
			</cite>
		</li>
