<?php namespace ProcessWire;

/**
 * Cookie widget markup: banner, preferences window, toast, floating icon,
 * placeholder blueprint. Override by copying to /site/templates/Cookie/banner.php
 *
 * @var Cookie $module
 * @var string $prefix CSS class prefix
 * @var bool $preview rendered inside the ProcessCookie builder preview
 * @var array $categories enabled categories: key, label, desc, required, services[]
 * @var array $t resolved texts
 * @var array $design design settings
 * @var string $iconSvg inline SVG of the chosen icon
 */

$p = $prefix;
$s = wire()->sanitizer;
$rootClass = "{$p}-root" . ($preview ? " {$p}-preview" : '');

$links = '';
if(!empty($t['link_privacy'])) {
	$links .= "<a href=\"{$s->entities1($t['link_privacy'])}\">{$s->entities1($t['txt_privacy'])}</a>";
}
if(!empty($t['link_imprint'])) {
	$links .= "<a href=\"{$s->entities1($t['link_imprint'])}\">{$s->entities1($t['txt_imprint'])}</a>";
}
?>
<div class="<?php echo $rootClass; ?>" id="<?php echo $p; ?>-root"
	data-layout="<?php echo $s->entities1($design['design_layout']); ?>"
	data-position="<?php echo $s->entities1($design['design_position']); ?>"
	data-overlay="<?php echo (int) $design['design_overlay']; ?>"
	data-fab-pos="<?php echo $s->entities1($design['icon_position']); ?>"
	data-fab-shape="<?php echo $s->entities1($design['icon_shape']); ?>"
	data-fab-transparent="<?php echo (int) $design['icon_transparent']; ?>">

	<div class="<?php echo $p; ?>-overlay" hidden></div>

	<section class="<?php echo $p; ?>-banner" role="dialog" aria-label="<?php echo $s->entities1($t['txt_banner_title']); ?>" hidden>
		<div class="<?php echo $p; ?>-banner-content">
			<?php if($t['txt_banner_title']): ?>
				<h2 class="<?php echo $p; ?>-title"><?php echo $s->entities1($t['txt_banner_title']); ?></h2>
			<?php endif; ?>
			<div class="<?php echo $p; ?>-text"><?php echo $t['txt_banner_text']; ?></div>
			<?php if($links): ?><div class="<?php echo $p; ?>-links"><?php echo $links; ?></div><?php endif; ?>
		</div>
		<div class="<?php echo $p; ?>-actions">
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-primary" data-action="accept-all"><?php echo $s->entities1($t['txt_btn_accept_all']); ?></button>
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-secondary" data-action="reject"><?php echo $s->entities1($t['txt_btn_reject']); ?></button>
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-ghost" data-action="prefs"><?php echo $s->entities1($t['txt_btn_prefs']); ?></button>
		</div>
	</section>

	<section class="<?php echo $p; ?>-prefs" role="dialog" aria-modal="true" aria-labelledby="<?php echo $p; ?>-prefs-title" hidden>
		<header class="<?php echo $p; ?>-prefs-head">
			<h2 class="<?php echo $p; ?>-title" id="<?php echo $p; ?>-prefs-title"><?php echo $s->entities1($t['txt_prefs_title']); ?></h2>
			<button type="button" class="<?php echo $p; ?>-close" data-action="close" aria-label="<?php echo $s->entities1($t['txt_close']); ?>">&#10005;</button>
		</header>
		<div class="<?php echo $p; ?>-prefs-body">
			<div class="<?php echo $p; ?>-text"><?php echo $t['txt_prefs_text']; ?></div>
			<ul class="<?php echo $p; ?>-cats">
				<?php foreach($categories as $cat): ?>
					<li class="<?php echo $p; ?>-cat">
						<label class="<?php echo $p; ?>-switch">
							<input type="checkbox"
								data-consent-cat="<?php echo $s->entities1($cat['key']); ?>"
								<?php echo $cat['required'] ? 'checked disabled' : ''; ?>>
							<span class="<?php echo $p; ?>-slider" aria-hidden="true"></span>
							<span class="<?php echo $p; ?>-cat-label"><?php echo $s->entities1($cat['label']); ?></span>
						</label>
						<?php if($cat['desc']): ?>
							<p class="<?php echo $p; ?>-cat-desc"><?php echo $s->entities1($cat['desc']); ?></p>
						<?php endif; ?>
						<?php if(count($cat['services'])): ?>
							<details class="<?php echo $p; ?>-svc">
								<summary><?php echo $s->entities1($t['txt_details']); ?> (<?php echo count($cat['services']); ?>)</summary>
								<table class="<?php echo $p; ?>-svc-table">
									<?php foreach($cat['services'] as $svc): ?>
										<tr>
											<th><?php echo $s->entities1($svc['name']); ?></th>
											<td>
												<?php if($svc['provider']): ?><span><?php echo $s->entities1($svc['provider']); ?></span><?php endif; ?>
												<?php if($svc['purpose']): ?><span><?php echo $s->entities1($svc['purpose']); ?></span><?php endif; ?>
												<?php if($svc['duration']): ?><span><?php echo $s->entities1($svc['duration']); ?></span><?php endif; ?>
												<?php if(count($svc['cookies'])): ?><code><?php echo $s->entities1(implode(', ', $svc['cookies'])); ?></code><?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</table>
							</details>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="<?php echo $p; ?>-actions">
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-primary" data-action="save"><?php echo $s->entities1($t['txt_btn_save']); ?></button>
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-secondary" data-action="accept-all"><?php echo $s->entities1($t['txt_btn_accept_all']); ?></button>
			<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-secondary" data-action="reject"><?php echo $s->entities1($t['txt_btn_reject']); ?></button>
		</div>
		<?php if($links): ?><div class="<?php echo $p; ?>-links"><?php echo $links; ?></div><?php endif; ?>
	</section>

	<div class="<?php echo $p; ?>-toast" role="status" aria-live="polite" hidden><?php echo $s->entities1($t['txt_msg_saved']); ?></div>

	<button type="button" class="<?php echo $p; ?>-fab" data-action="prefs" data-icon="<?php echo $s->entities1($design['icon_type']); ?>" aria-label="<?php echo $s->entities1($t['txt_icon_aria']); ?>" <?php echo ($design['icon_show'] || $preview) ? '' : 'data-disabled="1"'; ?> hidden>
		<?php echo $iconSvg; ?>
	</button>

	<template id="<?php echo $p; ?>-ph-tpl">
		<div class="<?php echo $p; ?>-ph">
			<div class="<?php echo $p; ?>-ph-poster" hidden><span class="<?php echo $p; ?>-ph-play" aria-hidden="true"></span></div>
			<div class="<?php echo $p; ?>-ph-inner">
				<p class="<?php echo $p; ?>-ph-msg"><?php echo $s->entities1($t['txt_ph_message']); ?></p>
				<div class="<?php echo $p; ?>-ph-actions">
					<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-secondary" data-ph="load"><?php echo $s->entities1($t['txt_ph_load']); ?></button>
					<button type="button" class="<?php echo $p; ?>-btn <?php echo $p; ?>-btn-primary" data-ph="always"><?php echo $s->entities1($t['txt_ph_always']); ?></button>
				</div>
			</div>
		</div>
	</template>
</div>
