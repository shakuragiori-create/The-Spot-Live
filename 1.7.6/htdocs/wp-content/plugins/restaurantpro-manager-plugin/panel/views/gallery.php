<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( 'admin' !== ( $role ?? '' ) ) { wp_die( 'You do not have permission to manage the gallery.' ); }
$raw = RP_Settings::get( 'gallery_images', '[]' );
$gallery_ids = json_decode( (string) $raw, true );
if ( ! is_array( $gallery_ids ) ) $gallery_ids = [];
$gallery_ids = array_values( array_filter( array_map( 'absint', $gallery_ids ) ) );
?>
<div class="panel-page">
    <div class="page-title">Gallery</div>
    <p class="text-muted" style="margin-top:-12px;margin-bottom:20px">Upload restaurant photos directly from The Spot panel. No WordPress Admin or Media Library visit is required.</p>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="rp-gallery-success">✓ Gallery saved successfully.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="rp-gallery-form">
        <?php wp_nonce_field( 'rp_gallery_action' ); ?>
        <input type="hidden" name="rp_gallery_action" value="save">
        <input type="hidden" name="gallery_images" id="rp-gallery-ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">

        <div class="p-card">
            <h2 class="p-card-title">Add Photos</h2>
            <label class="rp-gallery-drop" for="rp-gallery-files">
                <span class="rp-gallery-camera">📷</span>
                <strong>Choose photos</strong>
                <span>JPG, PNG, WEBP or GIF · up to 10 MB each</span>
                <input type="file" id="rp-gallery-files" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            </label>
            <div id="rp-gallery-selected" class="rp-gallery-selected"></div>
        </div>

        <div class="p-card" style="margin-top:16px">
            <div class="flex-between">
                <div>
                    <h2 class="p-card-title" style="margin-bottom:3px">Your Gallery</h2>
                    <div class="text-sm text-muted">Drag photos to change their order. Remove only removes them from the public gallery.</div>
                </div>
                <button type="button" class="btn btn-outline" id="rp-gallery-clear">Clear All</button>
            </div>

            <div id="rp-gallery-preview" class="rp-gallery-grid">
                <?php foreach ( $gallery_ids as $id ) :
                    $thumb = wp_get_attachment_image_url( $id, 'medium_large' ) ?: wp_get_attachment_image_url( $id, 'medium' );
                    $full  = wp_get_attachment_image_url( $id, 'full' );
                    if ( ! $thumb || ! $full ) continue;
                    $alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
                ?>
                    <div class="rp-gallery-card" draggable="true" data-id="<?php echo esc_attr( $id ); ?>">
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $alt ); ?>">
                        <span class="rp-gallery-drag">↕</span>
                        <button type="button" class="rp-gallery-remove" aria-label="Remove photo">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="rp-gallery-empty" class="rp-gallery-empty"<?php echo $gallery_ids ? ' style="display:none"' : ''; ?>>No photos yet. Choose photos above and click Save Gallery.</div>

            <div class="rp-gallery-actions">
                <button type="submit" class="btn btn-primary">Save Gallery</button>
            </div>
        </div>
    </form>
</div>
<script>
(function(){
    var form=document.getElementById('rp-gallery-form');
    var files=document.getElementById('rp-gallery-files');
    var ids=document.getElementById('rp-gallery-ids');
    var grid=document.getElementById('rp-gallery-preview');
    var empty=document.getElementById('rp-gallery-empty');
    var selected=document.getElementById('rp-gallery-selected');
    if(!form||!grid||!ids)return;
    function sync(){var a=[];grid.querySelectorAll('.rp-gallery-card').forEach(function(c){a.push(c.getAttribute('data-id'));});ids.value=a.join(',');empty.style.display=a.length?'none':'block';}
    function renderSelected(){if(!files||!selected)return;selected.innerHTML='';Array.prototype.forEach.call(files.files,function(f){var s=document.createElement('span');s.textContent='✓ '+f.name;selected.appendChild(s);});}
    if(files)files.addEventListener('change',renderSelected);
    grid.addEventListener('click',function(e){var b=e.target.closest('.rp-gallery-remove');if(b){b.closest('.rp-gallery-card').remove();sync();}});
    document.getElementById('rp-gallery-clear').addEventListener('click',function(){if(confirm('Remove all photos from the public gallery?')){grid.innerHTML='';sync();}});
    var drag=null;
    grid.addEventListener('dragstart',function(e){var c=e.target.closest('.rp-gallery-card');if(c){drag=c;c.classList.add('dragging');e.dataTransfer.effectAllowed='move';}});
    grid.addEventListener('dragend',function(){if(drag)drag.classList.remove('dragging');drag=null;sync();});
    grid.addEventListener('dragover',function(e){e.preventDefault();var c=e.target.closest('.rp-gallery-card');if(!drag||!c||c===drag)return;var r=c.getBoundingClientRect();var before=e.clientY<r.top+r.height/2;if(before)grid.insertBefore(drag,c);else grid.insertBefore(drag,c.nextSibling);});
    form.addEventListener('submit',function(){sync();});
})();
</script>
