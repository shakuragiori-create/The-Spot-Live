<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a href="#main" class="ts-skip">Skip to content</a>

<nav class="nav" id="nav">
	<div class="nav-logo">
		<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( ts_setting( 'restaurant_name' ) ); ?>">
			</a>
		<?php endif; ?>
	</div>
	<div class="nav-links" id="navLinks">
		<a href="#menu">Menu</a>
		<a href="#about">About</a>
		<a href="#gallery">Gallery</a>
		<a href="#reviews">Reviews</a>
		<a href="#contact" class="nav-cta">Reserve / Contact</a>
		<a href="<?php echo esc_url( home_url( '/panel/login' ) ); ?>" class="nav-cta" style="background:var(--b);color:var(--y);margin-left:4px">Spot System</a>
	</div>
	<button type="button" class="ham" id="hamBtn" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
</nav>

<header class="hero" id="main">
	<div class="hero-bg">
		<div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div>
		<span class="orb" style="--ox:8%;--oy:22%;--d:0s">🥟</span>
		<span class="orb" style="--ox:85%;--oy:18%;--d:2s">🍔</span>
		<span class="orb" style="--ox:14%;--oy:72%;--d:4s">🍕</span>
		<span class="orb" style="--ox:80%;--oy:68%;--d:1s">🍵</span>
	</div>
	<div class="hero-inner">
		<span class="hero-badge"><span class="live"></span><?php echo esc_html( ts_setting( 'hero_badge' ) ); ?></span>
		<h1 class="hero-ttl"><span class="l1">Welcome to</span><span class="l2"><?php echo esc_html( ts_setting( 'restaurant_name' ) ); ?></span></h1>
		<p class="hero-sub"><?php echo esc_html( ts_setting( 'hero_tagline' ) ); ?></p>
		<div class="hero-btns">
			<a href="#menu" class="btn-p">View Menu</a>
			<a href="#contact" class="btn-o">Reserve a Table</a>
		</div>
		<div class="hero-meta">
			<span class="hpill">📍 <?php echo esc_html( ts_setting( 'restaurant_address' ) ); ?></span>
			<span class="hpill">🕒 <?php echo esc_html( ts_today_hours() ); ?></span>
			<?php $wa = ts_whatsapp_url(); if ( $wa ) : ?>
				<a href="<?php echo esc_url( $wa ); ?>" class="hpill" target="_blank" rel="noopener">💬 WhatsApp</a>
			<?php endif; ?>
		</div>
	</div>
	<a href="#menu" class="hero-scroll" aria-label="Scroll down"><span></span></a>
</header>

<div class="strip">
	<div class="strip-track">
		<?php $strip = ts_strip_items(); foreach ( array_merge( $strip, $strip ) as $line ) : ?>
			<span class="strip-item"><?php echo esc_html( $line ); ?></span>
		<?php endforeach; ?>
	</div>
</div>

<section class="sec sec-cream" id="menu">
	<div class="sec-head">
		<div>
			<div class="sec-lbl">Our Menu</div>
			<h2 class="sec-ttl">Something for <em>everyone</em></h2>
			<p class="sec-sub">Browse by category or search for your favourite.</p>
		</div>
	</div>
	<div class="msearch">
		<span class="mi-ico">🔍</span>
		<input type="search" id="mSearch" placeholder="Search the menu…" autocomplete="off">
		<button type="button" class="mclear" id="mClear" aria-label="Clear search">✕</button>
	</div>
	<p class="mcount" id="mCount"></p>
	<?php ts_menu(); ?>
</section>

<section class="sec sec-dark" id="about">
	<div class="about-grid">
		<div class="emoji-grid" data-stagger>
			<div class="ej reveal"><span class="e" style="--d:0s">🥟</span><span class="l">Fresh Momos</span></div>
			<div class="ej reveal"><span class="e" style="--d:1s">🍔</span><span class="l">Juicy Burgers</span></div>
			<div class="ej reveal"><span class="e" style="--d:2s">🍜</span><span class="l">Hot Chowmein</span></div>
			<div class="ej reveal"><span class="e" style="--d:3s">💨</span><span class="l">Hookah Lounge</span></div>
		</div>
		<div class="about-content">
			<div class="sec-lbl">About Us</div>
			<h2 class="sec-ttl">The story behind <em>The Spot</em></h2>
			<?php foreach ( explode( "\n\n", ts_setting( 'about_text' ) ) as $p ) : if ( '' === trim( $p ) ) { continue; } ?>
				<p><?php echo esc_html( $p ); ?></p>
			<?php endforeach; ?>
			<?php $stats = ts_menu_stats(); ?>
			<div class="stats">
				<div class="stat"><div class="num" data-count="<?php echo (int) $stats['items']; ?>">0</div><div class="lbl">Menu Items</div></div>
				<div class="stat"><div class="num" data-count="<?php echo (int) $stats['cats']; ?>">0</div><div class="lbl">Categories</div></div>
				<div class="stat"><div class="num" data-count="<?php echo (int) $stats['min']; ?>" data-prefix="Rs. ">0</div><div class="lbl">Starting From</div></div>
			</div>
		</div>
	</div>
</section>

<section class="sec sec-cream" id="reviews">
	<div class="sec-lbl" style="text-align:center">Reviews</div>
	<h2 class="sec-ttl" style="text-align:center">What guests <em>say</em></h2>
	<?php $revs = ts_testimonials(); ?>
	<div class="google-review-album" id="googleReviewAlbum">
		<div class="google-review-card">
			<div class="google-review-icon" aria-hidden="true">
				<span class="google-g">G</span>
			</div>
			<div class="google-review-kicker">Google Reviews</div>
			<div class="google-stars" aria-label="Five star review icon">★★★★★</div>
			<div class="google-review-title">Loved by our guests</div>
			<p class="google-review-copy">Your experience matters to us. See what guests are saying about The Spot and share your own review.</p>
			<div class="google-rating-row"><strong>4.6</strong><span>/ 5 on Google</span><span class="google-review-count">43 reviews</span></div>
			<div class="google-review-actions">
				<a class="btn-gold" href="https://g.page/r/CaMfHMdfdeT0EBM/review" target="_blank" rel="noopener">★ Give us a 5-Star Review</a>
				<a class="btn-review-link" href="https://g.page/r/CaMfHMdfdeT0EBM/review" target="_blank" rel="noopener">View &amp; write a Google Review ↗</a>
			</div>
		</div>
		<?php if ( $revs ) : ?>
		<div class="rev-wrap rev-album-existing" id="revWrap">
			<div class="rev-view"><div class="rev-track" id="revTrack">
				<?php foreach ( $revs as $r ) : ?>
				<div class="rev">
					<div class="rev-card">
						<div class="rev-stars"><?php echo str_repeat( '★', $r['rating'] ) . str_repeat( '☆', 5 - $r['rating'] ); ?></div>
						<p class="rev-quote">"<?php echo esc_html( $r['quote'] ); ?>"</p>
						<div class="rev-author"><?php echo esc_html( $r['author'] ); ?></div>
					</div>
				</div>
				<?php endforeach; ?>
			</div></div>
			<div class="rev-ctrl">
				<button type="button" class="rev-btn" id="revPrev" aria-label="Previous">‹</button>
				<div class="rev-dots"><?php foreach ( $revs as $i => $r ) : ?><button type="button" class="rev-dot<?php echo 0 === $i ? ' on' : ''; ?>"></button><?php endforeach; ?></div>
				<button type="button" class="rev-btn" id="revNext" aria-label="Next">›</button>
			</div>
		</div>
		<?php endif; ?>
	</div>
</section>

<section class="sec sec-dark" id="gallery">
	<div class="sec-lbl">Gallery</div>
	<h2 class="sec-ttl">A taste of <em>The Spot</em></h2>
	<div class="ggrid">
	<?php
	$photos = ts_gallery();
	if ( $photos ) :
		foreach ( $photos as $p ) :
			?>
			<button type="button" class="gitem" data-full="<?php echo esc_url( $p['full'] ); ?>" data-alt="<?php echo esc_attr( $p['alt'] ); ?>">
				<img src="<?php echo esc_url( $p['thumb'] ); ?>" alt="<?php echo esc_attr( $p['alt'] ); ?>" loading="lazy">
				<span class="gzoom">🔍 View</span>
			</button>
			<?php
		endforeach;
	else :
		foreach ( ts_gallery_placeholders() as $i => $ph ) :
			?>
			<div class="gph"><span class="e" style="--d:<?php echo (int) $i; ?>s"><?php echo esc_html( $ph['icon'] ); ?></span><span class="l"><?php echo esc_html( $ph['label'] ); ?></span><span class="s">Coming Soon</span></div>
			<?php
		endforeach;
	endif;
	?>
	</div>
</section>

<div class="lb" id="lightbox" aria-hidden="true">
	<button type="button" class="lb-close" id="lbClose" aria-label="Close">✕</button>
	<button type="button" class="lb-prev" id="lbPrev" aria-label="Previous">‹</button>
	<img id="lbImg" src="" alt="">
	<button type="button" class="lb-next" id="lbNext" aria-label="Next">›</button>
</div>

<section class="sec sec-cream" id="contact">
	<div class="sec-lbl">Get in Touch</div>
	<h2 class="sec-ttl">Visit or <em>reach out</em></h2>
	<div class="contact-grid">
		<div>
			<div class="c-items">
				<div class="c-item">
					<div class="c-icon">📍</div>
					<div class="c-detail"><strong>Address</strong><span><?php echo esc_html( ts_setting( 'restaurant_address' ) ); ?></span>
					<?php $map = ts_setting( 'map_url' ); if ( $map ) : ?><a href="<?php echo esc_url( $map ); ?>" class="c-link" target="_blank" rel="noopener">Get Directions →</a><?php endif; ?></div>
				</div>
				<div class="c-item"><div class="c-icon">📞</div><div class="c-detail"><strong>Phone</strong><span><?php echo esc_html( ts_phone_display() ); ?></span></div></div>
				<?php $email = ts_setting( 'restaurant_email' ); if ( $email ) : ?>
				<div class="c-item"><div class="c-icon">✉️</div><div class="c-detail"><strong>Email</strong><span><?php echo esc_html( $email ); ?></span></div></div>
				<?php endif; ?>
			</div>
			<div class="call-cta">
				<a href="tel:<?php echo esc_attr( ts_digits( ts_setting( 'restaurant_phone' ) ) ); ?>">📞 Call Now</a>
				<?php $wa = ts_whatsapp_url(); if ( $wa ) : ?><a href="<?php echo esc_url( $wa ); ?>" class="wa" target="_blank" rel="noopener">💬 WhatsApp</a><?php endif; ?>
			</div>
			<div class="hours">
				<?php foreach ( ts_hours_rows() as $row ) : ?>
				<div class="hr<?php echo $row['today'] ? ' today' : ''; ?>"><span class="day"><?php echo esc_html( $row['label'] ); ?></span><span class="time"><?php echo esc_html( $row['time'] ); ?></span></div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="cform">
			<div class="fswitch" role="tablist">
				<button type="button" class="on" data-panel="fReserve" role="tab" aria-selected="true">Reserve a Table</button>
				<button type="button" data-panel="fMessage" role="tab" aria-selected="false">Send a Message</button>
			</div>

			<div class="fpanel on" id="fReserve">
				<form data-rp-action="rp_submit_reservation">
					<input type="text" name="rp_hp" class="fhp" tabindex="-1" autocomplete="off">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'rp_public_nonce' ) ); ?>">
					<div class="fg"><label for="rGuestName">Name</label><input type="text" id="rGuestName" name="guest_name" placeholder="<?php echo esc_attr( ts_setting( 'contact_name_placeholder' ) ); ?>" required></div>
					<div class="fg-row">
						<div class="fg"><label for="rDate">Date</label><input type="date" id="rDate" name="reservation_date" required></div>
						<div class="fg"><label for="rTime">Time</label><input type="time" id="rTime" name="reservation_time" required></div>
					</div>
					<div class="fg-row">
						<div class="fg"><label for="rPhone">Phone</label><input type="tel" id="rPhone" name="phone" placeholder="98XXXXXXXX"></div>
						<div class="fg"><label for="rGuests">Guests</label><input type="number" id="rGuests" name="guests" min="1" value="2"></div>
					</div>
					<div class="fg"><label for="rNotes">Notes</label><textarea id="rNotes" name="notes" placeholder="Any special requests?"></textarea></div>
					<button type="submit" class="fsub">Reserve Table</button>
					<div class="fmsg"></div>
				</form>
			</div>

			<div class="fpanel" id="fMessage">
				<form data-rp-action="rp_submit_enquiry">
					<input type="text" name="rp_hp" class="fhp" tabindex="-1" autocomplete="off">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'rp_public_nonce' ) ); ?>">
					<div class="fg"><label for="mName">Name</label><input type="text" id="mName" name="name" placeholder="<?php echo esc_attr( ts_setting( 'contact_name_placeholder' ) ); ?>" required></div>
					<div class="fg-row">
						<div class="fg"><label for="mPhone">Phone</label><input type="tel" id="mPhone" name="phone" placeholder="Optional"></div>
						<div class="fg"><label for="mEmail">Email</label><input type="email" id="mEmail" name="email" placeholder="Optional"></div>
					</div>
					<div class="fg"><label for="mMessage">Message</label><textarea id="mMessage" name="message" placeholder="How can we help?" required></textarea></div>
					<button type="submit" class="fsub">Send Message</button>
					<div class="fmsg"></div>
					<p class="fnote">Prefer to talk? <a href="tel:<?php echo esc_attr( ts_digits( ts_setting( 'restaurant_phone' ) ) ); ?>">Call us</a> directly.</p>
				</form>
			</div>
		</div>
	</div>
</section>

<footer class="footer">
	<div class="footer-grid">
		<div class="footer-brand">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( ts_setting( 'restaurant_name' ) ); ?>">
			<p><?php echo esc_html( wp_trim_words( ts_setting( 'hero_tagline' ), 22 ) ); ?></p>
			<div class="socials">
				<?php $fb = ts_setting( 'facebook_url' ); if ( $fb ) : ?><a href="<?php echo esc_url( $fb ); ?>" class="soc" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/></svg></a><?php endif; ?>
				<?php $tt = ts_setting( 'tiktok_url' ); if ( $tt ) : ?><a href="<?php echo esc_url( $tt ); ?>" class="soc" target="_blank" rel="noopener" aria-label="TikTok"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a><?php endif; ?>
				<?php $ig = ts_setting( 'instagram_url' ); if ( $ig ) : ?><a href="<?php echo esc_url( $ig ); ?>" class="soc" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077"/></svg></a><?php endif; ?>
			</div>
		</div>
		<div class="fcol"><h4>Explore</h4><ul><li><a href="#menu">Menu</a></li><li><a href="#about">About</a></li><li><a href="#gallery">Gallery</a></li><li><a href="#reviews">Reviews</a></li><li><a href="#contact">Contact</a></li><li><a href="<?php echo esc_url( home_url( '/panel/login' ) ); ?>">Spot System</a></li></ul></div>
		<div class="fcol"><h4>Contact</h4><ul><li><a href="tel:<?php echo esc_attr( ts_digits( ts_setting( 'restaurant_phone' ) ) ); ?>"><?php echo esc_html( ts_phone_display() ); ?></a></li><li><?php echo esc_html( ts_setting( 'restaurant_address' ) ); ?></li></ul></div>
		<div class="fcol"><h4>Hours</h4><div class="fhours"><?php foreach ( ts_hours_rows() as $row ) : ?><div><span><?php echo esc_html( $row['label'] ); ?></span><span><?php echo esc_html( $row['time'] ); ?></span></div><?php endforeach; ?></div></div>
	</div>
	<div class="footer-bottom">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( ts_setting( 'restaurant_name' ) ); ?>. All rights reserved.</p>
		<p>Khairahani-08, Parsa, Chitwan</p>
	</div>
</footer>

<button type="button" class="totop" id="toTop" aria-label="Back to top">↑</button>

<?php wp_footer(); ?>
</body>
</html>
