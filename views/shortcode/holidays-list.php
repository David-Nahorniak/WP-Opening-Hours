<?php
defined( 'ABSPATH' ) || exit;

use OpeningHours\Entity\Holiday;
use OpeningHours\Entity\Set;
use OpeningHours\Util\Dates;

$attributes = $this->data['attributes'];

$before_widget      = isset( $attributes['before_widget'] ) ? $attributes['before_widget'] : '';
$after_widget       = isset( $attributes['after_widget'] ) ? $attributes['after_widget'] : '';
$before_title       = isset( $attributes['before_title'] ) ? $attributes['before_title'] : '';
$after_title        = isset( $attributes['after_title'] ) ? $attributes['after_title'] : '';
$title              = isset( $attributes['title'] ) ? $attributes['title'] : null;
$holidays           = isset( $attributes['holidays'] ) ? $attributes['holidays'] : array();
$highlight          = isset( $attributes['highlight'] ) ? $attributes['highlight'] : false;
$class_highlighted  = isset( $attributes['class_highlighted'] ) ? $attributes['class_highlighted'] : '';
$date_format        = isset( $attributes['date_format'] ) ? $attributes['date_format'] : '';

/**
 * variables defined by extract
 *
 * @var         $before_widget      string w/ HTML markup before Widget
 * @var         $after_widget       string w/ HTML markup after Widget
 * @var         $before_title       string w/ HTML markup before title
 * @var         $after_title        string w/ HTML markup after title
 *
 * @var         $set                Set object
 * @var         $holidays           ArrayObject w/ Holiday objects of set
 * @var         $highlight          bool whether highlight active Holiday or not
 * @var         $title              string w/ Widget title
 *
 * @var         $class_holiday      string w/ class for holiday row
 * @var         $class_highlighted  string w/ class for highlighted Holiday
 * @var         $date_format        string w/ PHP date format
 */

if ( !count( $holidays ) )
	return;

echo wp_kses_post( $before_widget );

if ( ! empty( $title ) ) {
	echo wp_kses_post( $before_title ) . wp_kses_post( $title ) . wp_kses_post( $after_title );
}

?>
<dl class="op-list op-list-holidays">
    <?php
    /** @var Holiday $holiday */
    foreach ($holidays as $holiday) :
    $highlighted = ($highlight && $holiday->isActive()) ? $class_highlighted : '';
    ?>
    <dt class="<?php echo esc_attr( $highlighted ); ?>"><?php echo esc_html( $holiday->getName() ); ?></dt>
    <dd class="<?php echo esc_attr( $highlighted ); ?>"><?php echo esc_html( $holiday->getFormattedDateRange( $date_format ) ); ?></dd>
    <?php endforeach; ?>
</dl>

<?php echo wp_kses_post( $after_widget ); ?>