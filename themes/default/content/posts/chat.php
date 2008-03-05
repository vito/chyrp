<?php if (!empty($post->title)): ?>
						<h1>
							<a href="<?php echo $post->url(); ?>" rel="bookmark" title="<?php echo sprintf(__("Permanent Link to %s", "theme"), $post->title); ?>"><?php echo $post->title; ?></a>
						</h1>
<?php endif; ?>
						<?php echo $post->dialogue; ?>