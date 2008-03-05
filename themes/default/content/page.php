				<div class="page" id="page_<?php echo $page->id; ?>">
					<div id="page_inner_<?php echo $page->id; ?>">
						<h2>
<?php if ($user->can("edit_page") or $user->can("delete_page")): ?>
						<span class="info right">
							<?php $page->edit_link(); ?>
							<?php $page->delete_link(null, ' <em>/</em> '); ?>
						</span>
<?php endif; ?><?php echo $page->title; ?></h2>
						<br />
						<?php echo $page->body; ?>
					</div>
				</div>