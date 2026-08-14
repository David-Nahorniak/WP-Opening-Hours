<?php
defined( 'ABSPATH' ) || exit;
use OpeningHours\Entity\Period;
use OpeningHours\Util\Dates;

/** @var Period $period */
$period = $this->data['period'];
?>

<tr class="period">
	<td class="col-time-start">
		<input
			name="opening-hours[<?php echo absint( $period->getWeekday() ); ?>][start][]"
			type="text"
			class="input-timepicker input-time-end"
			value="<?php echo esc_attr( $period->getTimeStart()->format( Dates::STD_TIME_FORMAT ) ); ?>"/>
	</td>

	<td class="col-time-end">
		<input
			name="opening-hours[<?php echo absint( $period->getWeekday() ); ?>][end][]"
			type="text"
			class="input-timepicker input-time-end"
			value="<?php echo esc_attr( $period->getTimeEnd()->format( Dates::STD_TIME_FORMAT ) ); ?>"/>
	</td>

	<td class="col-delete-period">
		<a class="button delete-period has-icon red">
			<i class="dashicons dashicons-no-alt"></i>
		</a>
	</td>
</tr>