<?php

/**
 * Cart Page
 *
 * Custom flex layout.
 *
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');

?>

<?php get_template_part('template-parts/page-nav'); ?>

<div class="cart-contents__inner">
	<form
		class="woocommerce-cart-form"
		action="<?php echo esc_url(wc_get_cart_url()); ?>"
		method="post">

		<?php do_action('woocommerce_before_cart_table'); ?>

		<div class="cart-items">

			<?php do_action('woocommerce_before_cart_contents'); ?>

			<?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :

				$_product = apply_filters(
					'woocommerce_cart_item_product',
					$cart_item['data'],
					$cart_item,
					$cart_item_key
				);

				$product_id = apply_filters(
					'woocommerce_cart_item_product_id',
					$cart_item['product_id'],
					$cart_item,
					$cart_item_key
				);

				$visible = apply_filters(
					'woocommerce_cart_item_visible',
					true,
					$cart_item,
					$cart_item_key
				);

				if (
					! $_product instanceof WC_Product ||
					! $_product->exists() ||
					$cart_item['quantity'] <= 0 ||
					! $visible
				) {
					continue;
				}

				$product_name = apply_filters(
					'woocommerce_cart_item_name',
					$_product->get_name(),
					$cart_item,
					$cart_item_key
				);

				$product_permalink = apply_filters(
					'woocommerce_cart_item_permalink',
					$_product->is_visible()
						? $_product->get_permalink($cart_item)
						: '',
					$cart_item,
					$cart_item_key
				);

				$thumbnail = apply_filters(
					'woocommerce_cart_item_thumbnail',
					$_product->get_image(),
					$cart_item,
					$cart_item_key
				);

				/*
     * Количество
     */
				$quantity = (int) $cart_item['quantity'];

				/*
     * Цена за одну единицу
     */
				$unit_price = WC()->cart->get_product_price($_product);

				/*
     * Общая сумма товара с учётом количества
     */
				$subtotal = WC()->cart->get_product_subtotal(
					$_product,
					$quantity
				);

				/*
     * Атрибуты товара
     */
				$attributes = $_product->get_attributes();

			?>

				<div class="cart-item woocommerce-cart-form__cart-item <?php echo esc_attr(
																			apply_filters(
																				'woocommerce_cart_item_class',
																				'cart_item',
																				$cart_item,
																				$cart_item_key
																			)
																		); ?>">

					<!-- 1. ФОТО -->

					<div class="cart-item__image">

						<?php if ($product_permalink) : ?>

							<a href="<?php echo esc_url($product_permalink); ?>">
								<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								?>
							</a>

						<?php else : ?>

							<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
							?>

						<?php endif; ?>

					</div>

					<div class="cart-item__content">
						<!-- 2. ИНФОРМАЦИЯ О ТОВАРЕ -->

						<div class="cart-item__info">

							<div class="cart-item__name">

								<?php if ($product_permalink) : ?>

									<a href="<?php echo esc_url($product_permalink); ?>">
										<?php echo esc_html($product_name); ?>
									</a>

								<?php else : ?>

									<?php echo esc_html($product_name); ?>

								<?php endif; ?>

							</div>


							<?php if ($attributes) : ?>

								<div class="cart-item__attributes">

									<?php
									/*
     * Производитель
     */
									$manufacturer = wc_get_product_terms(
										$product_id,
										'pa_proizoditel',
										[
											'fields' => 'names',
										]
									);

									$manufacturer = !is_wp_error($manufacturer) && !empty($manufacturer)
										? implode(', ', $manufacturer)
										: '—';


									/*
     * Коллекция
     */
									$collection = wc_get_product_terms(
										$product_id,
										'pa_collection',
										[
											'fields' => 'names',
										]
									);

									$collection = !is_wp_error($collection) && !empty($collection)
										? implode(', ', $collection)
										: '—';


									/*
     * Бренд
     *
     * Стандартная таксономия WooCommerce:
     * product_brand
     */
									$brand = wc_get_product_terms(
										$product_id,
										'product_brand',
										[
											'fields' => 'names',
										]
									);

									$brand = !is_wp_error($brand) && !empty($brand)
										? implode(', ', $brand)
										: '—';
									?>

									<div class="cart-item__attribute">

										<span class="cart-item__attribute-name">
											Производитель:
										</span>

										<span class="cart-item__attribute-value">
											<?php echo esc_html($manufacturer); ?>
										</span>

									</div>


									<div class="cart-item__attribute">

										<span class="cart-item__attribute-name">
											Коллекция:
										</span>

										<span class="cart-item__attribute-value">
											<?php echo esc_html($collection); ?>
										</span>

									</div>


									<div class="cart-item__attribute">

										<span class="cart-item__attribute-name">
											Бренд:
										</span>

										<span class="cart-item__attribute-value">
											<?php echo esc_html($brand); ?>
										</span>

									</div>

								</div>

							<?php endif; ?>


							<?php
							do_action(
								'woocommerce_after_cart_item_name',
								$cart_item,
								$cart_item_key
							);
							?>


							<?php
							/*
             * Дополнительные данные вариации / cart item data
             */
							$item_data = wc_get_formatted_cart_item_data($cart_item);

							if ($item_data) {
								echo $item_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>


							<?php if (
								$_product->backorders_require_notification() &&
								$_product->is_on_backorder($quantity)
							) : ?>

								<p class="backorder_notification">
									<?php esc_html_e(
										'Available on backorder',
										'woocommerce'
									); ?>
								</p>

							<?php endif; ?>

						</div>


						<div class="cart-item__content__price">
							<!-- 3. КОЛИЧЕСТВО + ЦЕНА ЗА ШТУКУ -->

							<div class="cart-item__quantity-price">


								<?php
								aura_product_quantity(
									$_product,
									[
										'input_name'  => "cart[{$cart_item_key}][qty]",
										'input_value' => $quantity,
									]
								);
								?>



								<div class="cart-item__unit-price">
									<?php
									echo apply_filters(
										'woocommerce_cart_item_price',
										$unit_price,
										$cart_item,
										$cart_item_key
									);
									?>

									<span class="cart-item__unit">
										/шт.
									</span>

								</div>

							</div>


							<!-- 4. СУММА -->

							<div class="cart-item__subtotal">

								<span class="cart-item__label">
									Сумма:
								</span>

								<?php
								echo apply_filters(
									'woocommerce_cart_item_subtotal',
									$subtotal,
									$cart_item,
									$cart_item_key
								);
								?>

							</div>
						</div>



						<!-- 5. ИЗБРАННОЕ + УДАЛЕНИЕ -->

						<div class="cart-item__actions">

							<div class="cart-item__wishlist">

								<?php custom_add_to_wishlist_button($_product); ?>

							</div>


							<div class="cart-item__remove">

								<?php
								echo apply_filters(
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a
                            role="button"
                            href="%s"
                            class="remove"
                            aria-label="%s"
                            data-product_id="%s"
                            data-product_sku="%s"
                        >
                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8.90464 0C8.32576 0 7.85695 0.457898 7.85695 1.02331V1.53484H1.04768C0.469332 1.53484 0 1.99325 0 2.55815C0 3.12304 0.469332 3.58146 1.04768 3.58146H1.65533L2.98625 19.1851C3.12034 20.7635 4.49719 22 6.11874 22L15.881 21.9997C17.5028 21.9997 18.8791 20.7632 19.0135 19.1848L20.3444 3.5812L20.9523 3.58146C21.5307 3.58146 22 3.12304 22 2.55815C22 1.99325 21.5307 1.53484 20.9523 1.53484H14.1428V1.02331C14.1428 0.457898 13.674 0 13.0951 0H8.90464ZM3.75782 3.58146H18.2432L16.9255 19.0152C16.8804 19.5411 16.4227 19.9534 15.8821 19.9534H6.119C5.57787 19.9534 5.1191 19.5411 5.07449 19.0152L3.75782 3.58146Z" fill="#D4D4D4"/>
</svg>

                        </a>',
										esc_url(
											wc_get_cart_remove_url($cart_item_key)
										),
										esc_attr(
											sprintf(
												__(
													'Remove %s from cart',
													'woocommerce'
												),
												wp_strip_all_tags($product_name)
											)
										),
										esc_attr($product_id),
										esc_attr($_product->get_sku())
									),
									$cart_item_key
								);
								?>

							</div>

						</div>
					</div>




				</div>

			<?php endforeach; ?>

			<?php do_action('woocommerce_cart_contents'); ?>


			<div class="cart-actions">

				<?php if (wc_coupons_enabled()) : ?>

					<div class="coupon">

						<label
							for="coupon_code"
							class="screen-reader-text">
							<?php esc_html_e('Coupon:', 'woocommerce'); ?>
						</label>

						<input
							type="text"
							name="coupon_code"
							class="input-text"
							id="coupon_code"
							value=""
							placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" />

						<button
							type="submit"
							class="button<?php echo esc_attr(
												wc_wp_theme_get_element_class_name('button')
													? ' ' . wc_wp_theme_get_element_class_name('button')
													: ''
											); ?>"
							name="apply_coupon"
							value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
							<?php esc_html_e('Apply coupon', 'woocommerce'); ?>
						</button>

						<?php do_action('woocommerce_cart_coupon'); ?>

					</div>

				<?php endif; ?>


				<button
					type="submit"
					class="button<?php echo esc_attr(
										wc_wp_theme_get_element_class_name('button')
											? ' ' . wc_wp_theme_get_element_class_name('button')
											: ''
									); ?>"
					name="update_cart"
					value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>">
					<?php esc_html_e('Update cart', 'woocommerce'); ?>
				</button>


				<?php do_action('woocommerce_cart_actions'); ?>


				<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>

			</div>


			<?php do_action('woocommerce_after_cart_contents'); ?>

		</div>

		<?php do_action('woocommerce_after_cart_table'); ?>

	</form>


	<?php do_action('woocommerce_before_cart_collaterals');
	?>


	<div class="cart-collaterals">

		<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action('woocommerce_cart_collaterals');
		?>

	</div>

</div><!-- end of cart-contents__inner -->

<?php do_action('woocommerce_after_cart'); ?>