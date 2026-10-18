<?php
/**
 * Customizer control: drag-and-drop rows with left / centre / right zones.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builder control. The value is a JSON layout kept in a hidden input.
 */
class Semrs_Builder_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'semrs_builder';

	/**
	 * Rows: id => label.
	 *
	 * @var array
	 */
	public $rows = array();

	/**
	 * Elements: id => label.
	 *
	 * @var array
	 */
	public $elements = array();

	/**
	 * Render.
	 */
	public function render_content() {
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<div class="semrs-builder" data-rows="<?php echo esc_attr( wp_json_encode( $this->rows ) ); ?>" data-elements="<?php echo esc_attr( wp_json_encode( $this->elements ) ); ?>">
			<input type="hidden" class="semrs-builder-value" <?php $this->link(); ?> value="<?php echo esc_attr( $this->value() ); ?>">
			<div class="semrs-builder-ui"></div>
		</div>
		<?php
	}
}
