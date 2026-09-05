<?php
/**
 * Theme data bridge.
 *
 * Every piece of content on the front page is read through the helpers in this
 * file. When the RestaurantPro Manager plugin is active they return live data
 * from the dashboard; when it is not, they return the values the site shipped
 * with, so the public site looks exactly as it did before and can never break.
 *
 * @package The_Spot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the RestaurantPro Manager plugin active and booted?
 */
function ts_plugin_active(): bool {
	static $active = null;

	if ( null === $active ) {
		$active = function_exists( 'rp_manager_get_setting' ) && post_type_exists( 'rp_menu_item' );
	}

	return $active;
}

/**
 * Hard-coded fallbacks — identical to the values the site launched with.
 */
function ts_defaults(): array {
	return [
		'restaurant_name'          => 'The Spot Fast Food and Tea',
		'restaurant_phone'         => '+9779845423522',
		'restaurant_email'         => '',
		'restaurant_address'       => 'Khairahani-08, Parsa, Chitwan',
		'whatsapp_number'          => '9779845423522',
		'facebook_url'             => 'https://www.facebook.com/aakashi.dhungana',
		'tiktok_url'               => 'https://www.tiktok.com/@the.spot.fastfood',
		'instagram_url'            => '',
		'map_url'                  => 'https://maps.app.goo.gl/fZP9an6p6Z25uME18',
		'contact_name_placeholder' => 'Aakash Dhungana',
		'hero_badge'               => '🍔 Open Daily in Khairahani, Chitwan',
		'hero_tagline'             => 'Fast Food & Tea — momos, burgers, chowmein, pizza, hookah & more. Your favourite hangout in Khairahani-08, Parsa, Chitwan.',
		'about_text'               => "The Spot Fast Food and Tea is your go-to destination for delicious, affordable food in Khairahani, Chitwan. From piping hot momos to sizzling chicken dishes, crispy burgers to relaxing tea — we have something for everyone.\n\nWhether you're dropping in for a quick snack, a full meal, or chilling with friends over hookah and milkshakes, The Spot is where memories are made.",
	];
}

/**
 * One setting, plugin first, hard-coded fallback second.
 */
function ts_setting( string $key, string $fallback = '' ): string {
	$value = '';

	if ( ts_plugin_active() ) {
		$raw = rp_manager_get_setting( $key, '' );
		if ( is_scalar( $raw ) ) {
			$value = trim( (string) $raw );
		}
	}

	if ( '' !== $value ) {
		return $value;
	}

	if ( '' !== $fallback ) {
		return $fallback;
	}

	$defaults = ts_defaults();

	return $defaults[ $key ] ?? '';
}

/**
 * Digits only — for tel: and wa.me links.
 */
function ts_digits( string $value ): string {
	return preg_replace( '/[^0-9]/', '', $value );
}

/**
 * WhatsApp link for the shop, or '' when no number is configured.
 */
function ts_whatsapp_url(): string {
	$number = ts_digits( ts_setting( 'whatsapp_number' ) );
	if ( '' === $number ) {
		$number = ts_digits( ts_setting( 'restaurant_phone' ) );
	}

	return $number ? 'https://wa.me/' . $number : '';
}

/**
 * Pretty phone number for display (+977 984 542 3522).
 */
function ts_phone_display(): string {
	$raw    = ts_setting( 'restaurant_phone' );
	$digits = ts_digits( $raw );

	if ( 13 === strlen( $digits ) && str_starts_with( $digits, '977' ) ) {
		return '+977 ' . substr( $digits, 3, 3 ) . ' ' . substr( $digits, 6, 3 ) . ' ' . substr( $digits, 9 );
	}

	return $raw;
}

/**
 * 24h "17:30" -> "5:30 PM". Deliberately timezone-free.
 */
function ts_fmt_time( string $time ): string {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', trim( $time ), $m ) ) {
		return trim( $time );
	}

	$hour   = (int) $m[1];
	$suffix = $hour >= 12 ? 'PM' : 'AM';
	$hour12 = $hour % 12;
	if ( 0 === $hour12 ) {
		$hour12 = 12;
	}

	return $hour12 . ':' . $m[2] . ' ' . $suffix;
}

/**
 * Opening hours per day, week starting Sunday (Nepali week).
 *
 * @return array<string,array{open:string,close:string,closed:bool}>
 */
function ts_hours_raw(): array {
	$days = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];

	$fallback = [];
	foreach ( $days as $day ) {
		$fallback[ $day ] = [
			'open'   => '10:00',
			'close'  => 'saturday' === $day ? '23:00' : '22:00',
			'closed' => false,
		];
	}

	if ( ! ts_plugin_active() || ! class_exists( 'RP_Helpers' ) ) {
		return $fallback;
	}

	$stored = RP_Helpers::get_opening_hours();
	if ( ! is_array( $stored ) || ! $stored ) {
		return $fallback;
	}

	$hours = [];
	foreach ( $days as $day ) {
		$row = $stored[ $day ] ?? [];
		$hours[ $day ] = [
			'open'   => (string) ( $row['open'] ?? $fallback[ $day ]['open'] ),
			'close'  => (string) ( $row['close'] ?? $fallback[ $day ]['close'] ),
			'closed' => ! empty( $row['closed'] ),
		];
	}

	return $hours;
}

/**
 * Opening hours collapsed into readable rows — consecutive days that share the
 * same times are merged ("Sunday – Friday", "Saturday").
 *
 * @return array<int,array{label:string,time:string,today:bool}>
 */
function ts_hours_rows(): array {
	$hours = ts_hours_raw();
	$today = strtolower( wp_date( 'l' ) );
	$rows  = [];
	$group = null;

	foreach ( $hours as $day => $data ) {
		$time = $data['closed'] ? 'Closed' : ts_fmt_time( $data['open'] ) . ' – ' . ts_fmt_time( $data['close'] );

		if ( $group && $group['time'] === $time ) {
			$group['end']   = $day;
			$group['today'] = $group['today'] || ( $day === $today );
			continue;
		}

		if ( $group ) {
			$rows[] = $group;
		}

		$group = [
			'start' => $day,
			'end'   => $day,
			'time'  => $time,
			'today' => ( $day === $today ),
		];
	}

	if ( $group ) {
		$rows[] = $group;
	}

	$out = [];
	foreach ( $rows as $row ) {
		$label = ucfirst( $row['start'] );
		if ( $row['start'] !== $row['end'] ) {
			$label .= ' – ' . ucfirst( $row['end'] );
		}
		$out[] = [
			'label' => $label,
			'time'  => $row['time'],
			'today' => $row['today'],
		];
	}

	return $out;
}

/**
 * Short "open today" line for the hero.
 */
function ts_today_hours(): string {
	$hours = ts_hours_raw();
	$today = strtolower( wp_date( 'l' ) );
	$data  = $hours[ $today ] ?? null;

	if ( ! $data ) {
		return '';
	}

	if ( ! empty( $data['closed'] ) ) {
		return 'Closed today';
	}

	return 'Today ' . ts_fmt_time( $data['open'] ) . ' – ' . ts_fmt_time( $data['close'] );
}

/**
 * Price as it goes inside .mi-price — bare number, because the "Rs. " prefix is
 * added by CSS. Whole numbers stay whole.
 */
function ts_price_num( float $price ): string {
	if ( abs( $price - round( $price ) ) < 0.005 ) {
		return number_format( $price, 0 );
	}

	return number_format( $price, 2 );
}

/**
 * Read the whole menu out of the plugin, grouped for rendering.
 *
 * The result is cached for the request, so the hero strip, the stat counters and
 * the menu itself all share a single database round-trip.
 *
 * @return array<int,array<string,mixed>> Empty array when there is nothing to show.
 */
function ts_menu_data(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$cache = [];

	if ( ! ts_plugin_active() ) {
		return $cache;
	}

	$terms = get_terms(
		[
			'taxonomy'   => 'rp_menu_category',
			'hide_empty' => true,
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $cache;
	}

	// Order by the category's own _rp_order, then alphabetically.
	usort(
		$terms,
		static function ( $a, $b ) {
			$oa = (int) get_term_meta( $a->term_id, '_rp_order', true );
			$ob = (int) get_term_meta( $b->term_id, '_rp_order', true );
			$oa = $oa ?: 9999;
			$ob = $ob ?: 9999;

			return $oa === $ob ? strcasecmp( $a->name, $b->name ) : ( $oa < $ob ? -1 : 1 );
		}
	);

	$cats = [];
	foreach ( $terms as $term ) {
		$icon = (string) get_term_meta( $term->term_id, '_rp_icon', true );
		$head = (string) get_term_meta( $term->term_id, '_rp_heading', true );

		$cats[ $term->term_id ] = [
			'slug'     => $term->slug,
			'name'     => $term->name,
			'icon'     => $icon,
			'heading'  => $head,
			'features' => [],
			'sections' => [],
			'count'    => 0,
		];
	}

	// One query for every item; WP primes the term + meta caches in bulk.
	$items = get_posts(
		[
			'post_type'        => 'rp_menu_item',
			'post_status'      => 'publish',
			'posts_per_page'   => 400,
			'orderby'          => 'menu_order title',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		]
	);

	if ( empty( $items ) ) {
		return $cache;
	}

	foreach ( $items as $item ) {
		if ( '0' === (string) get_post_meta( $item->ID, '_rp_is_available', true ) ) {
			continue;
		}

		$terms_of_item = get_the_terms( $item->ID, 'rp_menu_category' );
		if ( is_wp_error( $terms_of_item ) || empty( $terms_of_item ) ) {
			continue;
		}

		$term_id = 0;
		foreach ( $terms_of_item as $t ) {
			if ( isset( $cats[ $t->term_id ] ) ) {
				$term_id = (int) $t->term_id;
				break;
			}
		}

		if ( ! $term_id ) {
			continue;
		}

		$price = (float) get_post_meta( $item->ID, '_rp_price', true );

		$row = [
			'name'    => $item->post_title,
			'price'   => ts_price_num( $price ),
			'raw'     => $price,
			'veg'     => '1' === (string) get_post_meta( $item->ID, '_rp_is_veg', true ),
			'spicy'   => '1' === (string) get_post_meta( $item->ID, '_rp_is_spicy', true ),
			'popular' => '1' === (string) get_post_meta( $item->ID, '_rp_is_popular', true ),
			'desc'    => trim( wp_strip_all_tags( $item->post_content ) ),
		];

		++$cats[ $term_id ]['count'];

		// An item with a description becomes a full-width feature card
		// (this is how the Rs. 999 Spot Special Platter is presented).
		if ( '' !== $row['desc'] ) {
			$cats[ $term_id ]['features'][] = $row;
			continue;
		}

		$section = (string) get_post_meta( $item->ID, '_rp_section', true );
		$cats[ $term_id ]['sections'][ $section ][] = $row;
	}

	// Drop categories that ended up with nothing visible.
	$cats = array_filter(
		$cats,
		static function ( $cat ) {
			return $cat['count'] > 0;
		}
	);

	$cache = array_values( $cats );

	return $cache;
}

/**
 * Every visible item as a flat list — used by the stat counters and the strip.
 *
 * @return array<int,array<string,mixed>>
 */
function ts_menu_flat(): array {
	static $flat = null;

	if ( null !== $flat ) {
		return $flat;
	}

	$flat = [];
	foreach ( ts_menu_data() as $cat ) {
		foreach ( $cat['features'] as $row ) {
			$flat[] = $row;
		}
		foreach ( $cat['sections'] as $rows ) {
			foreach ( $rows as $row ) {
				$flat[] = $row;
			}
		}
	}

	return $flat;
}

/**
 * Numbers for the About section counters. Falls back to the figures the site
 * shipped with when there is no live menu to count.
 *
 * @return array{items:int,cats:int,min:int}
 */
function ts_menu_stats(): array {
	$items = ts_menu_flat();
	$cats  = ts_menu_data();

	if ( empty( $items ) ) {
		return [
			'items' => 90,
			'cats'  => 16,
			'min'   => 20,
		];
	}

	$prices = [];
	foreach ( $items as $row ) {
		if ( $row['raw'] > 0 ) {
			$prices[] = $row['raw'];
		}
	}

	return [
		'items' => count( $items ),
		'cats'  => count( $cats ),
		'min'   => $prices ? (int) round( min( $prices ) ) : 20,
	];
}

/**
 * Lines for the scrolling strip under the hero — the starred favourites with
 * their live prices, so the strip can never advertise an old price.
 *
 * @return array<int,string>
 */
function ts_strip_items(): array {
	$out = [];

	foreach ( ts_menu_flat() as $row ) {
		if ( empty( $row['popular'] ) ) {
			continue;
		}
		$out[] = '⭐ ' . $row['name'] . ' — Rs. ' . $row['price'];
		if ( count( $out ) >= 6 ) {
			break;
		}
	}

	if ( count( $out ) >= 3 ) {
		return $out;
	}

	return [
		'🥟 Momos from Rs. 100',
		'🍔 Burgers from Rs. 150',
		'🍕 Mini Pizza from Rs. 120',
		'🍵 Tea from Rs. 20',
		'⭐ Spot Platter — Rs. 999',
		'💨 Hookah from Rs. 300',
	];
}

/**
 * Print the menu: tab bar + one panel per category.
 * Falls back to the original static markup if there is no live data.
 */
function ts_menu(): void {
	$cats = ts_menu_data();

	if ( empty( $cats ) ) {
		ts_menu_fallback();
		return;
	}

	echo '<div class="tabs" id="tabs" role="tablist" aria-label="Menu categories">';
	$first = true;
	foreach ( $cats as $cat ) {
		printf(
			'<button class="tab%1$s" data-t="%2$s" role="tab" aria-selected="%3$s">%4$s</button>',
			$first ? ' on' : '',
			esc_attr( $cat['slug'] ),
			$first ? 'true' : 'false',
			esc_html( trim( $cat['icon'] . ' ' . $cat['name'] ) )
		);
		$first = false;
	}
	echo '</div>';

	echo '<div class="cats" id="cats">';

	$first = true;
	foreach ( $cats as $cat ) {
		printf(
			'<div class="cat%1$s" id="t-%2$s" role="tabpanel">',
			$first ? ' on' : '',
			esc_attr( $cat['slug'] )
		);
		$first = false;

		foreach ( $cat['features'] as $feature ) {
			?>
			<div class="platter">
				<div class="platter-txt">
					<div class="platter-name"><?php echo $feature['popular'] ? '⭐ ' : ''; ?><?php echo esc_html( $feature['name'] ); ?></div>
					<div class="platter-desc"><?php echo esc_html( $feature['desc'] ); ?></div>
				</div>
				<div class="platter-price">Rs. <?php echo esc_html( $feature['price'] ); ?><small>Best Value Deal</small></div>
			</div>
			<?php
		}

		$block = 0;
		foreach ( $cat['sections'] as $section => $rows ) {
			$heading = '' !== trim( (string) $section )
				? trim( (string) $section )
				: trim( $cat['icon'] . ' ' . ( '' !== $cat['heading'] ? $cat['heading'] : $cat['name'] ) );

			if ( '' !== $heading ) {
				printf(
					'<div class="cat-ttl%1$s">%2$s</div>',
					( $block > 0 || $cat['features'] ) ? ' mt' : '',
					esc_html( $heading )
				);
			}

			echo '<div class="mgrid">';
			foreach ( $rows as $row ) {
				?>
				<div class="mi<?php echo $row['popular'] ? ' sp' : ''; ?>">
					<span class="mi-name"><?php
						echo $row['popular'] ? '⭐ ' : '';
						echo esc_html( $row['name'] );
						if ( $row['spicy'] ) {
							echo ' <span class="sdot" title="Spicy">🌶</span>';
						}
						if ( $row['veg'] ) {
							echo ' <span class="vdot" title="Vegetarian"></span>';
						}
					?></span>
					<span class="mi-price"><?php echo esc_html( $row['price'] ); ?></span>
				</div>
				<?php
			}
			echo '</div>';
			++$block;
		}

		echo '</div>';
	}

	echo '</div>';
}

/**
 * Customer reviews entered in the dashboard.
 *
 * @return array<int,array{quote:string,author:string,rating:int}>
 */
function ts_testimonials(): array {
	if ( ! ts_plugin_active() ) {
		return [];
	}

	$posts = get_posts(
		[
			'post_type'        => 'rp_testimonial',
			'post_status'      => 'publish',
			'posts_per_page'   => 12,
			'orderby'          => 'menu_order date',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		]
	);

	$out = [];
	foreach ( $posts as $post ) {
		$quote = trim( wp_strip_all_tags( $post->post_content ) );
		if ( '' === $quote ) {
			$quote = trim( wp_strip_all_tags( $post->post_excerpt ) );
		}
		if ( '' === $quote ) {
			continue;
		}

		$author = trim( (string) get_post_meta( $post->ID, '_rp_author_name', true ) );
		$rating = (int) get_post_meta( $post->ID, '_rp_rating', true );

		$out[] = [
			'quote'  => $quote,
			'author' => '' !== $author ? $author : $post->post_title,
			'rating' => max( 1, min( 5, $rating ?: 5 ) ),
		];
	}

	return $out;
}

/**
 * Gallery photos chosen in Settings → Gallery.
 *
 * @return array<int,array{thumb:string,full:string,alt:string}>
 */
function ts_gallery(): array {
	if ( ! ts_plugin_active() ) {
		return [];
	}

	$ids = json_decode( (string) rp_manager_get_setting( 'gallery_images', '[]' ), true );
	if ( ! is_array( $ids ) ) {
		return [];
	}

	$out = [];
	foreach ( $ids as $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			continue;
		}

		$full  = wp_get_attachment_image_url( $id, 'large' );
		$thumb = wp_get_attachment_image_url( $id, 'medium_large' );
		if ( ! $full ) {
			continue;
		}

		$out[] = [
			'thumb' => $thumb ?: $full,
			'full'  => wp_get_attachment_image_url( $id, 'full' ) ?: $full,
			'alt'   => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		];
	}

	return $out;
}

/**
 * Placeholder tiles used until real photos are uploaded — branded, so an empty
 * gallery still looks deliberate.
 *
 * @return array<int,array{icon:string,label:string}>
 */
function ts_gallery_placeholders(): array {
	return [
		[ 'icon' => '🥟', 'label' => 'Steaming momos' ],
		[ 'icon' => '🍔', 'label' => 'Burgers & fries' ],
		[ 'icon' => '🍜', 'label' => 'Spot chowmein' ],
		[ 'icon' => '🍕', 'label' => 'Mini pizza' ],
		[ 'icon' => '🍵', 'label' => 'Tea time' ],
		[ 'icon' => '💨', 'label' => 'Hookah lounge' ],
	];
}
