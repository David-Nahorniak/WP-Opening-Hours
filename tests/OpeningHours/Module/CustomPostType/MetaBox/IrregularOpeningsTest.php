<?php

namespace OpeningHours\Test\Module\CustomPostType\MetaBox;

use OpeningHours\Module\CustomPostType\MetaBox\IrregularOpenings;
use OpeningHours\Test\OpeningHoursTestCase;

class IrregularOpeningsTest extends OpeningHoursTestCase {
  /**
   * Mocks sanitize_text_field so that it strips HTML tags, mirroring the
   * real behavior enough to prove that markup cannot reach the entity.
   */
  protected function mockSanitizeTextField() {
    \WP_Mock::wpFunction('sanitize_text_field', array(
      'times' => '0+',
      'return' => function ($value) {
        return is_string($value) ? strip_tags($value) : $value;
      }
    ));
  }

  public function testGetIrregularOpeningsRejectsMaliciousName() {
    $this->mockSanitizeTextField();

    $rawName = '"><script>alert(1)</script>';

    $data = array(
      'name' => array($rawName),
      'date' => array('2017-04-20'),
      'timeStart' => array('12:00'),
      'timeEnd' => array('14:00')
    );

    $ios = IrregularOpenings::getInstance()->getIrregularOpeningsFromPostData($data);

    $this->assertEquals(1, count($ios));

    $name = $ios[0]->getName();
    $this->assertEquals(strip_tags($rawName), $name);
    $this->assertNotContains('<script>', $name);
    $this->assertNotContains('</script>', $name);
  }

  public function testGetIrregularOpeningsKeepsCleanName() {
    $this->mockSanitizeTextField();

    $data = array(
      'name' => array('Summer Sale'),
      'date' => array('2017-04-20'),
      'timeStart' => array('12:00'),
      'timeEnd' => array('14:00')
    );

    $ios = IrregularOpenings::getInstance()->getIrregularOpeningsFromPostData($data);

    $this->assertEquals(1, count($ios));
    $this->assertEquals('Summer Sale', $ios[0]->getName());
  }

  public function testGetIrregularOpeningsRejectsInvalidDate() {
    $this->mockSanitizeTextField();

    $data = array(
      'name' => array('Bad Date'),
      'date' => array('not-a-date'),
      'timeStart' => array('12:00'),
      'timeEnd' => array('14:00')
    );

    $ios = IrregularOpenings::getInstance()->getIrregularOpeningsFromPostData($data);

    $this->assertEquals(0, count($ios));
  }

  public function testGetIrregularOpeningsMissingNameKeyReturnsEmpty() {
    $this->mockSanitizeTextField();

    $this->assertEquals(array(), IrregularOpenings::getInstance()->getIrregularOpeningsFromPostData(array(
      'date' => array('2017-04-20')
    )));
  }
}