<?php

/**
 * Single Product Image — Swiper gallery.
 *
 * @package WooCommerce
 */

use Automattic\WooCommerce\Enums\ProductType;

defined('ABSPATH') || exit;

if (! function_exists('wc_get_gallery_image_html')) {
	return;
}

global $product;

if (! $product instanceof WC_Product) {
	return;
}

$main_image_id = $product->get_image_id();
$gallery_ids   = $product->get_gallery_image_ids();

$image_ids = array_values(
	array_unique(
		array_filter(
			array_merge(
				$main_image_id ? array($main_image_id) : array(),
				$gallery_ids
			)
		)
	)
);

$columns = apply_filters('woocommerce_product_thumbnails_columns', 4);

$wrapper_classes = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ($main_image_id ? 'with-images' : 'without-images'),
		'woocommerce-product-gallery--columns-' . absint($columns),
		'images',
	)
);
?>

<div
	class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?> aura-product-gallery"
	data-columns="<?php echo esc_attr($columns); ?>">
	<?php if ($image_ids) : ?>
		<button
			class="slider-next"
			type="button"
			aria-label="<?php esc_attr_e('Next image', 'woocommerce'); ?>">
			<svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M2.75273 0.107826L0.112351 2.70776C-0.0365624 2.85592 -0.0373183 3.09479 0.109327 3.24219L14.148 17.3886L0.128603 31.6211C-0.0150191 31.7674 -0.015775 32.0029 0.128603 32.1495L2.72967 34.7926C2.87708 34.9392 3.11594 34.9384 3.26259 34.791L20.2625 17.7087C20.4084 17.5621 20.4088 17.3243 20.2625 17.1773L3.28602 0.112739C3.13824 -0.0357963 2.90051 -0.037686 2.75273 0.107826Z" fill="#1D1D1F" />
			</svg>

		</button>

		<div class="swiper aura-product-gallery__main">

			<div class="swiper-wrapper">

				<?php foreach ($image_ids as $index => $image_id) : ?>
					<div class="swiper-slide">
						<?php
						$html = wc_get_gallery_image_html($image_id, $index === 0);

						echo apply_filters(
							'woocommerce_single_product_image_thumbnail_html',
							$html,
							$image_id
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				<?php endforeach; ?>

			</div>
		</div>

		<button
			class="slider-prev"
			type="button"
			aria-label="<?php esc_attr_e('Previous image', 'woocommerce'); ?>">
			<svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M17.6194 0.107826L20.2597 2.70776C20.4086 2.85592 20.4094 3.09479 20.2628 3.24219L6.22408 17.3886L20.2435 31.6211C20.3871 31.7674 20.3879 32.0029 20.2435 32.1495L17.6424 34.7926C17.495 34.9392 17.2561 34.9384 17.1095 34.791L0.109559 17.7087C-0.0363307 17.5621 -0.0367086 17.3243 0.109559 17.1773L17.0861 0.112739C17.2338 -0.0357963 17.4716 -0.037686 17.6194 0.107826Z" fill="#1D1D1F" />
			</svg>

		</button>

		<?php if (count($image_ids) > 1) : ?>
			<div class="swiper aura-product-gallery__thumbs">
				<div class="swiper-wrapper">

					<?php foreach ($image_ids as $index => $image_id) : ?>
						<div class="swiper-slide">
							<?php
							echo wp_get_attachment_image(
								$image_id,
								'woocommerce_gallery_thumbnail',
								false,
								array(
									'class' => 'aura-product-gallery__thumb',
									'alt'   => sprintf(
										/* translators: %d: image number */
										esc_attr__('Product image %d', 'woocommerce'),
										$index + 1
									),
								)
							);
							?>
						</div>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<div class="aura-product-gallery__placeholder">
			<?php
			echo wp_kses_post(
				wc_placeholder_img('woocommerce_single')
			);
			?>
		</div>

	<?php endif; ?>
</div>