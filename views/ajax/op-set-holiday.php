<?php
use OpeningHours\Module\CustomPostType\MetaBox\Holidays;
defined( 'ABSPATH' ) || exit;
?>
<tr class="op-holiday">
	<td class="col-name">
		<input type="text" name="<?php echo Holidays::POST_KEY; ?>[name][]" class="widefat" value="<?php echo esc_attr( $this->data['name'] ); ?>" />
	</td>
	<td class="col-date-start">
		<input type="text" name="<?php echo Holidays::POST_KEY; ?>[dateStart][]" class="widefat date-start input-gray" value="<?php echo esc_attr( $this->data['dateStart'] ); ?>" />
	</td>
	<td class="col-date-end">
		<input type="text" name="<?php echo Holidays::POST_KEY; ?>[dateEnd][]" class="widefat date-end input-gray" value="<?php echo esc_attr( $this->data['dateEnd'] ); ?>" />
	</td>
	<td class="col-remove">
		<button class="button button-remove remove-holiday has-icon"><i class="dashicons dashicons-no-alt"></i></button>
	</td>
</tr>