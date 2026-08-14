<?php

namespace OpeningHours\Test\Module\Shortcode;

use OpeningHours\Module\Shortcode\AbstractShortcode;
use OpeningHours\Test\OpeningHoursTestCase;

/**
 * Minimal concrete AbstractShortcode used only to exercise the protected
 * filterAttributes() whitelist behavior in isolation.
 */
class FilterAttributesTestShortcode extends AbstractShortcode {
  /** @inheritdoc */
  protected function init() {
    $this->shortcodeTag = 'op_filter_test';

    $this->defaultAttributes = array(
      'template' => 'table',
      'highlight' => 'nothing',
      'free_text' => null
    );

    $this->validAttributeValues = array(
      'template' => array('table', 'list'),
      'highlight' => array('nothing', 'period', 'day')
    );
  }

  /** @inheritdoc */
  public function shortcode(array $attributes) {
  }

  /** Initializes the shortcode attributes for the test. */
  public function initForTest() {
    $this->init();
  }

  /**
   * Exposes the protected filterAttributes() method for testing.
   *
   * @param     array $attributes
   * @return    array
   */
  public function runFilterAttributes(array $attributes) {
    return $this->filterAttributes($attributes);
  }
}

class FilterAttributesTest extends OpeningHoursTestCase {
  public function testInvalidValueReplacedByFirstWhitelistValue() {
    $sc = new FilterAttributesTestShortcode();
    $sc->initForTest();

    $result = $sc->runFilterAttributes(array(
      'template' => 'evil',
      'highlight' => 'day',
      'free_text' => 'something'
    ));

    $this->assertEquals('table', $result['template']);
    $this->assertEquals('day', $result['highlight']);
    $this->assertEquals('something', $result['free_text']);
  }

  public function testValidValuesAreKept() {
    $sc = new FilterAttributesTestShortcode();
    $sc->initForTest();

    $result = $sc->runFilterAttributes(array(
      'template' => 'list',
      'highlight' => 'period'
    ));

    $this->assertEquals('list', $result['template']);
    $this->assertEquals('period', $result['highlight']);
  }

  public function testAttributesWithoutWhitelistAreKept() {
    $sc = new FilterAttributesTestShortcode();
    $sc->initForTest();

    $result = $sc->runFilterAttributes(array(
      'unknown_attr' => 'kept',
      'template' => 'evil'
    ));

    $this->assertEquals('kept', $result['unknown_attr']);
    $this->assertEquals('table', $result['template']);
  }
}