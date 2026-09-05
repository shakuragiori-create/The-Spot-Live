<?php if ( ! defined( 'ABSPATH' ) ) exit;
$tab = sanitize_text_field( $_GET['tab'] ?? 'general' );
$settings = RP_Settings::get_all();
?>
<div class="wrap rp-wrap">
    <h1 class="mb-4">RestaurantPro Settings</h1>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'general' ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=general' ) ); ?>">General</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'hours' ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=hours' ) ); ?>">Opening Hours</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'gallery' ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=gallery' ) ); ?>">Gallery</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'billing' ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=billing' ) ); ?>">Billing &amp; Tax</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'tools' ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=tools' ) ); ?>">Tools</a>
        </li>
    </ul>

    <?php if ( $tab === 'general' ) : ?>
        <form method="post">
            <?php wp_nonce_field( 'rp_save_settings' ); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Restaurant Name</label>
                            <input type="text" class="form-control" name="restaurant_name" value="<?php echo esc_attr( $settings['restaurant_name'] ?? '' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="restaurant_phone" value="<?php echo esc_attr( $settings['restaurant_phone'] ?? '' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="restaurant_email" value="<?php echo esc_attr( $settings['restaurant_email'] ?? '' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" name="currency_symbol" value="<?php echo esc_attr( $settings['currency_symbol'] ?? 'Rs.' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Position</label>
                            <select class="form-select" name="currency_position">
                                <option value="before" <?php selected( $settings['currency_position'] ?? 'before', 'before' ); ?>>Before (Rs. 100)</option>
                                <option value="after" <?php selected( $settings['currency_position'] ?? 'before', 'after' ); ?>>After (100 Rs.)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="restaurant_address" rows="2"><?php echo esc_textarea( $settings['restaurant_address'] ?? '' ); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Form — Default Name Placeholder</label>
                            <input type="text" class="form-control" name="contact_name_placeholder" value="<?php echo esc_attr( $settings['contact_name_placeholder'] ?? 'Aakash Dhungana' ); ?>">
                            <small class="text-muted">Shown as the greyed-out example in the website's "Your Name" field.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" class="form-control" name="whatsapp_number" value="<?php echo esc_attr( $settings['whatsapp_number'] ?? '' ); ?>">
                            <small class="text-muted">Country code, no + or spaces — e.g. <code>9779845423522</code>. Leave empty to use the phone number above.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="mb-1">Website Links</h5>
                    <p class="text-muted small mb-3">Leave a box empty to hide that link on the website.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Facebook Page URL</label>
                            <input type="url" class="form-control" name="facebook_url" value="<?php echo esc_attr( $settings['facebook_url'] ?? '' ); ?>" placeholder="https://www.facebook.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TikTok URL</label>
                            <input type="url" class="form-control" name="tiktok_url" value="<?php echo esc_attr( $settings['tiktok_url'] ?? '' ); ?>" placeholder="https://www.tiktok.com/@...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Instagram URL</label>
                            <input type="url" class="form-control" name="instagram_url" value="<?php echo esc_attr( $settings['instagram_url'] ?? '' ); ?>" placeholder="https://www.instagram.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Maps Link</label>
                            <input type="url" class="form-control" name="map_url" value="<?php echo esc_attr( $settings['map_url'] ?? '' ); ?>" placeholder="https://maps.app.goo.gl/...">
                            <small class="text-muted">Used by the "Get directions" links.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="mb-1">Website Text</h5>
                    <p class="text-muted small mb-3">The wording on the homepage. Changes appear as soon as you save (clear the site cache if you don't see them right away).</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Top Banner Line</label>
                            <input type="text" class="form-control" name="hero_badge" value="<?php echo esc_attr( $settings['hero_badge'] ?? '' ); ?>">
                            <small class="text-muted">The small pill above the big "the Spot" title.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Homepage Tagline</label>
                            <textarea class="form-control" name="hero_tagline" rows="2"><?php echo esc_textarea( $settings['hero_tagline'] ?? '' ); ?></textarea>
                            <small class="text-muted">One or two sentences under the title. Also used as the site's search-engine description.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">About Us Text</label>
                            <textarea class="form-control" name="about_text" rows="6"><?php echo esc_textarea( $settings['about_text'] ?? '' ); ?></textarea>
                            <small class="text-muted">Leave a blank line between paragraphs.</small>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" name="rp_save_settings" class="btn btn-primary mt-3">Save Settings</button>
        </form>
    <?php endif; ?>

    <?php if ( $tab === 'hours' ) : ?>
        <?php $hours = json_decode( $settings['opening_hours'] ?? '{}', true ) ?: []; ?>
        <form method="post">
            <?php wp_nonce_field( 'rp_save_settings' ); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php
                    $days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
                    foreach ( $days as $day ) :
                        $d = $hours[ $day ] ?? [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ];
                    ?>
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-md-2"><strong><?php echo esc_html( ucfirst( $day ) ); ?></strong></div>
                            <div class="col-md-3">
                                <input type="time" class="form-control form-control-sm" name="hours_<?php echo esc_attr( $day ); ?>_open" value="<?php echo esc_attr( $d['open'] ); ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control form-control-sm" name="hours_<?php echo esc_attr( $day ); ?>_close" value="<?php echo esc_attr( $d['close'] ); ?>">
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="hours_<?php echo esc_attr( $day ); ?>_closed" <?php checked( ! empty( $d['closed'] ) ); ?>>
                                    <label class="form-check-label">Closed</label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" name="rp_save_settings" class="btn btn-primary mt-3">Save Hours</button>
        </form>
    <?php endif; ?>

    <?php if ( $tab === 'billing' ) : ?>
        <form method="post">
            <?php wp_nonce_field( 'rp_save_settings' ); ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="vat_enabled" id="vat_enabled" value="1" <?php checked( ( $settings['vat_enabled'] ?? '0' ), '1' ); ?>>
                                <label class="form-check-label" for="vat_enabled">Charge VAT on bills</label>
                            </div>
                            <label class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" name="vat_rate" min="0" max="100" step="0.01" value="<?php echo esc_attr( $settings['vat_rate'] ?? '13' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="service_enabled" id="service_enabled" value="1" <?php checked( ( $settings['service_enabled'] ?? '0' ), '1' ); ?>>
                                <label class="form-check-label" for="service_enabled">Charge Service Charge on bills</label>
                            </div>
                            <label class="form-label">Service Charge (%)</label>
                            <input type="number" class="form-control" name="service_rate" min="0" max="100" step="0.01" value="<?php echo esc_attr( $settings['service_rate'] ?? '10' ); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number Prefix</label>
                            <input type="text" class="form-control" name="invoice_prefix" value="<?php echo esc_attr( $settings['invoice_prefix'] ?? 'INV-' ); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bill Footer Note</label>
                            <textarea class="form-control" name="bill_footer_note" rows="2"><?php echo esc_textarea( $settings['bill_footer_note'] ?? '' ); ?></textarea>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">These are the defaults applied at the POS. The cashier can still toggle VAT / service charge and adjust amounts on each individual bill before printing.</p>
                </div>
            </div>
            <button type="submit" name="rp_save_settings" class="btn btn-primary mt-3">Save Billing Settings</button>
        </form>
    <?php endif; ?>

    <?php if ( $tab === 'tools' ) : ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-2">Import The Spot Menu</h5>
                <p class="text-muted mb-3">Creates all menu categories and the full item list (with prices, vegetarian &amp; popular flags). Safe to run more than once — items that already exist are skipped, so nothing is duplicated or overwritten.</p>
                <button type="button" id="rp-seed-btn" class="btn btn-primary">Import / Sync Menu</button>
                <div id="rp-seed-progress" class="progress mt-3" style="display:none;height:22px">
                    <div id="rp-seed-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
                </div>
                <p id="rp-seed-status" class="mt-2 mb-0 small text-muted"></p>
            </div>
        </div>
        <script>
        (function(){
            var btn=document.getElementById('rp-seed-btn');
            if(!btn){return;}
            var ajaxUrl=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            var nonce=<?php echo wp_json_encode( wp_create_nonce( 'rp_admin_nonce' ) ); ?>;
            var prog=document.getElementById('rp-seed-progress');
            var bar=document.getElementById('rp-seed-bar');
            var statusEl=document.getElementById('rp-seed-status');
            var created=0, skipped=0;
            function step(offset){
                var body=new URLSearchParams();
                body.set('action','rp_seed_menu');
                body.set('nonce',nonce);
                body.set('offset',offset);
                fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
                .then(function(r){return r.json();})
                .then(function(res){
                    if(!res||!res.success){throw new Error((res&&res.data&&res.data.message)||'Import failed');}
                    var d=res.data;
                    created+=d.created; skipped+=d.skipped;
                    var pct=d.total?Math.round(d.processed/d.total*100):100;
                    bar.style.width=pct+'%'; bar.textContent=pct+'%';
                    statusEl.textContent='Processed '+d.processed+' of '+d.total+' — '+created+' added, '+skipped+' already existed.';
                    if(d.done){
                        bar.classList.remove('progress-bar-animated');
                        statusEl.textContent='✅ Done! '+created+' items added, '+skipped+' already existed ('+d.total+' items total).';
                        btn.disabled=false; btn.textContent='Import / Sync Menu';
                    }else{
                        step(d.processed);
                    }
                })
                .catch(function(err){
                    statusEl.textContent='⚠️ '+err.message+'. Please try again.';
                    btn.disabled=false; btn.textContent='Import / Sync Menu';
                });
            }
            btn.addEventListener('click',function(){
                btn.disabled=true; btn.textContent='Importing…';
                created=0; skipped=0; prog.style.display='';
                bar.classList.add('progress-bar-animated');
                step(0);
            });
        })();
        </script>
    <?php endif; ?>
</div>
