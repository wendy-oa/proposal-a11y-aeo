<?php
/**
 * Schema Meta Boxes — FAQ Items & Defined Terms
 *
 * Registers two meta boxes in the post editor so content editors can attach
 * FAQ and DefinedTerm data to any article, news post, or guide without
 * touching code.  OA_Schema reads this data at render time.
 *
 * Meta box locations in the editor:
 *   "Schema: FAQ Items"      — normal / default priority
 *   "Schema: Defined Terms"  — normal / default priority
 *
 * Post-meta keys are defined in config.php:
 *   OA_SCHEMA_META_FAQ   → '_schema_faq_items'
 *   OA_SCHEMA_META_TERMS → '_schema_defined_terms'
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Register post-meta (allows get/update_post_meta to work reliably).
// ---------------------------------------------------------------------------

add_action( 'init', static function (): void {
    $shared_args = [
        'single'        => true,
        'show_in_rest'  => false, // raw arrays; exposed via REST only if needed
        'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
    ];

    register_post_meta( '', OA_SCHEMA_META_FAQ,   $shared_args + [ 'type' => 'array' ] );
    register_post_meta( '', OA_SCHEMA_META_TERMS, $shared_args + [ 'type' => 'array' ] );
} );

// ---------------------------------------------------------------------------
// Add meta boxes to relevant post types.
// ---------------------------------------------------------------------------

add_action( 'add_meta_boxes', static function (): void {
    // List every post type that can carry schema data.
    // Mirror the keys in OA_SCHEMA_POST_TYPE_MAP.
    $screens = array_keys( OA_SCHEMA_POST_TYPE_MAP );

    add_meta_box(
        'oa_schema_faq',
        'Schema: FAQ Items',
        'oa_schema_faq_meta_box_render',
        $screens,
        'normal',
        'default'
    );

    add_meta_box(
        'oa_schema_defined_terms',
        'Schema: Defined Terms',
        'oa_schema_terms_meta_box_render',
        $screens,
        'normal',
        'default'
    );
} );

// ---------------------------------------------------------------------------
// Render callbacks
// ---------------------------------------------------------------------------

/**
 * Renders the FAQ meta box.
 *
 * @param WP_Post $post
 */
function oa_schema_faq_meta_box_render( WP_Post $post ): void {
    wp_nonce_field( 'oa_schema_faq_save', 'oa_schema_faq_nonce' );

    $items = get_post_meta( $post->ID, OA_SCHEMA_META_FAQ, true );
    if ( ! is_array( $items ) || empty( $items ) ) {
        $items = [ [ 'question' => '', 'answer' => '' ] ];
    }
    ?>
    <p style="color:#666;margin-top:0">
        Add FAQ pairs to generate a <code>FAQPage</code> schema node.
        Leave blank to omit the FAQPage from the schema output entirely.
    </p>

    <div id="oa-faq-wrapper">
        <?php foreach ( $items as $i => $item ) :
            $q = esc_attr( $item['question'] ?? '' );
            $a = esc_textarea( $item['answer'] ?? '' );
            ?>
        <div class="oa-faq-row" style="margin-bottom:10px;border:1px solid #ddd;padding:10px 12px;background:#fafafa;border-radius:3px">
            <p style="margin:0 0 6px">
                <label>
                    <strong>Q<?php echo $i + 1; ?>: Question</strong><br>
                    <input type="text"
                           name="oa_faq[<?php echo $i; ?>][question]"
                           value="<?php echo $q; ?>"
                           placeholder="e.g. Can you live on $300/month in the Philippines?"
                           style="width:100%;margin-top:4px">
                </label>
            </p>
            <p style="margin:0 0 6px">
                <label>
                    <strong>Answer</strong><br>
                    <textarea name="oa_faq[<?php echo $i; ?>][answer]"
                              rows="3"
                              placeholder="Plain text or basic HTML. Avoid block-level tags."
                              style="width:100%;margin-top:4px"><?php echo $a; ?></textarea>
                </label>
            </p>
            <button type="button" class="button oa-remove-row" style="color:#a00">&#x2715; Remove</button>
        </div>
        <?php endforeach; ?>
    </div>

    <p>
        <button type="button" class="button button-secondary" id="oa-add-faq">+ Add FAQ pair</button>
    </p>

    <script>
    (function () {
        var wrapper  = document.getElementById('oa-faq-wrapper');
        var addBtn   = document.getElementById('oa-add-faq');
        var rowCount = wrapper.querySelectorAll('.oa-faq-row').length;

        function bindRemove(btn) {
            btn.addEventListener('click', function () {
                btn.closest('.oa-faq-row').remove();
                renumber();
            });
        }

        function renumber() {
            wrapper.querySelectorAll('.oa-faq-row').forEach(function (row, idx) {
                var label = row.querySelector('strong');
                if (label) label.textContent = 'Q' + (idx + 1) + ': Question';
                row.querySelectorAll('input,textarea').forEach(function (el) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
                });
            });
            rowCount = wrapper.querySelectorAll('.oa-faq-row').length;
        }

        wrapper.querySelectorAll('.oa-remove-row').forEach(bindRemove);

        addBtn.addEventListener('click', function () {
            var i   = rowCount;
            var div = document.createElement('div');
            div.className = 'oa-faq-row';
            div.style.cssText = 'margin-bottom:10px;border:1px solid #ddd;padding:10px 12px;background:#fafafa;border-radius:3px';
            div.innerHTML =
                '<p style="margin:0 0 6px"><label>'
                + '<strong>Q' + (i + 1) + ': Question</strong><br>'
                + '<input type="text" name="oa_faq[' + i + '][question]"'
                + ' placeholder="e.g. Can you live on $300/month in the Philippines?"'
                + ' style="width:100%;margin-top:4px"></label></p>'
                + '<p style="margin:0 0 6px"><label>'
                + '<strong>Answer</strong><br>'
                + '<textarea name="oa_faq[' + i + '][answer]" rows="3"'
                + ' placeholder="Plain text or basic HTML. Avoid block-level tags."'
                + ' style="width:100%;margin-top:4px"></textarea></label></p>'
                + '<button type="button" class="button oa-remove-row" style="color:#a00">&#x2715; Remove</button>';
            wrapper.appendChild(div);
            bindRemove(div.querySelector('.oa-remove-row'));
            rowCount++;
        });
    }());
    </script>
    <?php
}

/**
 * Renders the Defined Terms meta box.
 *
 * @param WP_Post $post
 */
function oa_schema_terms_meta_box_render( WP_Post $post ): void {
    wp_nonce_field( 'oa_schema_terms_save', 'oa_schema_terms_nonce' );

    $items = get_post_meta( $post->ID, OA_SCHEMA_META_TERMS, true );
    if ( ! is_array( $items ) || empty( $items ) ) {
        $items = [ [ 'name' => '', 'description' => '' ] ];
    }
    ?>
    <p style="color:#666;margin-top:0">
        Define key terms explained in this article to generate
        <code>DefinedTerm</code> schema nodes (AEO glossary signals).
        Leave blank to omit.
    </p>

    <div id="oa-terms-wrapper">
        <?php foreach ( $items as $i => $item ) :
            $n = esc_attr( $item['name']        ?? '' );
            $d = esc_textarea( $item['description'] ?? '' );
            ?>
        <div class="oa-term-row" style="margin-bottom:10px;border:1px solid #ddd;padding:10px 12px;background:#fafafa;border-radius:3px">
            <p style="margin:0 0 6px">
                <label>
                    <strong>Term <?php echo $i + 1; ?></strong><br>
                    <input type="text"
                           name="oa_terms[<?php echo $i; ?>][name]"
                           value="<?php echo $n; ?>"
                           placeholder="e.g. BPO (Business Process Outsourcing)"
                           style="width:100%;margin-top:4px">
                </label>
            </p>
            <p style="margin:0 0 6px">
                <label>
                    <strong>Definition</strong><br>
                    <textarea name="oa_terms[<?php echo $i; ?>][description]"
                              rows="3"
                              placeholder="A concise, authoritative definition of the term."
                              style="width:100%;margin-top:4px"><?php echo $d; ?></textarea>
                </label>
            </p>
            <button type="button" class="button oa-remove-row" style="color:#a00">&#x2715; Remove</button>
        </div>
        <?php endforeach; ?>
    </div>

    <p>
        <button type="button" class="button button-secondary" id="oa-add-term">+ Add term</button>
    </p>

    <script>
    (function () {
        var wrapper  = document.getElementById('oa-terms-wrapper');
        var addBtn   = document.getElementById('oa-add-term');
        var rowCount = wrapper.querySelectorAll('.oa-term-row').length;

        function bindRemove(btn) {
            btn.addEventListener('click', function () {
                btn.closest('.oa-term-row').remove();
                renumber();
            });
        }

        function renumber() {
            wrapper.querySelectorAll('.oa-term-row').forEach(function (row, idx) {
                var label = row.querySelector('strong');
                if (label) label.textContent = 'Term ' + (idx + 1);
                row.querySelectorAll('input,textarea').forEach(function (el) {
                    el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
                });
            });
            rowCount = wrapper.querySelectorAll('.oa-term-row').length;
        }

        wrapper.querySelectorAll('.oa-remove-row').forEach(bindRemove);

        addBtn.addEventListener('click', function () {
            var i   = rowCount;
            var div = document.createElement('div');
            div.className = 'oa-term-row';
            div.style.cssText = 'margin-bottom:10px;border:1px solid #ddd;padding:10px 12px;background:#fafafa;border-radius:3px';
            div.innerHTML =
                '<p style="margin:0 0 6px"><label>'
                + '<strong>Term ' + (i + 1) + '</strong><br>'
                + '<input type="text" name="oa_terms[' + i + '][name]"'
                + ' placeholder="e.g. BPO (Business Process Outsourcing)"'
                + ' style="width:100%;margin-top:4px"></label></p>'
                + '<p style="margin:0 0 6px"><label>'
                + '<strong>Definition</strong><br>'
                + '<textarea name="oa_terms[' + i + '][description]" rows="3"'
                + ' placeholder="A concise, authoritative definition of the term."'
                + ' style="width:100%;margin-top:4px"></textarea></label></p>'
                + '<button type="button" class="button oa-remove-row" style="color:#a00">&#x2715; Remove</button>';
            wrapper.appendChild(div);
            bindRemove(div.querySelector('.oa-remove-row'));
            rowCount++;
        });
    }());
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// Save handler — shared for both meta boxes.
// ---------------------------------------------------------------------------

add_action( 'save_post', static function ( int $post_id ): void {
    // Block autosaves, revisions, and bulk-edits.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) )                   return;

    // ---- FAQ ---------------------------------------------------------------
    if (
        isset( $_POST['oa_schema_faq_nonce'] )
        && wp_verify_nonce( sanitize_key( $_POST['oa_schema_faq_nonce'] ), 'oa_schema_faq_save' )
        && current_user_can( 'edit_post', $post_id )
        && isset( $_POST['oa_faq'] )
        && is_array( $_POST['oa_faq'] )
    ) {
        $faq_items = [];
        foreach ( $_POST['oa_faq'] as $item ) {
            $q = sanitize_text_field( $item['question'] ?? '' );
            $a = sanitize_textarea_field( $item['answer'] ?? '' );
            if ( $q !== '' && $a !== '' ) {
                $faq_items[] = [ 'question' => $q, 'answer' => $a ];
            }
        }
        update_post_meta( $post_id, OA_SCHEMA_META_FAQ, $faq_items );
    }

    // ---- Defined Terms -----------------------------------------------------
    if (
        isset( $_POST['oa_schema_terms_nonce'] )
        && wp_verify_nonce( sanitize_key( $_POST['oa_schema_terms_nonce'] ), 'oa_schema_terms_save' )
        && current_user_can( 'edit_post', $post_id )
        && isset( $_POST['oa_terms'] )
        && is_array( $_POST['oa_terms'] )
    ) {
        $term_items = [];
        foreach ( $_POST['oa_terms'] as $item ) {
            $n = sanitize_text_field( $item['name']        ?? '' );
            $d = sanitize_textarea_field( $item['description'] ?? '' );
            if ( $n !== '' && $d !== '' ) {
                $term_items[] = [ 'name' => $n, 'description' => $d ];
            }
        }
        update_post_meta( $post_id, OA_SCHEMA_META_TERMS, $term_items );
    }
} );
