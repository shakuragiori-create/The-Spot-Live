<?php if ( ! defined( 'ABSPATH' ) ) exit;
$gallery_ids = json_decode( RP_Settings::get( 'gallery_images', '[]' ), true ) ?: [];
?>
<div class="wrap rp-wrap">
    <h1 class="mb-4">RestaurantPro Settings</h1>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=general' ) ); ?>">General</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=hours' ) ); ?>">Opening Hours</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=gallery' ) ); ?>">Gallery</a>
        </li>
    </ul>

    <form method="post">
        <?php wp_nonce_field( 'rp_save_settings' ); ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Gallery Images</h5>
                <p class="text-muted">Select images from the Media Library to display on the Gallery page.</p>

                <div id="rp-gallery-preview" class="row g-2 mb-3">
                    <?php foreach ( $gallery_ids as $id ) :
                        $img = wp_get_attachment_image_url( $id, 'thumbnail' );
                        if ( ! $img ) continue;
                    ?>
                        <div class="col-2">
                            <img src="<?php echo esc_url( $img ); ?>" class="img-fluid rounded" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="gallery_images" id="rp-gallery-ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
                <button type="button" class="btn btn-outline-primary" id="rp-gallery-select-btn">Select Images</button>
                <button type="button" class="btn btn-outline-danger" id="rp-gallery-clear-btn">Clear All</button>
            </div>
        </div>
        <button type="submit" name="rp_save_settings" class="btn btn-primary mt-3">Save Gallery</button>
    </form>
</div>

<script>
jQuery(function($) {
    var frame;
    $('#rp-gallery-select-btn').on('click', function(e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
            title: 'Select Gallery Images',
            multiple: true,
            library: { type: 'image' }
        });
        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            var ids = attachments.map(function(a) { return a.id; });
            $('#rp-gallery-ids').val(ids.join(','));
            var html = '';
            attachments.forEach(function(a) {
                var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
                html += '<div class="col-2"><img src="' + url + '" class="img-fluid rounded" alt=""></div>';
            });
            $('#rp-gallery-preview').html(html);
        });
        frame.open();
    });
    $('#rp-gallery-clear-btn').on('click', function() {
        $('#rp-gallery-ids').val('');
        $('#rp-gallery-preview').html('');
    });
});
</script>
