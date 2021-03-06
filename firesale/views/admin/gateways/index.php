
	<h1><?php echo lang('firesale:gateways:admin_title'); ?></h1>
	
	<section class="title">
		<h4><?php echo lang('firesale:gateways:installed_title'); ?></h4>
	</section>

	<section class="item">
		<?php if ( ! empty($gateways)): ?>
			<table id="product_table">		    
				<thead>
					<tr>
						<th><input type="checkbox" name="action_to_all" value="" class="check-all" /></th>
						<th><?php echo lang('firesale:gateways:labels:name'); ?></th>
						<th><?php echo lang('firesale:gateways:labels:desc'); ?></th>
						<th style="width: 200px;"></th>
					</tr>
				</thead>
				<tbody>
					
						<?php foreach ($gateways as $gateway): ?>
							<tr>
								<td><input type="checkbox" name="action_to[]" value="<?php echo $gateway['id']; ?>"  /></td>
								<td><?php echo $gateway['name']; ?></td>
								<td><?php echo $gateway['desc']; ?></td>
								<td class="actions">
									<?php if ($gateway['enabled']): ?>
										<a class="confirm button small" href="<?php echo site_url('admin/firesale/gateways/disable/'.$gateway['id']); ?>" title="<?php echo lang('firesale:gateways:warning'); ?>"><?php echo lang('buttons.disable'); ?></a>
									<?php else: ?>
										<a class="button small" href="<?php echo site_url('admin/firesale/gateways/enable/'.$gateway['id']); ?>"><?php echo lang('buttons.enable'); ?></a>
									<?php endif; ?>
									<a class="button small" href="<?php echo site_url('admin/firesale/gateways/edit/'.$gateway['slug']); ?>"><?php echo lang('buttons.edit'); ?></a>
									<a class="confirm button small" href="<?php echo site_url('admin/firesale/gateways/uninstall/'.$gateway['id']); ?>" title="<?php echo lang('firesale:gateways:warning'); ?>"><?php echo lang('buttons.uninstall'); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<div class="no_data"><?php echo lang('firesale:gateways:no_gateways'); ?></div>
		<?php endif; ?>
	</section>
	<div class="table_action_buttons">
		<button class="btn red green" value="publish" name="btnAction" type="submit" disabled="">
			<span><?php echo lang('buttons.enable'); ?></span>
		</button>
		
		<button class="btn red orange" value="publish" name="btnAction" type="submit" disabled="">
			<span><?php echo lang('buttons.disable'); ?></span>
		</button>
				
		<button class="btn red confirm" value="delete" name="btnAction" type="submit" disabled="">
			<span><?php echo lang('buttons.uninstall'); ?></span>
		</button>
	</div>
