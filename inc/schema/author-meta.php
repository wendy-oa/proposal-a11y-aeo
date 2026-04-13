<?php
/**
 * Schema Author Meta — Extended User Profile Fields
 *
 * Adds three fields to every WordPress author profile page
 * (Users → Your Profile / Edit User):
 *
 *   1. Job Title          — e.g. "Founder & CEO"
 *   2. Social Profiles    — one URL per line (LinkedIn, Twitter, Medium …)
 *   3. Author Photo ID    — WP Media Library attachment ID
 *
 * OA_Schema reads these values when building the Person node so that every
 * article written by that author carries accurate, consistent author schema
 * without any per-post configuration.
 *
 * Meta keys are defined in config.php:
 *   OA_SCHEMA_UMETA_JOB_TITLE        → 'oa_author_job_title'
 *   OA_SCHEMA_UMETA_SOCIAL_PROFILES  → 'oa_author_social_profiles'
 *   OA_SCHEMA_UMETA_PHOTO_ID         → 'oa_author_photo_id'
 *
 * @package OA_Schema
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Render extra fields on the profile / edit-user screens.
// ---------------------------------------------------------------------------

add_action( 'show_user_profile', 'oa_schema_author_fields_render' );
add_action( 'edit_user_profile', 'oa_schema_author_fields_render' );

/**
 * Outputs the Schema Author Details fieldset inside the profile form.
 *
 * @param WP_User $user  The user being viewed / edited.
 */
function oa_schema_author_fields_render( WP_User $user ): void {
    $job_title  = get_user_meta( $user->ID, OA_SCHEMA_UMETA_JOB_TITLE, true );
    $social_raw = get_user_meta( $user->ID, OA_SCHEMA_UMETA_SOCIAL_PROFILES, true );
    $social_str = is_array( $social_raw ) ? implode( "\n", $social_raw ) : '';
    $photo_id   = (int) get_user_meta( $user->ID, OA_SCHEMA_UMETA_PHOTO_ID, true );
    ?>
    <h2>Schema Author Details</h2>
    <p style="color:#666;margin-top:-8px">
        These fields populate the <code>Person</code> node in JSON-LD schema on
        every article written by this author. Fill them in once and all articles
        are updated automatically.
    </p>

    <table class="form-table" role="presentation">

        <tr>
            <th scope="row">
                <label for="oa_author_job_title">Job Title</label>
            </th>
            <td>
                <input type="text"
                       id="oa_author_job_title"
                       name="oa_author_job_title"
                       value="<?php echo esc_attr( $job_title ); ?>"
                       class="regular-text"
                       placeholder="e.g. Founder &amp; CEO">
                <p class="description">
                    Shown in the <code>jobTitle</code> property of the Person schema node.
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="oa_author_social_profiles">Social Profiles</label>
            </th>
            <td>
                <textarea id="oa_author_social_profiles"
                          name="oa_author_social_profiles"
                          rows="5"
                          class="large-text"
                          placeholder="https://www.linkedin.com/in/username/&#10;https://twitter.com/username&#10;https://medium.com/@username"
                ><?php echo esc_textarea( $social_str ); ?></textarea>
                <p class="description">
                    One fully-qualified URL per line. Used for the <code>sameAs</code>
                    array in the Person node. LinkedIn is highest-priority for Google.
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="oa_author_photo_id">Author Photo</label>
            </th>
            <td>
                <input type="number"
                       id="oa_author_photo_id"
                       name="oa_author_photo_id"
                       value="<?php echo esc_attr( $photo_id ?: '' ); ?>"
                       class="small-text"
                       min="0"
                       placeholder="0">
                <?php if ( $photo_id ) : ?>
                    <br><img src="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'thumbnail' ) ); ?>"
                             alt="Author photo preview"
                             style="margin-top:8px;max-width:80px;border-radius:50%">
                <?php endif; ?>
                <p class="description">
                    WordPress Media Library <strong>Attachment ID</strong> (the number in
                    <em>Media › Edit › URL</em>). Used for the <code>image</code> property
                    in the Person node.
                </p>
            </td>
        </tr>

    </table>
    <?php
}

// ---------------------------------------------------------------------------
// Save handler.
// ---------------------------------------------------------------------------

add_action( 'personal_options_update',  'oa_schema_author_fields_save' );
add_action( 'edit_user_profile_update', 'oa_schema_author_fields_save' );

/**
 * Validates and persists the schema author fields.
 *
 * @param int $user_id  ID of the user being saved.
 */
function oa_schema_author_fields_save( int $user_id ): void {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }

    // Job title.
    if ( isset( $_POST['oa_author_job_title'] ) ) {
        update_user_meta(
            $user_id,
            OA_SCHEMA_UMETA_JOB_TITLE,
            sanitize_text_field( wp_unslash( $_POST['oa_author_job_title'] ) )
        );
    }

    // Social profiles — one URL per line; sanitise and filter.
    if ( isset( $_POST['oa_author_social_profiles'] ) ) {
        $raw_lines = explode( "\n", wp_unslash( $_POST['oa_author_social_profiles'] ) );
        $profiles  = array_values(
            array_filter(
                array_map( static fn( string $line ): string => esc_url_raw( trim( $line ) ), $raw_lines )
            )
        );
        update_user_meta( $user_id, OA_SCHEMA_UMETA_SOCIAL_PROFILES, $profiles );
    }

    // Author photo attachment ID.
    if ( isset( $_POST['oa_author_photo_id'] ) ) {
        update_user_meta(
            $user_id,
            OA_SCHEMA_UMETA_PHOTO_ID,
            absint( $_POST['oa_author_photo_id'] )
        );
    }
}
