<?php
defined( 'ABSPATH' ) || exit;

use OpeningHours\Entity\IrregularOpening;
use OpeningHours\Entity\Set;
use OpeningHours\Util\Dates;

$attributes = $this->data['attributes'];

$before_widget        = isset( $attributes['before_widget'] ) ? $attributes['before_widget'] : '';
$after_widget         = isset( $attributes['after_widget'] ) ? $attributes['after_widget'] : '';
$before_title         = isset( $attributes['before_title'] ) ? $attributes['before_title'] : '';
$after_title          = isset( $attributes['after_title'] ) ? $attributes['after_title'] : '';
$title                = isset( $attributes['title'] ) ? $attributes['title'] : null;
$irregular_openings   = isset( $attributes['irregular_openings'] ) ? $attributes['irregular_openings'] : array();
$highlight            = isset( $attributes['highlight'] ) ? $attributes['highlight'] : false;
$class_highlighted    = isset( $attributes['class_highlighted'] ) ? $attributes['class_highlighted'] : '';
$date_format          = isset( $attributes['date_format'] ) ? $attributes['date_format'] : '';
$time_format          = isset( $attributes['time_format'] ) ? $attributes['time_format'] : '';

/**
 * variables defined by extract
 *
 * @var         $before_widget      string w/ HTML markup before Widget
 * @var         $after_widget       string w/ HTML markup after Widget
 * @var         $before_title       string w/ HTML markup before title
 * @var         $after_title        string w/ HTML markup after title
 *
 * @var         $set                Set object
 * @var         $irregular_openings ArrayObject w/ IrregularOpening objects of set
 * @var         $highlight          bool whether highlight active Holiday or not
 * @var         $title              string w/ Widget title
 *
 * @var         $class_highlighted  string w/ class for highlighted IrregularOpening
 * @var         $date_format        string w/ PHP date format
 * @var         $time_format        string w/ PHP time format
 */

if ( !count( $irregular_openings ) )
	return;

echo wp_kses_post( $before_widget );

if ( ! empty( $title ) ) {
	echo wp_kses_post( $before_title ) . wp_kses_post( $title ) . wp_kses_post( $after_title );
}
?>

<table class="op-table-irregular-openings op-table op-irregular-openings">
  <tbody>
  <?php
  /** @var IrregularOpening $io */
  foreach ($irregular_openings as $io) :
    $highlighted = ($highlight && $io->isInEffect()) ? $class_highlighted : '';
  ?>
    <tr class="op-irregular-opening <?php echo esc_attr( $highlighted ); ?>">
      <td class="col-name"><?php echo esc_html( $io->getName() ); ?></td>
      <td class="col-date"><?php echo esc_html( Dates::format( $date_format, $io->getDate() ) ); ?></td>
      <td class="col-time"><?php echo esc_html( $io->getFormattedTimeRange( $time_format ) ); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php echo wp_kses_post( $after_widget ); ?>