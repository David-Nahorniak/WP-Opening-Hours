<?php

namespace OpeningHours\Test\Module\CustomPostType\MetaBox;

use OpeningHours\Module\CustomPostType\MetaBox\Holidays;
use OpeningHours\Test\OpeningHoursTestCase;

class HolidaysTest extends OpeningHoursTestCase {
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

  public function testGetHolidaysRejectsMaliciousName() {
    $this->mockSanitizeTextField();

    $rawName = '"><script>alert(1)</script>';

    $data = array(
      'name' => array($rawName),
      'dateStart' => array('2017-01-01'),
      'dateEnd' => array('2017-01-02')
    );

    $holidays = Holidays::getInstance()->getHolidaysFromPostData($data);

    $this->assertEquals(1, count($holidays));

    $name = $holidays[0]->getName();
    $this->assertEquals(strip_tags($rawName), $name);
    $this->assertNotContains('<script>', $name);
    $this->assertNotContains('</script>', $name);
  }

  public function testGetHolidaysKeepsCleanName() {
    $this->mockSanitizeTextField();

    $data = array(
      'name' => array('New Year'),
      'dateStart' => array('2017-01-01'),
      'dateEnd' => array('2017-01-02')
    );

    $holidays = Holidays::getInstance()->getHolidaysFromPostData($data);

    $this->assertEquals(1, count($holidays));
    $this->assertEquals('New Year', $holidays[0]->getName());
  }

  public function testGetHolidaysRejectsInvalidDateFormat() {
    $this->mockSanitizeTextField();

    $data = array(
      'name' => array('Bad Date'),
      'dateStart' => array('not-a-date'),
      'dateEnd' => array('2017-01-02')
    );

    $holidays = Holidays::getInstance()->getHolidaysFromPostData($data);

    $this->assertEquals(0, count($holidays));
  }

  public function testGetHolidaysSkipsNameWithoutDates() {
    $this->mockSanitizeTextField();

    $data = array(
      'name' => array('Floating Name'),
      'dateStart' => array(''),
      'dateEnd' => array('')
    );

    $holidays = Holidays::getInstance()->getHolidaysFromPostData($data);

    $this->assertEquals(0, count($holidays));
  }

  public function testGetHolidaysMissingNameKeyReturnsEmpty() {
    $this->mockSanitizeTextField();

    $this->assertEquals(array(), Holidays::getInstance()->getHolidaysFromPostData(array(
      'dateStart' => array('2017-01-01')
    )));
  }
}