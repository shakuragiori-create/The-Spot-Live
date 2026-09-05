<?php
/**
 * Static menu fallback — this is the menu exactly as it was hardcoded in the
 * theme before the RestaurantPro plugin took over.
 *
 * It is used ONLY when the plugin is inactive or has zero menu items, so the
 * public site can never end up with an empty menu. Once the plugin is active
 * and the menu has been imported, ts_menu() renders from the database instead
 * and this file is never touched.
 *
 * @package The_Spot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ts_menu_fallback' ) ) :
/**
 * Print the tab bar and every category panel (static copy).
 */
function ts_menu_fallback(): void {
	?>
	<div class="tabs" id="tabs" role="tablist" aria-label="Menu categories">
		<button class="tab on" data-t="burgers">🍔 Burgers</button>
		<button class="tab" data-t="chicken">🍗 Chicken</button>
		<button class="tab" data-t="vegmomo">🥦 Veg &amp; Momo</button>
		<button class="tab" data-t="cmomo">🥟 Chicken Momo</button>
		<button class="tab" data-t="chow">🍜 Chowmein</button>
		<button class="tab" data-t="pizza">🍕 Mini Pizza</button>
		<button class="tab" data-t="rice">🍚 Fried Rice</button>
		<button class="tab" data-t="rolls">🌯 Rolls</button>
		<button class="tab" data-t="paratha">🫓 Paratha</button>
		<button class="tab" data-t="snacks">🌽 Snacks</button>
		<button class="tab" data-t="noodles">🍝 Noodles</button>
		<button class="tab" data-t="thukpa">🍲 Thukpa</button>
		<button class="tab" data-t="tacos">🌮 Tacos &amp; Salad</button>
		<button class="tab" data-t="soups">🥣 Soups</button>
		<button class="tab" data-t="drinks">🥤 Drinks</button>
		<button class="tab" data-t="platter">⭐ Platter</button>
	</div>

	<div class="cats" id="cats">

		<!-- BURGERS -->
		<div class="cat on" id="t-burgers">
			<div class="cat-ttl">🍔 Burgers</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Burger <span class="vdot"></span></span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Chicken Burger</span><span class="mi-price">200</span></div>
				<div class="mi"><span class="mi-name">French Fries <span class="vdot"></span></span><span class="mi-price">120</span></div>
			</div>
		</div>

		<!-- CHICKEN -->
		<div class="cat" id="t-chicken">
			<div class="cat-ttl">🍗 Chicken</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Chicken Taas Khaja</span><span class="mi-price">280</span></div>
				<div class="mi"><span class="mi-name">Hot Chicken Lollipop</span><span class="mi-price">200</span></div>
				<div class="mi"><span class="mi-name">Hot Wings</span><span class="mi-price">200</span></div>
				<div class="mi"><span class="mi-name">Sausage Chilly</span><span class="mi-price">180</span></div>
				<div class="mi"><span class="mi-name">Chicken Chilly</span><span class="mi-price">220</span></div>
				<div class="mi"><span class="mi-name">Dragon Chicken</span><span class="mi-price">250</span></div>
				<div class="mi"><span class="mi-name">Chicken Lollipop</span><span class="mi-price">180</span></div>
				<div class="mi"><span class="mi-name">Chicken Roast</span><span class="mi-price">250</span></div>
				<div class="mi"><span class="mi-name">Chicken 65</span><span class="mi-price">250</span></div>
			</div>
		</div>

		<!-- VEG & MOMO -->
		<div class="cat" id="t-vegmomo">
			<div class="cat-ttl">🥦 Veg</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Momo (Steam) <span class="vdot"></span></span><span class="mi-price">100</span></div>
				<div class="mi"><span class="mi-name">Veg Momo (Fry) <span class="vdot"></span></span><span class="mi-price">110</span></div>
				<div class="mi"><span class="mi-name">Veg Momo (Kothey) <span class="vdot"></span></span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Veg Momo (C-Momo) <span class="vdot"></span></span><span class="mi-price">130</span></div>
				<div class="mi"><span class="mi-name">Veg Momo (Jhol) <span class="vdot"></span></span><span class="mi-price">130</span></div>
				<div class="mi"><span class="mi-name">Veg Momo (Crunchy) <span class="vdot"></span></span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Veg Khaja Set <span class="vdot"></span></span><span class="mi-price">200</span></div>
			</div>
		</div>

		<!-- CHICKEN MOMO -->
		<div class="cat" id="t-cmomo">
			<div class="cat-ttl">🥟 Chicken Momo</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Chicken Momo (Steam)</span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Chicken Momo (Fry)</span><span class="mi-price">130</span></div>
				<div class="mi"><span class="mi-name">Chicken Momo (Kothey)</span><span class="mi-price">140</span></div>
				<div class="mi"><span class="mi-name">Chicken Momo (C-Momo)</span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Chicken Momo (Jhol)</span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Chicken Momo (Crunchy)</span><span class="mi-price">190</span></div>
			</div>
		</div>

		<!-- CHOWMEIN -->
		<div class="cat" id="t-chow">
			<div class="cat-ttl">🍜 Chowmein</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Chowmein <span class="vdot"></span></span><span class="mi-price">100</span></div>
				<div class="mi"><span class="mi-name">Egg Chowmein</span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Chicken Chowmein</span><span class="mi-price">130</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Chowmein</span><span class="mi-price">180</span></div>
				<div class="mi"><span class="mi-name">Kima Noodles</span><span class="mi-price">180</span></div>
			</div>
		</div>

		<!-- MINI PIZZA -->
		<div class="cat" id="t-pizza">
			<div class="cat-ttl">🍕 Mini Pizza</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Cheese Pizza <span class="vdot"></span></span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Veg Pizza <span class="vdot"></span></span><span class="mi-price">140</span></div>
				<div class="mi"><span class="mi-name">Chicken Pizza</span><span class="mi-price">160</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Pizza</span><span class="mi-price">350</span></div>
			</div>
		</div>

		<!-- FRIED RICE -->
		<div class="cat" id="t-rice">
			<div class="cat-ttl">🍚 Fried Rice</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Fried Rice <span class="vdot"></span></span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Egg Fried Rice</span><span class="mi-price">140</span></div>
				<div class="mi"><span class="mi-name">Chicken Fried Rice</span><span class="mi-price">160</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Fried Rice</span><span class="mi-price">200</span></div>
			</div>
		</div>

		<!-- ROLLS -->
		<div class="cat" id="t-rolls">
			<div class="cat-ttl">🌯 Rolls</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Rolls <span class="vdot"></span></span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Chicken Rolls</span><span class="mi-price">180</span></div>
				<div class="mi"><span class="mi-name">Spring Rolls <span class="vdot"></span></span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Chrispy Cheese Chicken Rolls</span><span class="mi-price">200</span></div>
			</div>
		</div>

		<!-- PARATHA -->
		<div class="cat" id="t-paratha">
			<div class="cat-ttl">🫓 Paratha</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Aalu Paratha <span class="vdot"></span></span><span class="mi-price">60</span></div>
				<div class="mi"><span class="mi-name">Paneer Paratha <span class="vdot"></span></span><span class="mi-price">100</span></div>
				<div class="mi"><span class="mi-name">Chicken Cheese Paratha</span><span class="mi-price">120</span></div>
			</div>
		</div>

		<!-- SNACKS -->
		<div class="cat" id="t-snacks">
			<div class="cat-ttl">🌽 Corns, Pakauda &amp; More</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Sweet Corns <span class="vdot"></span></span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Crispy Sweet Corns <span class="vdot"></span></span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Veg Pakauda <span class="vdot"></span></span><span class="mi-price">100</span></div>
				<div class="mi"><span class="mi-name">Chicken Pakauda</span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Chicken Sausage (Per Piece)</span><span class="mi-price">50</span></div>
				<div class="mi"><span class="mi-name">Syafale (Per Piece)</span><span class="mi-price">60</span></div>
				<div class="mi"><span class="mi-name">Corn Dog</span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Cheese Corn Dog</span><span class="mi-price">200</span></div>
				<div class="mi"><span class="mi-name">Potato Cheese Balls <span class="vdot"></span></span><span class="mi-price">220</span></div>
				<div class="mi"><span class="mi-name">Chips Chilly <span class="vdot"></span></span><span class="mi-price">160</span></div>
			</div>
		</div>

		<!-- NOODLES / CURRENT -->
		<div class="cat" id="t-noodles">
			<div class="cat-ttl">🍝 Current Time (Instant Noodles)</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Current <span class="vdot"></span></span><span class="mi-price">80</span></div>
				<div class="mi"><span class="mi-name">Current with Egg</span><span class="mi-price">100</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Current</span><span class="mi-price">150</span></div>
			</div>
		</div>

		<!-- THUKPA -->
		<div class="cat" id="t-thukpa">
			<div class="cat-ttl">🍲 Thukpa</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Veg Thukpa <span class="vdot"></span></span><span class="mi-price">140</span></div>
				<div class="mi"><span class="mi-name">Chicken Thukpa</span><span class="mi-price">160</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Thukpa</span><span class="mi-price">200</span></div>
			</div>
		</div>

		<!-- TACOS & SALAD -->
		<div class="cat" id="t-tacos">
			<div class="cat-ttl">🌮 Nepali Tacos &amp; Salad</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Paneer Cheese Tacos <span class="vdot"></span></span><span class="mi-price">180</span></div>
				<div class="mi"><span class="mi-name">Chicken Cheese Tacos</span><span class="mi-price">200</span></div>
				<div class="mi"><span class="mi-name">Green Salad <span class="vdot"></span></span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Fruit Salad <span class="vdot"></span></span><span class="mi-price">200</span></div>
			</div>
		</div>

		<!-- SOUPS -->
		<div class="cat" id="t-soups">
			<div class="cat-ttl">🥣 Soups</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Chicken Soup</span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Veg Soup <span class="vdot"></span></span><span class="mi-price">100</span></div>
				<div class="mi"><span class="mi-name">Mushroom Soup <span class="vdot"></span></span><span class="mi-price">110</span></div>
			</div>
		</div>

		<!-- DRINKS -->
		<div class="cat" id="t-drinks">
			<div class="cat-ttl">🍵 Tea</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Black Tea</span><span class="mi-price">20</span></div>
				<div class="mi"><span class="mi-name">Lemon Tea</span><span class="mi-price">20</span></div>
				<div class="mi"><span class="mi-name">Milk Tea</span><span class="mi-price">25</span></div>
				<div class="mi sp"><span class="mi-name">⭐ Spot Special Tea</span><span class="mi-price">35</span></div>
				<div class="mi"><span class="mi-name">Matka Tea</span><span class="mi-price">50</span></div>
			</div>
			<div class="cat-ttl mt">☕ Coffee</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Black Coffee</span><span class="mi-price">50</span></div>
				<div class="mi"><span class="mi-name">Milk Coffee</span><span class="mi-price">80</span></div>
			</div>
			<div class="cat-ttl mt">🥛 Milk Shakes &amp; Lassi</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Banana Milk Shake</span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Chocolate Milk Shake</span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Strawberry Milk Shake</span><span class="mi-price">160</span></div>
				<div class="mi"><span class="mi-name">Vanilla Milk Shake</span><span class="mi-price">170</span></div>
				<div class="mi"><span class="mi-name">Plain Lassi</span><span class="mi-price">80</span></div>
				<div class="mi"><span class="mi-name">Sweet Lassi</span><span class="mi-price">80</span></div>
				<div class="mi"><span class="mi-name">Banana Lassi</span><span class="mi-price">100</span></div>
			</div>
			<div class="cat-ttl mt">🥤 Cold Drinks</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Coke / Fanta / Sprite</span><span class="mi-price">60</span></div>
				<div class="mi"><span class="mi-name">Masala Sprite</span><span class="mi-price">80</span></div>
				<div class="mi"><span class="mi-name">Masala Soda</span><span class="mi-price">80</span></div>
				<div class="mi"><span class="mi-name">Mint Lemonade</span><span class="mi-price">120</span></div>
				<div class="mi"><span class="mi-name">Hot Chocolate</span><span class="mi-price">140</span></div>
				<div class="mi"><span class="mi-name">Virjin Mojito</span><span class="mi-price">150</span></div>
				<div class="mi"><span class="mi-name">Blue Lagoon</span><span class="mi-price">180</span></div>
			</div>
		</div>

		<!-- PLATTER & HOOKAH -->
		<div class="cat" id="t-platter">
			<div class="platter">
				<div class="platter-txt">
					<div class="platter-name">⭐ Spot Special Platter</div>
					<div class="platter-desc">Lollipop 3pc &bull; Hot Wings 2pc &bull; Pizza &bull; Kothey Momo 4pc &bull; Chicken C-Momo 4pc &bull; French Fries &bull; Chicken Noodles &bull; Salad</div>
				</div>
				<div class="platter-price">Rs. 999<small>Best Value Deal</small></div>
			</div>
			<div class="cat-ttl">💨 Hookah</div>
			<div class="mgrid">
				<div class="mi"><span class="mi-name">Hookah (Mint)</span><span class="mi-price">300</span></div>
				<div class="mi"><span class="mi-name">Hookah (Lady Killer)</span><span class="mi-price">400</span></div>
				<div class="mi"><span class="mi-name">Hookah Extra Coal</span><span class="mi-price">50</span></div>
			</div>
		</div>

	</div>
	<?php
}
endif;
