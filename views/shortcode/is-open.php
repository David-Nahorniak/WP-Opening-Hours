<?php
defined( 'ABSPATH' ) || exit;

$attributes = $this->data['attributes'];

$before_widget        = isset( $attributes['before_widget'] ) ? $attributes['before_widget'] : '';
$after_widget         = isset( $attributes['after_widget'] ) ? $attributes['after_widget'] : '';
$before_title         = isset( $attributes['before_title'] ) ? $attributes['before_title'] : '';
$after_title          = isset( $attributes['after_title'] ) ? $attributes['after_title'] : '';
$title                = isset( $attributes['title'] ) ? $attributes['title'] : null;
$text                 = isset( $attributes['text'] ) ? $attributes['text'] : '';
$is_open              = isset( $attributes['is_open'] ) ? $attributes['is_open'] : false;
$classes              = isset( $attributes['classes'] ) ? $attributes['classes'] : '';
$next_string          = isset( $attributes['next_string'] ) ? $attributes['next_string'] : null;
$next_period_classes  = isset( $attributes['next_period_classes'] ) ? $attributes['next_period_classes'] : '';
$today_string         = isset( $attributes['today_string'] ) ? $attributes['today_string'] : null;

/**
 * Variables defined by extraction
 *
 * @var     $before_widget      string w/ html before widget
 * @var     $after_widget       string w/ html after widget
 * @var     $before_title       string w/ html before title
 * @var     $after_title        string w/ html after title
 *
 * @var     $title              string w/ widget title
 * @var     $text               string w/ status text for widget
 * @var     $next_string        string w/ string for next period
 * @var     $next_period_classes  string w/ classes for next period span
 * @var     $is_open            bool whether set is open or not
 *
 * @var     $classes            string w/ classes for span
 */

echo wp_kses_post( $before_widget );

if ( ! empty( $title ) ) {
	echo wp_kses_post( $before_title ) . wp_kses_post( $title ) . wp_kses_post( $after_title );
}

echo '<span class="' . esc_attr( $classes ) . '">' . wp_kses_post( $text ) . '</span>';

if ( !$is_open && isset($next_string) && is_string($next_string) ) {
	echo '<span class="op-next-period ' . esc_attr( $next_period_classes ) . '">' . wp_kses_post( $next_string ) . '</span>';
}

if (isset($today_string) && is_string($today_string) && strlen($today_string) > 0) {
  echo '<span class="op-today">'. wp_kses_post( $today_string ) .'</span>';
}

echo wp_kses_post( $after_widget );