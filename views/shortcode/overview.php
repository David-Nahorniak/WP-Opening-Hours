<?php
defined( 'ABSPATH' ) || exit;

use OpeningHours\Entity\Set;
use OpeningHours\Module\OpeningHours;

$attributes = $this->data['attributes'];

$before_widget      = isset( $attributes['before_widget'] ) ? $attributes['before_widget'] : '';
$after_widget       = isset( $attributes['after_widget'] ) ? $attributes['after_widget'] : '';
$before_title       = isset( $attributes['before_title'] ) ? $attributes['before_title'] : '';
$after_title        = isset( $attributes['after_title'] ) ? $attributes['after_title'] : '';
$title              = isset( $attributes['title'] ) ? $attributes['title'] : null;
$show_description   = isset( $attributes['show_description'] ) ? $attributes['show_description'] : false;
$days               = isset( $attributes['days'] ) ? $attributes['days'] : array();
$set                = isset( $attributes['set'] ) ? $attributes['set'] : null;

/**
 * Variables defined by extraction
 *
 * @var       $before_widget      string w/ html before widget
 * @var       $after_widget       string w/ html after widget
 * @var       $before_title       string w/ html before title
 * @var       $after_title        string w/ html after title
 *
 * @var       $title              string w/ widget title
 * @var       $show_description   bool whether to show description or not
 * @var       $days               array containing per day data
 *
 * @var       $set                Set whose Opening Hours to show
 */

echo wp_kses_post( $before_widget );

if ( $title ) {
	echo wp_kses_post( $before_title ) . wp_kses_post( $title ) . wp_kses_post( $after_title );
}

$description = $set->getDescription();
?>

<table class="op-table op-table-overview">
  <?php if ($show_description && !empty($description)) : ?>
    <tr class="op-row op-row-description">
      <td class="op-cell op-cell-description" colspan="2"><?php echo wp_kses_post( $description ); ?></td>
    </tr>
  <?php endif; ?>

  <?php foreach ($days as $dayData) : ?>
  <tr class="op-row op-row-day <?php echo esc_attr( $dayData['highlightedDayClass'] ); ?>">
    <th class="op-cell op-cell-heading" scope="row"><?php echo esc_html( $dayData['dayCaption'] ); ?></th>
    <td class="op-cell op-cell-periods"><?php echo $dayData['periodsMarkup']; ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<?php echo wp_kses_post( $after_widget ); ?>